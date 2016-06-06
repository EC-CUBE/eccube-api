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
     */
    public function findAll(Application $app, Request $request, $table = null)
    {
        // TODO 暫定
        $scope_reuqired = 'read';
        $is_authorized = $this->verifyRequest($app, $scope_reuqired);
        $AccessToken = $app['oauth2.server.resource']->getAccessTokenData(
            \OAuth2\Request::createFromGlobals(),
            $app['oauth2.server.resource']->getResponse()
        );
        if (preg_match('/Bearer (\w+)/', $request->headers->get('authorization'))) {
            // Bearer トークンが存在する場合は認証チェック
            if (!$is_authorized) {
                return $app['oauth2.server.resource']->getResponse();
            }

            // Member で認証済みかどうか
            if (!$AccessToken['client']->hasMember()) {
                if ($table == 'order') { // TODO 暫定
                    $this->addErrors($app, 403, 'Access Forbidden');
                    return $this->getWrapperedResponseBy($app, $this->getErrors(), 403);
                }
            }
        }

        $metadata = EntityUtil::findMetadata($app, $table);
        if (!is_object($metadata)) {
            throw new NotFoundHttpException();
        }
        $className = $metadata->getName();

        $Repository = $app['orm.em']->getRepository($className);
        $Results = array();
        // TODO LIMIT, OFFSET が必要
        foreach ($Repository->findAll() as $Entity) {
            $Results[] = EntityUtil::entityToArray($app, $Entity);
        }

        return $this->getWrapperedResponseBy($app, array($table => $Results));
    }

    /**
     * IDを指定してエンティティを検索する
     */
    public function find(Application $app, Request $request, $table = null, $id = 0)
    {
        return $this->findEntity($app, $request,
                                 function ($id, $className) use ($app) {
                                     return $app['orm.em']->getRepository($className)->find($id);
                                 },
                                 array($id), $table);
    }

    /**
     * \Eccube\Entity\ProductCategory を検索する.
     */
    public function findProductCategory(Application $app, Request $request, $product_id = 0, $category_id = 0)
    {
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
     * コールバック関数を指定してエンティティを検索する.
     *
     * @param Application $app
     * @param Request $request
     * @param callable $callback コールバック関数
     * @param array $params_arr コールバック関数の引数
     * @param string $table 検索対象のテーブル名
     * @return \Symfony\Component\HttpFoundation\JsonResponse|Response
     */
    protected function findEntity(Application $app, Request $request, callable $callback = null, array $params_arr = array(), $table = null)
    {
        // TODO 暫定
        $scope_reuqired = 'read';
        if (!$this->verifyRequest($app, $scope_reuqired)) {
            return $app['oauth2.server.resource']->getResponse();
        }

        $metadata = EntityUtil::findMetadata($app, $table);
        if (!is_object($metadata)) {
            throw new NotFoundHttpException();
        }
        $className = $metadata->getName();
        $params_arr[] = $className;

        $Results = call_user_func_array($callback, $params_arr);

        return $this->getWrapperedResponseBy($app, array($table => EntityUtil::entityToArray($app, $Results)));
    }

    /**
     * エンティティを生成する.
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
     * コールバック関数を指定してエンティティを生成する.
     */
    protected function createEntity(Application $app, Request $request, callable $callback, $table = null)
    {
        // TODO 暫定
        $scope_reuqired = 'write';
        if (!$this->verifyRequest($app, $scope_reuqired)) {
            return $app['oauth2.server.resource']->getResponse();
        }

        $this->verifyRequest($app);
        $metadata = EntityUtil::findMetadata($app, $table);
        if (!is_object($metadata)) {
            throw new NotFoundHttpException();
        }
        $className = $metadata->getName();
        $Entity = new $className;
        EntityUtil::copyRelatePropertiesFromArray($app, $Entity, $request->request->all());

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
     */
    public function updateProductCategory(Application $app, Request $request, $product_id = 0, $category_id = 0)
    {
        // TODO エンティティが存在しない場合は 404 を返す.
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
     * コールバック関数を指定してエンティティを更新する.
     */
    public function updateEntity(Application $app, Request $request, callable $callback, array $params_arr = array(), $table = null)
    {
        // TODO 暫定
        $scope_reuqired = 'write';
        if (!$this->verifyRequest($app, $scope_reuqired)) {
            return $app['oauth2.server.resource']->getResponse();
        }

        $metadata = EntityUtil::findMetadata($app, $table);
        if (!is_object($metadata)) {
            throw new NotFoundHttpException();
        }
        $className = $metadata->getName();
        $params_arr[] = $className;

        $Entity = call_user_func_array($callback, $params_arr);
        // $Entity = $app['orm.em']->getRepository($className)->find($id);
        EntityUtil::copyRelatePropertiesFromArray($app, $Entity, $request->request->all());
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
     */
    public function delete(Application $app, Request $request, $table = null, $id = 0)
    {
        // TODO 暫定
        $scope_reuqired = 'write';
        if (!$this->verifyRequest($app, $scope_reuqired)) {
            return $app['oauth2.server.resource']->getResponse();
        }

        $metadata = EntityUtil::findMetadata($app, $table);
        if (!is_object($metadata)) {
            throw new NotFoundHttpException();
        }
        if (!array_key_exists('del_flg', $metadata->fieldMappings)) {
            // TODO エラーメッセージ
            return $this->getWrapperedResponseBy($app, $this->getErrors(), 405);
        }
        $className = $metadata->getName();

        $Repository = $app['orm.em']->getRepository($className);
        $Entity = $Repository->find($id);
        try {
            $Entity->setDelFlg(Constant::ENABLED);
            $app['orm.em']->flush($Entity);
        } catch (\Exception $e) {
            return $this->getWrapperedResponseBy($app, $this->getErrors(), 400);
        }

        return $this->getWrapperedResponseBy($app, null, 204);
    }
}
