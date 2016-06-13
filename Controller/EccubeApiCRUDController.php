<?php

/*
 * This file is part of the EccubeApi
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\EccubeApi\Controller;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Entry\AbstractEntity;
use Plugin\EccubeApi\Util\EntityUtil;
use OAuth2\HttpFoundationBridge\Request as BridgeRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * エンティティの CRUD API.
 *
 * @author Kentaro Ohkouchi
 */
class EccubeApiCRUDController extends AbstractApiController
{

    /**
     * すべてデータを返す.
     *
     * Customer で認証されている場合は、対象の Customer と関連する検索結果に絞り込みをする.
     *
     * @param Application $app
     * @param Request $request
     * @param string $table 検索対象のテーブル名(dtb_, mtb_ は除去する)
     * @return \OAuth2\HttpFoundationBridge\Response
     */
    public function findAll(Application $app, Request $request, $table = null)
    {
        $metadata = EntityUtil::findMetadata($app, $table);
        if (!is_object($metadata)) {
            return $this->getWrapperedErrorResponseBy($app);
        }

        $response = $this->doAuthorizedByFind($app, $request, $table);
        if ($response !== true) {
            return $response;
        }
        $className = $metadata->getName();

        $Repository = $app['orm.em']->getRepository($className);
        $Results = array();

        $AccessToken = $this->getAccessToken($app, $request);
        $searchConditions = array();
        // Customer で認証されている場合
        if (is_array($AccessToken) && $AccessToken['client']->hasCustomer()) {
            switch ($table) {
                case 'customer':
                     $searchConditions['id'] = $AccessToken['client']->getCustomer()->getId();
                    break;

                case 'customer_address':
                case 'order':
                case 'order_detail':
                    $searchConditions['Customer'] = $AccessToken['client']->getCustomer();
                    break;
            }
        }

        // TODO LIMIT, OFFSET が必要
        foreach ($Repository->findBy($searchConditions) as $Entity) {
            $Results[] = EntityUtil::entityToArray($app, $Entity);
        }

        return $this->getWrapperedResponseBy($app, array($table => $Results));
    }

    /**
     * IDを指定してエンティティを検索する.
     *
     * Customer で認証されている場合、他の Customer のデータを表示しようとすると HTTP Status 403 を返す.
     *
     * @param Application $app
     * @param Request $request
     * @param string $table 検索対象のテーブル名(dtb_, mtb_ は除去する)
     * @param integer $id 検索対象の ID
     * @return \OAuth2\HttpFoundationBridge\Response
     */
    public function find(Application $app, Request $request, $table = null, $id = 0)
    {
        $metadata = EntityUtil::findMetadata($app, $table);
        if (!is_object($metadata)) {
            return $this->getWrapperedErrorResponseBy($app);
        }
        $response = $this->doAuthorizedByFind($app, $request, $table);
        if ($response !== true) {
            return $response;
        }

        $Result = $this->getWrapperedErrorResponseBy($app);
        try {
            $Result = $this->findEntity($app, $request,
                                    function ($id, $className) use ($app, $table) {
                                        $Entity = $app['orm.em']->getRepository($className)->find($id);
                                        if (!is_object($Entity)) {
                                            throw new NotFoundHttpException();
                                        }
                                        return $Entity;
                                    },
                                    array($id), $table);
        } catch (NotFoundHttpException $e) {
            return $Result;
        } catch (\Exception $e) {
            $this->addErrors($app, 400, $e->getMessage());
            return $this->getWrapperedResponseBy($app, $this->getErrors(), 400);
        }

        // Bearer トークンが存在しない場合はレスポンスを返す
        if (!$this->hasBearerTokenHeader($request)) {
            return $Result;
        }
        // Customer で認証されている場合は結果をチェック
        $AccessToken = $this->getAccessToken($app, $request);
        if (is_array($AccessToken) && $AccessToken['client']->hasCustomer()) {
            switch ($table) {
                case 'customer':
                    if ($id != $AccessToken['client']->getCustomer()->getId()) {
                        return $this->getWrapperedErrorResponseBy($app, 'Access Forbidden', 403);
                    }
                    break;

                case 'customer_address':
                case 'order':
                case 'order_detail':
                    if ($Result[$table]['Customer']['id'] != $AccessToken['client']->getCustomer()->getId()) {
                        return $this->getWrapperedErrorResponseBy($app, 'Access Forbidden', 403);
                    }
                    break;
            }
        }

        return $Result;
    }

    /**
     * \Eccube\Entity\ProductCategory を検索する.
     *
     * Bearer トークンが存在する場合は認証チェックを行なう.
     *
     * @param Application $app
     * @param Request $request
     * @param integer $product_id 商品ID
     * @param integer $category_id カテゴリID
     * @return \OAuth2\HttpFoundationBridge\Response
     */
    public function findProductCategory(Application $app, Request $request, $product_id = 0, $category_id = 0)
    {
        $scope_required = 'product_category_read';
        $is_authorized = $this->verifyRequest($app, $request, $scope_required);
        if ($this->hasBearerTokenHeader($request)) {
            // Bearer トークンが存在する場合は認証チェック
            if (!$is_authorized) {
                return $app['oauth2.server.resource']->getResponse();
            }
        }

        // TODO 検索結果が0件の場合は 404 を返す.
        return $this->findEntity($app, $request,
                                 function ($product_id, $category_id, $className) use ($app) {
                                     return $app['orm.em']->getRepository($className)->findOneBy(
                                         array(
                                             'product_id' => $product_id,
                                             'category_id' => $category_id
                                     ));
                                 },
                                 array($product_id, $category_id), 'product_category');
    }

    /**
     * \Eccube\Entity\PaymentOption を検索する.
     *
     * @param Application $app
     * @param Request $request
     * @param integer $delivery_id 配送業者ID
     * @param integer $payment_id 支払い方法ID
     * @return \OAuth2\HttpFoundationBridge\Response
     */
    public function findPaymentOption(Application $app, Request $request, $delivery_id = 0, $payment_id = 0)
    {
        $scope_required = 'payment_option_read';
        if (!$this->verifyRequest($app, $request, $scope_required)) {
            return $app['oauth2.server.resource']->getResponse();
        }

        // TODO 検索結果が0件の場合は 404 を返す.
        return $this->findEntity($app, $request,
                                 function ($delivery_id, $payment_id, $className) use ($app) {
                                     return $app['orm.em']->getRepository($className)->findOneBy(
                                         array(
                                             'delivery_id' => $delivery_id,
                                             'payment_id' => $payment_id
                                     ));
                                 },
                                 array($delivery_id, $payment_id), 'payment_option');
    }

    /**
     * \Eccube\Entity\BlockPosition を検索する.
     * Bearer トークンが存在する場合は認証チェックを行なう.
     *
     * @param Application $app
     * @param Request $request
     * @param integer $page_id ページID
     * @param integer $target_id ターゲットID
     * @param integer $block_id ブロックID
     * @return \OAuth2\HttpFoundationBridge\Response
     */
    public function findBlockPosition(Application $app, Request $request, $page_id = 0, $target_id = 0, $block_id = 0)
    {
        $scope_required = 'block_position_read';
        if (!$this->verifyRequest($app, $request, $scope_required)) {
            return $app['oauth2.server.resource']->getResponse();
        }

        // TODO 検索結果が0件の場合は 404 を返す.
        return $this->findEntity($app, $request,
                                 function ($page_id, $target_id, $block_id, $className) use ($app) {
                                     return $app['orm.em']->getRepository($className)->findOneBy(
                                         array(
                                             'page_id' => $page_id,
                                             'target_id' => $target_id,
                                             'block_id' => $block_id
                                     ));
                                 },
                                 array($page_id, $target_id, $block_id), 'block_position');
    }

    /**
     * コールバック関数を指定してエンティティを検索する.
     *
     * コールバック関数は、検索キーとエンティティのクラス名を引数とし、検索結果のエンティティを返す.
     *
     * @param Application $app
     * @param Request $request
     * @param callable $callback コールバック関数
     * @param array $params_arr コールバック関数の引数
     * @param string $table 検索対象のテーブル名
     * @return \OAuth2\HttpFoundationBridge\Response
     */
    protected function findEntity(Application $app, Request $request, callable $callback = null, array $params_arr = array(), $table = null)
    {
        $metadata = EntityUtil::findMetadata($app, $table);
        if (!is_object($metadata)) {
            return $this->getWrapperedErrorResponseBy($app);
        }
        $className = $metadata->getName();
        $params_arr[] = $className;

        $Results = call_user_func_array($callback, $params_arr);

        return $this->getWrapperedResponseBy($app, array($table => EntityUtil::entityToArray($app, $Results)));
    }

    /**
     * エンティティを生成する.
     *
     * @param Application $app
     * @param Request $request
     * @param string $table 生成するテーブル
     * @return \OAuth2\HttpFoundationBridge\Response
     */
    public function create(Application $app, Request $request, $table = null)
    {
        return $this->createEntity($app, $request, function ($Response, $table, $Entity) use ($app) {
            $Response->headers->set("Location", $app->url('api_operation_find',
                                                          array(
                                                              'table' => $table,
                                                              'id' => $Entity->getId()
                                                          )));

            return;
        }, $table);
    }

    /**
     * \Eccube\Entity\ProductCategory を生成する.
     *
     * @param Application $app
     * @param Request $request
     * @return \OAuth2\HttpFoundationBridge\Response
     */
    public function createProductCategory(Application $app, Request $request)
    {
        return $this->createEntity($app, $request, function ($Response, $table, $Entity) use ($app) {
            $Response->headers->set("Location", $app->url('api_operation_find_product_category',
                                                          array(
                                                              'product_id' => $Entity->getProductId(),
                                                              'category_id' => $Entity->getCategoryId()
                                                          )));

            return;
        }, 'product_category');
    }

    /**
     * \Eccube\Entity\PaymentOption を生成する.
     *
     * @param Application $app
     * @param Request $request
     * @return \OAuth2\HttpFoundationBridge\Response
     */
    public function createPaymentOption(Application $app, Request $request)
    {
        return $this->createEntity($app, $request, function ($Response, $table, $Entity) use ($app) {
            $Response->headers->set("Location", $app->url('api_operation_find_payment_option',
                                                          array(
                                                              'delivery_id' => $Entity->getDeliveryId(),
                                                              'payment_id' => $Entity->getPaymentId()
                                                          )));

            return;
        }, 'payment_option');
    }

    /**
     * \Eccube\Entity\BlockPosition を生成する.
     *
     * @param Application $app
     * @param Request $request
     * @return \OAuth2\HttpFoundationBridge\Response
     */
    public function createBlockPosition(Application $app, Request $request)
    {
        return $this->createEntity($app, $request, function ($Response, $table, $Entity) use ($app) {
            $Response->headers->set("Location", $app->url('api_operation_find_block_position',
                                                          array(
                                                              'page_id' => $Entity->getPageId(),
                                                              'target_id' => $Entity->getTargetId(),
                                                              'block_id' => $Entity->getBlockId()
                                                          )));

            return;
        }, 'block_position');
    }

    /**
     * コールバック関数を指定してエンティティを生成する.
     * TODO パスワードの置き換え
     *
     * コールバック関数は、Response, テーブル名, 生成したエンティティを引数とし、 Location ヘッダに生成したリソースの URIをセットする.
     * 生成に成功した場合は HTTP Status 201 を返し、 Location ヘッダに生成したリソースの URI を出力する.
     * Customer で認証されている場合は、自分以外の Customer に関連するデータは生成できない.
     *
     * @param Application $app
     * @param Request $request
     * @param callable $callback コールバック関数
     * @param string $table データを生成するテーブル名(dtb_, mtb_ は除去する)
     * @return \OAuth2\HttpFoundationBridge\Response
     */
    protected function createEntity(Application $app, Request $request, callable $callback, $table = null)
    {
        $metadata = EntityUtil::findMetadata($app, $table);
        if (!is_object($metadata)) {
            return $this->getWrapperedErrorResponseBy($app);
        }

        $scope_required = $table.'_read '.$table.'_write';
        if (!$this->verifyRequest($app, $request, $scope_required)) {
            return $app['oauth2.server.resource']->getResponse();
        }

        $className = $metadata->getName();
        $Entity = new $className;
        EntityUtil::copyRelatePropertiesFromArray($app, $Entity, $request->request->all());

        // Customer で認証されている場合
        $AccessToken = $this->getAccessToken($app, $request);
        if (is_array($AccessToken) && $AccessToken['client']->hasCustomer()) {
            switch ($table) {
                case 'customer':
                    return $this->getWrapperedErrorResponseBy($app, 'Access Forbidden', 403);
                    break;
                case 'customer_address':
                    if ($Entity->getCustomer()->getId() != $AccessToken['client']->getCustomer()->getId()) {
                        return $this->getWrapperedErrorResponseBy($app, 'Access Forbidden', 403);
                    }
                    break;
            }
        }

        try {
            $app['orm.em']->persist($Entity);
            $app['orm.em']->flush($Entity);
            $Response = $app['oauth2.server.resource']->getResponse();
            call_user_func($callback, $Response, $table, $Entity);

            return $this->getWrapperedResponseBy($app, array(), 201);
        } catch (\Exception $e) {
            $this->addErrors($app, 400, $e->getMessage());
            return $this->getWrapperedResponseBy($app, $this->getErrors(), 400);
        }
    }

    /**
     * ID を指定してエンティティを更新する.
     *
     * @param Application $app
     * @param Request $request
     * @param string $table データを更新するテーブル名(dtb_, mtb_ は除去する)
     * @return \OAuth2\HttpFoundationBridge\Response
     */
    public function update(Application $app, Request $request, $table = null, $id = 0)
    {
        return $this->updateEntity($app, $request,
                                 function ($id, $className) use ($app) {
                                     return $app['orm.em']->getRepository($className)->find($id);
                                 },
                                 array($id), $table);

    }

    /**
     * \Eccube\Entity\ProductCategory を更新する.
     *
     * @param Application $app
     * @param Request $request
     * @param integer $product_id 商品ID
     * @param integer $category_id カテゴリID
     * @return \OAuth2\HttpFoundationBridge\Response
     */
    public function updateProductCategory(Application $app, Request $request, $product_id = 0, $category_id = 0)
    {
        return $this->updateEntity($app, $request,
                                   function ($product_id, $category_id, $className) use ($app) {
                                       return $app['orm.em']->getRepository($className)->findOneBy(
                                           array(
                                               'product_id' => $product_id,
                                               'category_id' => $category_id
                                           ));
                                   },
                                   array($product_id, $category_id), 'product_category');
    }

    /**
     * \Eccube\Entity\BlockPosition を更新する.
     *
     * @param Application $app
     * @param Request $request
     * @param integer $page_id ページID
     * @param integer $target_id ターゲットID
     * @param integer $block_id ブロックID
     * @return \OAuth2\HttpFoundationBridge\Response
     */
    public function updateBlockPosition(Application $app, Request $request, $page_id = 0, $target_id = 0, $block_id = 0)
    {
        return $this->updateEntity($app, $request,
                                   function ($page_id, $target_id, $block_id, $className) use ($app) {
                                       return $app['orm.em']->getRepository($className)->findOneBy(
                                           array(
                                               'page_id' => $page_id,
                                               'target_id' => $target_id,
                                               'block_id' => $block_id
                                           ));
                                   },
                                   array($page_id, $target_id, $block_id), 'block_position');
    }

    /**
     * コールバック関数を指定してエンティティを更新する.
     * TODO パスワードの置き換え
     *
     * コールバック関数は、対象エンティティの検索キー、クラス名を引数とし、更新対象のエンティティを返す.
     * 更新に成功した場合は HTTP Status 204 を返す.
     * Customer で認証されている場合は、自分以外の Customer に関連するデータは更新できない.

     * @param Application $app
     * @param Request $request
     * @param callable $callback コールバック関数
     * @param array $params_arr コールバック関数の引数
     * @param string 更新対象のテーブル名
     * @return \OAuth2\HttpFoundationBridge\Response
     */
    public function updateEntity(Application $app, Request $request, callable $callback, array $params_arr = array(), $table = null)
    {
        $metadata = EntityUtil::findMetadata($app, $table);
        if (!is_object($metadata)) {
            return $this->getWrapperedErrorResponseBy($app);
        }

        $scope_required = $table.'_read '.$table.'_write';
        if (!$this->verifyRequest($app, $request, $scope_required)) {
            return $app['oauth2.server.resource']->getResponse();
        }

        $className = $metadata->getName();
        $params_arr[] = $className;

        $Entity = null;
        try {
            $Entity = call_user_func_array($callback, $params_arr);
        } catch (\Exception $e) {
            $this->addErrors($app, 400, $e->getMessage());
            return $this->getWrapperedResponseBy($app, $this->getErrors(), 400);
        }

        if (!is_object($Entity)) {
            return $this->getWrapperedErrorResponseBy($app);
        }
        EntityUtil::copyRelatePropertiesFromArray($app, $Entity, $request->request->all());

        // Customer で認証されている場合
        $AccessToken = $this->getAccessToken($app, $request);
        if (is_array($AccessToken) && $AccessToken['client']->hasCustomer()) {
            switch ($table) {
                case 'customer':
                    if ($Entity->getId() != $AccessToken['client']->getCustomer()->getId()) {
                        return $this->getWrapperedErrorResponseBy($app, 'Access Forbidden', 403);
                    }
                    break;
                case 'customer_address':
                    if ($Entity->getCustomer()->getId() != $AccessToken['client']->getCustomer()->getId()) {
                        return $this->getWrapperedErrorResponseBy($app, 'Access Forbidden', 403);
                    }
                    break;
            }
        }

        try {
            $app['orm.em']->flush($Entity);
            return $this->getWrapperedResponseBy($app, null, 204);
        } catch (\Exception $e) {
            $this->addErrors($app, 400, $e->getMessage());
            return $this->getWrapperedResponseBy($app, $this->getErrors(), 400);
        }
    }

    /**
     * エンティティを削除する.
     *
     * このメソッドは、 del_flg フィールドを持つエンティティを論理削除する.
     * del_flg フィールドのないエンティティを削除しようとした場合は HTTP Status 400 を返す.
     * 削除に成功した場合、 HTTP Status 204 を返す.
     * Customer で認証されている場合は、自分以外の Customer に関連するデータは削除できない.
     *
     * @param Application $app
     * @param Request $request
     * @param string $table テーブル名
     * @param integer $id 削除対象のID
     * @return \OAuth2\HttpFoundationBridge\Response
     */
    public function delete(Application $app, Request $request, $table = null, $id = 0)
    {
        $metadata = EntityUtil::findMetadata($app, $table);
        if (!is_object($metadata)) {
            return $this->getWrapperedErrorResponseBy($app);
        }
        $scope_required = $table.'_read '.$table.'_write';
        if (!$this->verifyRequest($app, $request, $scope_required)) {
            return $app['oauth2.server.resource']->getResponse();
        }

        if (!array_key_exists('del_flg', $metadata->fieldMappings)) {
            return $this->getWrapperedErrorResponseBy($app, 'Method Not Allowed', 405);
        }
        $className = $metadata->getName();

        $Repository = $app['orm.em']->getRepository($className);
        $Entity = $Repository->find($id);
        if (!is_object($Entity)) {
            return $this->getWrapperedErrorResponseBy($app);
        }

        // Customer で認証されている場合
        $AccessToken = $this->getAccessToken($app, $request);
        if (is_array($AccessToken) && $AccessToken['client']->hasCustomer()) {
            switch ($table) {
                case 'customer':
                    return $this->getWrapperedErrorResponseBy($app, 'Access Forbidden', 403);
                    break;
                case 'customer_address':
                    if ($Entity->getCustomer()->getId() != $AccessToken['client']->getCustomer()->getId()) {
                        return $this->getWrapperedErrorResponseBy($app, 'Access Forbidden', 403);
                    }
                    break;
            }
        }

        try {
            $Entity->setDelFlg(Constant::ENABLED);
            $app['orm.em']->flush($Entity);
        } catch (\Exception $e) {
            return $this->getWrapperedResponseBy($app, $this->getErrors(), 400);
        }

        return $this->getWrapperedResponseBy($app, null, 204);
    }

    /**
     * 認証の必要なテーブルかどうか.
     *
     * @param string $table テーブル名
     * @return 認証が必要なテーブルの場合 true, 不要なテーブルの場合 false
     */
    protected function requireAuthorization($table)
    {
        switch ($table) {
            // 認証不要なテーブル
            case 'news':
            case 'product':
            case 'product_class':
            case 'product_image':
            case 'product_tag':
            case 'product_category':
            case 'job':
            case 'pref':
            case 'sex':
                return false;
            default:
                return true;
        }
    }

    /**
     * 検索メソッドの認証をします.
     *
     * @param Application $app
     * @param Request $request
     * @param string $table テーブル名
     * @return \OAuth2\HttpFoundationBridge\Response|boolean 認証が失敗した場合エラーレスポンス, 成功した場合 true
     */
    protected function doAuthorizedByFind(Application $app, Request $request, $table)
    {
        $metadata = EntityUtil::findMetadata($app, $table);
        if (!is_object($metadata)) {
            return $this->getWrapperedErrorResponseBy($app);
        }

        $scope_required = $table.'_read';
        $is_authorized = $this->verifyRequest($app, $request, $scope_required);
        $AccessToken = $this->getAccessToken($app, $request);
        if ($this->hasBearerTokenHeader($request)
            || $this->requireAuthorization($table)) {
            // Bearer トークンが存在する場合は認証チェック
            if (!$is_authorized) {
                return $app['oauth2.server.resource']->getResponse();
            }

            // Customer で認証すれば参照可能なテーブル
            if ($AccessToken['client']->hasCustomer()) {
                switch ($table) {
                    case 'customer':
                    case 'customer_address':
                    case 'order':
                    case 'order_detail':
                        break;
                    default:
                        return $this->getWrapperedErrorResponseBy($app, 'Access Forbidden', 403);
                }
            }
        }
        return true;
    }

    /**
     * Authorization ヘッダの Bearer トークンが存在するかどうか.
     *
     * @param Request $request
     * @return boolean リクエストヘッダに Bearer トークンが存在する場合 true
     */
    protected function hasBearerTokenHeader(Request $request)
    {
        return preg_match('/Bearer (\w+)/', $request->headers->get('authorization')) > 0;
    }

    /**
     * Request から AccessToken を取得します.
     *
     * @param Application $app
     * @param Request $request
     * @see Plugin\EccubeApi\Repository\OAuth2\AccessTokenRepository::getAccessToken()
     */
    protected function getAccessToken(Application $app, Request $request)
    {
        $AccessToken = $app['oauth2.server.resource']->getAccessTokenData(
            BridgeRequest::createFromRequest($request),
            $app['oauth2.server.resource']->getResponse()
        );
        return $AccessToken;
    }
}
