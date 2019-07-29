<?php

/*
 * This file is part of the EccubeApi
 *
 * Copyright (C) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\EccubeApi\Tests\Web;

use Eccube\Common\Constant;
use Eccube\Entity\AbstractEntity;
use Eccube\Entity\MailHistory;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\CustomerFavoriteProduct;
use Eccube\Entity\ProductTag;
use Eccube\Entity\AuthorityRole;
use Plugin\EccubeApi\Util\EntityUtil;
use Symfony\Component\Security\Core\User\UserInterface;

class EccubeApiCRUDAuthencationTest extends AbstractEccubeApiWebTestCase
{
    protected $Customer;
    protected $Member;
    protected $OAuth2Client;
    protected $UserInfo;
    protected $state;
    protected $nonce;

    public function setUp()
    {
        parent::setUp();
        $this->Customer = $this->createCustomer();
        $this->Member = $this->app['eccube.repository.member']->find(2);
    }

    public function testUserInfoAuthencation()
    {
        $access_token = $this->doAuthencation($this->Customer, 'openid email');
        // API Request
        $crawler = $this->client->request(
            'GET',
            $this->app->path('oauth2_server_userinfo'),
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
                'CONTENT_TYPE' => 'application/json',
            )
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $UserInfo = json_decode($this->client->getResponse()->getContent(), true);

        $this->expected = $this->Customer->getEmail();
        $this->actual = $UserInfo['email'];
        $this->verify();
    }

    /**
     * 認証なしで取得可能な API のテスト
     */
    public function testFindAllWithPublicAccess()
    {
        $headers = array(
            'CONTENT_TYPE' => 'application/json'
        );
        $metadatas = $this->app['orm.em']->getMetadataFactory()->getAllMetadata();
        foreach ($metadatas as $metadata) {
            $className = $metadata->getName();
            $Reflect = new \ReflectionClass($className);
            if (!$Reflect->hasMethod('toArray')) {
                // FIXME https://github.com/EC-CUBE/ec-cube/pull/1576
                continue;
            }
            // $this->assertTrue($Reflect->hasMethod('toArray'), 'toArray() が存在するかどうか');
            if (strpos($metadata->table['name'], 'dtb_') === false
                && strpos($metadata->table['name'], 'mtb_') === false) {
                // dtb_ or mtb_ 以外のテーブルは除外
                continue;
            }
            $table_name = str_replace(array('dtb_', 'mtb_'), '', $metadata->table['name']);
            // 未使用のため除外
            switch ($table_name) {
                case 'country':
                case 'zip':
                case 'authority':
                case 'category_count':
                case 'category_total_count':
                    continue 2;
            }

            // public access 可能なテーブル
            switch ($table_name) {
                case 'news':
                case 'product':
                case 'product_class':
                case 'product_image':
                case 'product_tag':
                case 'product_category':
                case 'category':
                case 'job':
                case 'pref':
                case 'sex':
                case 'tag':
                    $this->expected = 200;
                    break;
                default:
                    $this->expected = 401;
            }

            $crawler = $this->client->request(
                'GET',
                $this->app->path('api_operation_findall', array('table' => $table_name)),
                array(),
                array(),
                $headers
            );

            $this->actual = $this->client->getResponse()->getStatusCode();
            $this->verify($table_name);
        }
    }

    /**
     * 認証なしで取得可能な API のテスト
     */
    public function testFindOnceWithPublicAccess()
    {
        $this->createEntities();
        $headers = array(
            'CONTENT_TYPE' => 'application/json'
        );
        $metadatas = $this->app['orm.em']->getMetadataFactory()->getAllMetadata();
        foreach ($metadatas as $metadata) {
            $className = $metadata->getName();
            $Reflect = new \ReflectionClass($className);
            if (!$Reflect->hasMethod('toArray')) {
                // FIXME https://github.com/EC-CUBE/ec-cube/pull/1576
                continue;
            }
            // $this->assertTrue($Reflect->hasMethod('toArray'), 'toArray() が存在するかどうか');
            if (strpos($metadata->table['name'], 'dtb_') === false
                && strpos($metadata->table['name'], 'mtb_') === false) {
                // dtb_ or mtb_ 以外のテーブルは除外
                continue;
            }
            $table_name = str_replace(array('dtb_', 'mtb_'), '', $metadata->table['name']);
            // 未使用のため除外
            switch ($table_name) {
                case 'country':
                case 'zip':
                case 'authority':
                case 'category_count':
                case 'category_total_count':
                    continue 2;
            }
            $Entity = $this->app['orm.em']->getRepository($className)->findOneBy(array());
            $route = 'api_operation_find';
            // public access 可能なテーブル
            switch ($table_name) {
                case 'news':
                case 'product':
                case 'product_class':
                case 'product_image':
                case 'product_tag':
                case 'category':
                case 'job':
                case 'pref':
                case 'sex':
                case 'tag':
                    $this->expected = 200;
                    $params = array(
                        'table' => $table_name,
                        'id' => $Entity->getId()
                    );
                    break;
                case 'product_category':
                    $this->expected = 200;
                    $route = 'api_operation_find_product_category';
                    $params = array(
                        'table' => $table_name,
                        'category_id' => $Entity->getCategoryId(),
                        'product_id' => $Entity->getProductId()
                    );
                    break;
                // 複合キーのテーブルは除外
                case 'block_position':
                case 'payment_option':
                case 'category_total_count':
                case 'category_count':
                    continue 2;
                    break;
                default:
                    $this->expected = 401;
                    $params = array(
                        'table' => $table_name,
                        'id' => $Entity->getId()
                    );
            }

            $crawler = $this->client->request(
                'GET',
                $this->app->path($route, $params),
                array(),
                array(),
                $headers
            );

            $this->actual = $this->client->getResponse()->getStatusCode();
            $this->verify($table_name);
        }
    }

    /**
     * Customer で取得可能な API のテスト
     */
    public function testFindAllWithAllowCustomer()
    {
        $metadatas = $this->app['orm.em']->getMetadataFactory()->getAllMetadata();
        foreach ($metadatas as $metadata) {
            $className = $metadata->getName();
            $Reflect = new \ReflectionClass($className);
            if (!$Reflect->hasMethod('toArray')) {
                // FIXME https://github.com/EC-CUBE/ec-cube/pull/1576
                continue;
            }
            // $this->assertTrue($Reflect->hasMethod('toArray'), 'toArray() が存在するかどうか');
            if (strpos($metadata->table['name'], 'dtb_') === false
                && strpos($metadata->table['name'], 'mtb_') === false) {
                // dtb_ or mtb_ 以外のテーブルは除外
                continue;
            }
            $table_name = str_replace(array('dtb_', 'mtb_'), '', $metadata->table['name']);
            // 未使用のため除外
            switch ($table_name) {
                case 'country':
                case 'zip':
                case 'authority':
                case 'category_count':
                case 'category_total_count':
                    continue 2;
            }
            $access_token = $this->doAuthencation($this->Customer, $table_name.'_read');
            $headers = array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
                'CONTENT_TYPE' => 'application/json'
            );

            // Customer で取得可能なテーブル
            switch ($table_name) {
                case 'customer':
                case 'customer_address':
                case 'order':
                case 'order_detail':
                    $this->expected = 200;
                    break;
                default:
                    $this->expected = 403;
            }

            $crawler = $this->client->request(
                'GET',
                $this->app->path('api_operation_findall', array('table' => $table_name)),
                array(),
                array(),
                $headers
            );

            $this->actual = $this->client->getResponse()->getStatusCode();
            $this->verify($table_name);
        }
    }

    /**
     * Customer で取得可能な API のテスト
     */
    public function testFindOnceWithAllowCustomer()
    {
        $this->createEntities();
        $this->createOrder($this->Customer);

        $metadatas = $this->app['orm.em']->getMetadataFactory()->getAllMetadata();
        foreach ($metadatas as $metadata) {
            $className = $metadata->getName();
            $Reflect = new \ReflectionClass($className);
            if (!$Reflect->hasMethod('toArray')) {
                // FIXME https://github.com/EC-CUBE/ec-cube/pull/1576
                continue;
            }
            // $this->assertTrue($Reflect->hasMethod('toArray'), 'toArray() が存在するかどうか');
            if (strpos($metadata->table['name'], 'dtb_') === false
                && strpos($metadata->table['name'], 'mtb_') === false) {
                // dtb_ or mtb_ 以外のテーブルは除外
                continue;
            }
            $table_name = str_replace(array('dtb_', 'mtb_'), '', $metadata->table['name']);
            // 未使用のため除外
            switch ($table_name) {
                case 'country':
                case 'zip':
                case 'authority':
                case 'category_count':
                case 'category_total_count':
                    continue 2;
            }
            $access_token = $this->doAuthencation($this->Customer, $table_name.'_read');
            $headers = array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
                'CONTENT_TYPE' => 'application/json'
            );

            $route = 'api_operation_find';
            // Customer でアクセス可能なテーブル
            switch ($table_name) {
                case 'customer_address':
                case 'order':
                    $Entity = $this->app['orm.em']->getRepository($className)->findOneBy(array('Customer' => $this->Customer));
                    $this->expected = 200;
                    $params = array(
                        'table' => $table_name,
                        'id' => $Entity->getId()
                    );
                    break;

                case 'customer':
                    $this->expected = 200;
                    $params = array(
                        'table' => $table_name,
                        'id' => $this->Customer->getId()
                    );
                    break;

                case 'order_detail':
                    $qb = $this->app['orm.em']->getRepository($className)->createQueryBuilder('od')
                        ->leftJoin('od.Order', 'o')
                        ->andWhere('o.Customer = :Customer')
                        ->setParameter('Customer', $this->Customer)
                        ->setMaxResults(1);
                    $Entity = $qb->getQuery()->getOneOrNullResult();
                    $this->expected = 200;
                    $params = array(
                        'table' => $table_name,
                        'id' => $Entity->getId()
                    );
                    break;

                // 複合キーのテーブルは除外
                case 'block_position':
                case 'payment_option':
                case 'category_total_count':
                case 'category_count':
                case 'product_category':
                    continue 2;
                    break;
                default:
                    $Entity = $this->app['orm.em']->getRepository($className)->findOneBy(array());
                    $this->expected = 403;
                    $params = array(
                        'table' => $table_name,
                        'id' => $Entity->getId()
                    );
            }

            $crawler = $this->client->request(
                'GET',
                $this->app->path($route, $params),
                array(),
                array(),
                $headers
            );

            $this->actual = $this->client->getResponse()->getStatusCode();
            $this->verify($table_name);
        }
    }

    /**
     * Customer で作成可能な API のテスト
     */
    public function testCreateWithAllowCustomer()
    {
        $this->createEntities();
        $this->createOrder($this->Customer);

        $metadatas = $this->app['orm.em']->getMetadataFactory()->getAllMetadata();
        foreach ($metadatas as $metadata) {
            $className = $metadata->getName();
            $Reflect = new \ReflectionClass($className);
            if (!$Reflect->hasMethod('toArray')) {
                // FIXME https://github.com/EC-CUBE/ec-cube/pull/1576
                continue;
            }
            // $this->assertTrue($Reflect->hasMethod('toArray'), 'toArray() が存在するかどうか');
            if (strpos($metadata->table['name'], 'dtb_') === false
                && strpos($metadata->table['name'], 'mtb_') === false) {
                // dtb_ or mtb_ 以外のテーブルは除外
                continue;
            }
            $table_name = str_replace(array('dtb_', 'mtb_'), '', $metadata->table['name']);
            // 未使用のため除外
            switch ($table_name) {
                case 'country':
                case 'zip':
                case 'authority':
                case 'category_count':
                case 'category_total_count':
                    continue 2;
            }
            $access_token = $this->doAuthencation($this->Customer, $table_name.'_read '.$table_name.'_write');
            if (is_numeric($access_token)) {
                // アクセストークンが取得できない場合は除外
                $this->expected = 401; // scope が許可されていない
                $this->actual = $access_token;
                $this->verify('アクセストークンが取得できない: '.$table_name);
                continue;
            }
            $headers = array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
                'CONTENT_TYPE' => 'application/json'
            );

            $route = 'api_operation_create';
            // Customer で作成可能なテーブル
            switch ($table_name) {
                case 'customer_address':
                    $this->expected = 201;
                    break;

                // 複合キーのテーブルは除外
                case 'block_position':
                case 'payment_option':
                case 'category_total_count':
                case 'category_count':
                case 'product_category':
                    continue 2;
                    break;
                default:
                    $this->expected = 403;
            }

            // 各テーブル特有の処理
            $Entity = $this->app['orm.em']->getRepository($className)->findOneBy(array());
            switch ($table_name) {
                case 'customer':
                    $Entity->setSecretKey($this->app['eccube.repository.customer']->getUniqueSecretKey($this->app));
                    break;
                case 'customer_address':
                    $Entity->setCustomer($this->Customer);
                    break;
                case 'block':
                    $Entity->setFileName('test');
                    break;
                case 'product_stock':
                    $Entity->setProductClass(null);
                    break;

                default:
            }
            $properties = $this->createProperties($metadata);
            $Entity->setPropertiesFromArray($properties);
            $arrayEntity = EntityUtil::entityToArray($this->app, $Entity);
            if (array_key_exists('id', $arrayEntity)) {
                if (strpos($metadata->table['name'], 'mtb_') !== false) {
                    $arrayEntity['id'] = 999;
                } else {
                    unset($arrayEntity['id']);
                }
            }

            $crawler = $this->client->request(
                'POST',
                $this->app->path($route, array('table' => $table_name)),
                array(),
                array(),
                $headers,
                json_encode($arrayEntity)
            );

            $this->actual = $this->client->getResponse()->getStatusCode();
            $this->verify($table_name);
        }
    }

    /**
     * Customer で更新可能な API のテスト
     */
    public function testUpdateWithAllowCustomer()
    {
        $this->createEntities();
        $this->createOrder($this->Customer);

        $metadatas = $this->app['orm.em']->getMetadataFactory()->getAllMetadata();
        foreach ($metadatas as $metadata) {
            $className = $metadata->getName();
            $Reflect = new \ReflectionClass($className);
            if (!$Reflect->hasMethod('toArray')) {
                // FIXME https://github.com/EC-CUBE/ec-cube/pull/1576
                continue;
            }
            // $this->assertTrue($Reflect->hasMethod('toArray'), 'toArray() が存在するかどうか');
            if (strpos($metadata->table['name'], 'dtb_') === false
                && strpos($metadata->table['name'], 'mtb_') === false) {
                // dtb_ or mtb_ 以外のテーブルは除外
                continue;
            }
            $table_name = str_replace(array('dtb_', 'mtb_'), '', $metadata->table['name']);
            // 未使用のため除外
            switch ($table_name) {
                case 'country':
                case 'zip':
                case 'authority':
                case 'category_count':
                case 'category_total_count':
                    continue 2;
            }
            $access_token = $this->doAuthencation($this->Customer, $table_name.'_read '.$table_name.'_write');
            if (is_numeric($access_token)) {
                // アクセストークンが取得できない場合は除外
                $this->expected = 401; // scope が許可されていない
                $this->actual = $access_token;
                $this->verify('アクセストークンが取得できない: '.$table_name);
                continue;
            }
            $headers = array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
                'CONTENT_TYPE' => 'application/json'
            );

            $route = 'api_operation_put';
            // Customer で更新可能なテーブル
            switch ($table_name) {
                case 'customer':
                case 'customer_address':
                    $this->expected = 204;
                    break;

                // 複合キーのテーブルは除外
                case 'block_position':
                case 'payment_option':
                case 'category_total_count':
                case 'category_count':
                case 'product_category':
                    continue 2;
                    break;
                default:
                    $this->expected = 403;
            }

            // 各テーブル特有の処理
            $Entity = $this->app['orm.em']->getRepository($className)->findOneBy(array());
            switch ($table_name) {
                case 'customer':
                    $Entity->setSecretKey($this->app['eccube.repository.customer']->getUniqueSecretKey($this->app));
                    break;
                case 'customer_address':
                    $Entity->setCustomer($this->Customer);
                    break;
                case 'block':
                    $Entity->setFileName('test');
                    break;
                case 'product_stock':
                    $Entity->setProductClass(null);
                    break;

                default:
            }
            $properties = $this->createProperties($metadata);
            $Entity->setPropertiesFromArray($properties);
            $arrayEntity = EntityUtil::entityToArray($this->app, $Entity);
            if (array_key_exists('id', $arrayEntity)) {
                if (strpos($metadata->table['name'], 'mtb_') !== false) {
                    $arrayEntity['id'] = 999;
                } else {
                    unset($arrayEntity['id']);
                }
            }

            $crawler = $this->client->request(
                'PUT',
                $this->app->path($route, array('table' => $table_name, 'id' => $Entity->getId())),
                array(),
                array(),
                $headers,
                json_encode($arrayEntity)
            );

            $this->actual = $this->client->getResponse()->getStatusCode();
            $this->verify($table_name);
        }
    }

    /**
     * Customer で削除可能な API のテスト
     */
    public function testDeleteWithAllowCustomer()
    {
        $this->createEntities();
        $this->createOrder($this->Customer);

        $metadatas = $this->app['orm.em']->getMetadataFactory()->getAllMetadata();
        foreach ($metadatas as $metadata) {
            $className = $metadata->getName();
            $Reflect = new \ReflectionClass($className);
            if (!$Reflect->hasMethod('toArray')) {
                // FIXME https://github.com/EC-CUBE/ec-cube/pull/1576
                continue;
            }
            // $this->assertTrue($Reflect->hasMethod('toArray'), 'toArray() が存在するかどうか');
            if (strpos($metadata->table['name'], 'dtb_') === false
                && strpos($metadata->table['name'], 'mtb_') === false) {
                // dtb_ or mtb_ 以外のテーブルは除外
                continue;
            }
            $table_name = str_replace(array('dtb_', 'mtb_'), '', $metadata->table['name']);
            // 未使用のため除外
            switch ($table_name) {
                case 'country':
                case 'zip':
                case 'authority':
                case 'category_count':
                case 'category_total_count':
                    continue 2;
            }
            $access_token = $this->doAuthencation($this->Customer, $table_name.'_read '.$table_name.'_write');
            if (is_numeric($access_token)) {
                // アクセストークンが取得できない場合は除外
                $this->expected = 401; // scope が許可されていない
                $this->actual = $access_token;
                $this->verify('アクセストークンが取得できない: '.$table_name);
                continue;
            }
            $headers = array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
                'CONTENT_TYPE' => 'application/json'
            );

            $route = 'api_operation_delete';
            // Customer で削除可能なテーブル
            switch ($table_name) {
                case 'customer_address':
                    $this->expected = 204;
                    break;

                // 複合キーのテーブルは除外
                case 'block_position':
                case 'payment_option':
                case 'category_total_count':
                case 'category_count':
                case 'product_category':
                    continue 2;
                    break;
                default:
                    $this->expected = 405;
            }

            // 各テーブル特有の処理
            $Entity = $this->app['orm.em']->getRepository($className)->findOneBy(array());
            switch ($table_name) {
                case 'customer':
                    $Entity->setSecretKey($this->app['eccube.repository.customer']->getUniqueSecretKey($this->app));
                    break;
                case 'customer_address':
                    $Entity->setCustomer($this->Customer);
                    break;
                case 'block':
                    $Entity->setFileName('test');
                    break;
                case 'product_stock':
                    $Entity->setProductClass(null);
                    break;

                default:
            }
            $properties = $this->createProperties($metadata);
            $Entity->setPropertiesFromArray($properties);
            $arrayEntity = EntityUtil::entityToArray($this->app, $Entity);
            if (array_key_exists('id', $arrayEntity)) {
                if (strpos($metadata->table['name'], 'mtb_') !== false) {
                    $arrayEntity['id'] = 999;
                } else {
                    unset($arrayEntity['id']);
                }
            }

            $crawler = $this->client->request(
                'DELETE',
                $this->app->path($route, array('table' => $table_name, 'id' => $Entity->getId())),
                array(),
                array(),
                $headers,
                json_encode($arrayEntity)
            );

            $this->actual = $this->client->getResponse()->getStatusCode();
            $this->verify($table_name);
        }
    }

    /**
     * 自分自身のパスワード, メールアドレスを変更しようとする
     */
    public function testUpdateCustomerWithIlleguler()
    {
        // $CustomerAddresses = $this->app['eccube.repository.customer_address']->findBy(array('Customer' => $this->Customer));
        // foreach ($CustomerAddresses as $CustomerAddress) {
        //     $this->app['orm.em']->remove($CustomerAddress);
        //     $this->app['orm.em']->flush($CustomerAddress);
        // }
        $arrayEntity = array(
            'email' => 'test@example.com',
            'password' => 'password'
        );

        $access_token = $this->doAuthencation($this->Customer, 'customer_read customer_write');
        $headers = array(
            'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
            'CONTENT_TYPE' => 'application/json'
        );

        $customer_id = $this->Customer->getId();
        $password = $this->Customer->getPassword();
        $crawler = $this->client->request(
            'PUT',
            $this->app->path('api_operation_put', array('table' => 'customer', 'id' => $customer_id)),
            array(),
            array(),
            $headers,
            json_encode($arrayEntity)
        );

        $this->expected = 204;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();

        $this->app['orm.em']->detach($this->Customer);
        $ResultCustomer = $this->app['eccube.repository.customer']->find($customer_id);

        $this->expected = $password;
        $this->actual = $ResultCustomer->getPassword();
        $this->verify('パスワードは変更されない');

        $this->expected = 'test@example.com';
        $this->actual = $ResultCustomer->getEmail();
        $this->verify('メールアドレスは変更される');
    }

    /**
     * 他人のパスワード, メールアドレスを変更しようとする
     */
    public function testUpdateOtherCustomerWithIlleguler()
    {
        $arrayEntity = array(
            'email' => 'test@example.com',
            'password' => 'password'
        );

        $access_token = $this->doAuthencation($this->Customer, 'customer_read customer_write');
        $headers = array(
            'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
            'CONTENT_TYPE' => 'application/json'
        );

        $OtherCustomer = $this->createCustomer();
        $customer_id = $OtherCustomer->getId();
        $password = $OtherCustomer->getPassword();
        $crawler = $this->client->request(
            'PUT',
            $this->app->path('api_operation_put', array('table' => 'customer', 'id' => $customer_id)),
            array(),
            array(),
            $headers,
            json_encode($arrayEntity)
        );

        $this->expected = 403;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();

        $this->app['orm.em']->detach($this->Customer);
        $ResultCustomer = $this->app['eccube.repository.customer']->find($customer_id);

        $this->expected = 'password';
        $this->actual = $ResultCustomer->getPassword();
        $this->assertNotEquals($this->expected, $this->actual, 'パスワードは変更されない');

        $this->expected = 'test@example.com';
        $this->actual = $ResultCustomer->getEmail();
        $this->assertNotEquals($this->expected, $this->actual, 'メールアドレスは変更されない');
    }

    /**
     * 会員住所IDを変更しようとする
     */
    public function testUpdateCustomerAddressWithId()
    {
        $CustomerAddress = $this->app['eccube.repository.customer_address']->findOneBy(array('Customer' => $this->Customer));
        $arrayEntity = array(
            'id' => 999999,
            'zip01' => 999,
            'zip02' => 9999
        );

        $access_token = $this->doAuthencation($this->Customer, 'customer_address_read customer_address_write');
        $headers = array(
            'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
            'CONTENT_TYPE' => 'application/json'
        );

        $id = $CustomerAddress->getId();
        $crawler = $this->client->request(
            'PUT',
            $this->app->path('api_operation_put', array('table' => 'customer_address', 'id' => $id)),
            array(),
            array(),
            $headers,
            json_encode($arrayEntity)
        );

        $this->expected = 204;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();

        $this->app['orm.em']->detach($CustomerAddress);
        $Result = $this->app['eccube.repository.customer_address']->find($id);

        $this->assertNotNull($Result, 'IDの変更は無視される');

        $this->expected = 999;
        $this->actual = $Result->getZip01();
        $this->verify();

        $this->expected = 9999;
        $this->actual = $Result->getZip02();
        $this->verify();
    }

    /**
     * 他人の受注明細を取得しようとする
     */
    public function testFindOtherCustomer()
    {
        $OtherOrder = $this->createOrder($this->createCustomer());
        $OrderDetails = $OtherOrder->getOrderDetails();

        $access_token = $this->doAuthencation($this->Customer, 'order_read');
        $headers = array(
            'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
            'CONTENT_TYPE' => 'application/json'
        );

        $crawler = $this->client->request(
            'GET',
            $this->app->path('api_operation_find', array('table' => 'order_detail', 'id' => $OrderDetails[0]->getId())),
            array(),
            array(),
            $headers
        );

        $this->expected = 403;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    /**
     * 他人の住所を変更しようとする
     */
    public function testUpdateOtherCustomerAddress()
    {
        $OtherCustomer = $this->createCustomer();
        $CustomerAddress = $this->app['eccube.repository.customer_address']->findOneBy(array('Customer' => $OtherCustomer));

        $access_token = $this->doAuthencation($this->Customer, 'customer_address_read customer_address_write');
        $headers = array(
            'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
            'CONTENT_TYPE' => 'application/json'
        );

        $arrayEntity = array(
            'id' => 999999,
            'zip01' => 999,
            'zip02' => 9999
        );

        $crawler = $this->client->request(
            'PUT',
            $this->app->path('api_operation_put', array('table' => 'customer_address', 'id' => $CustomerAddress->getId())),
            array(),
            array(),
            $headers,
            json_encode($arrayEntity)
        );

        $this->expected = 403;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    /**
     * 他人の住所を削除しようとする
     */
    public function testDeleteOtherCustomerAddress()
    {
        $OtherCustomer = $this->createCustomer();
        $CustomerAddress = $this->app['eccube.repository.customer_address']->findOneBy(array('Customer' => $OtherCustomer));

        $access_token = $this->doAuthencation($this->Customer, 'customer_address_read customer_address_write');
        $headers = array(
            'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
            'CONTENT_TYPE' => 'application/json'
        );

        $crawler = $this->client->request(
            'DELETE',
            $this->app->path('api_operation_delete', array('table' => 'customer_address', 'id' => $CustomerAddress->getId())),
            array(),
            array(),
            $headers
        );

        $this->expected = 403;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    /**
     * 認可されていない操作をする
     */
    public function testNotAuthencation()
    {
        $Product = $this->createProduct();
        // product_read を許可しない
        $access_token = $this->doAuthencation($this->Member, 'product_write');
        $headers = array(
            'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
            'CONTENT_TYPE' => 'application/json'
        );

        $crawler = $this->client->request(
            'GET',
            $this->app->path('api_operation_find', array('table' => 'product', 'id' => $Product->getId())),
            array(),
            array(),
            $headers
        );

        $this->expected = 403;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();

        $Errors = json_decode($this->client->getResponse()->getContent(), true);
        $this->expected = 'insufficient_scope';
        $this->actual = $Errors['error'];
        $this->verify();

        $this->expected = 'The request requires higher privileges than provided by the access token';
        $this->actual = $Errors['error_description'];
        $this->verify();
    }

    /**
     * 期限切れのアクセストークンで操作をする
     */
    public function testAuthencationExpired()
    {
        $Product = $this->createProduct();
        $token = $this->doAuthencation($this->Member, 'product_read');
        $AccessToken = $this->app['eccube.repository.oauth2.access_token']->findOneBy(array('token' => $token));
        $AccessToken->setExpires(new \DateTime('-1 seconds'));
        $this->app['orm.em']->flush($AccessToken);

        $headers = array(
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'CONTENT_TYPE' => 'application/json'
        );

        $crawler = $this->client->request(
            'GET',
            $this->app->path('api_operation_find', array('table' => 'product', 'id' => $Product->getId())),
            array(),
            array(),
            $headers
        );

        $this->expected = 401;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();

        $Errors = json_decode($this->client->getResponse()->getContent(), true);
        $this->expected = 'expired_token';
        $this->actual = $Errors['error'];
        $this->verify();

        $this->expected = 'The access token provided has expired';
        $this->actual = $Errors['error_description'];
        $this->verify();
    }

    /**
     * 期限切れのアクセストークンで public access 可能なテーブルを取得する
     */
    public function testNotAuthencationWithPublic()
    {
        $Product = $this->createProduct();
        $token = $this->doAuthencation($this->Member, 'product_read');
        $AccessToken = $this->app['eccube.repository.oauth2.access_token']->findOneBy(array('token' => $token));
        $AccessToken->setExpires(new \DateTime('-1 seconds'));
        $this->app['orm.em']->flush($AccessToken);

        $headers = array(
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'CONTENT_TYPE' => 'application/json'
        );

        $crawler = $this->client->request(
            'GET',
            $this->app->path('api_operation_find', array('table' => 'product', 'id' => $Product->getId())),
            array(),
            array(),
            $headers
        );

        $this->expected = 401;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();

        $Errors = json_decode($this->client->getResponse()->getContent(), true);
        $this->expected = 'expired_token';
        $this->actual = $Errors['error'];
        $this->verify();

        $this->expected = 'The access token provided has expired';
        $this->actual = $Errors['error_description'];
        $this->verify();
    }

    /**
     * Bearer 以外のトークンを使用する
     */
    public function testNotBearer()
    {
        $Product = $this->createProduct();
        $access_token = $this->doAuthencation($this->Member, 'db_read');
        $headers = array(
            'HTTP_AUTHORIZATION' => 'Basic '.$access_token, // Bearer 以外
            'CONTENT_TYPE' => 'application/json'
        );

        $crawler = $this->client->request(
            'GET',
            $this->app->path('api_operation_find', array('table' => 'db', 'id' => 1)),
            array(),
            array(),
            $headers
        );

        $this->expected = 400;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();

        $Errors = json_decode($this->client->getResponse()->getContent(), true);
        $this->expected = 'invalid_request';
        $this->actual = $Errors['error'];
        $this->verify();

        $this->expected = 'Malformed auth header';
        $this->actual = $Errors['error_description'];
        $this->verify();
    }

    /**
     * 認可要求をし, アクセストークンを発行します.
     *
     * @param UserInterface
     */
    protected function doAuthencation(UserInterface $User, $scope_granted = 'openid offline_access')
    {
        $client_id = sha1(openssl_random_pseudo_bytes(100));
        $client = $this->loginTo($User);
        $this->UserInfo = $this->createUserInfo($User);
        $this->OAuth2Client = $this->createApiClient(
            $User,
            'test-client-name',
            $client_id,
            'test-client-secret',
            'http://example.com/redirect_uri'
        );

        foreach (explode(' ', $scope_granted) as $scope) {
            $this->addClientScope($this->OAuth2Client, $scope);
        }

        $this->state = sha1(openssl_random_pseudo_bytes(100));
        $this->nonce = sha1(openssl_random_pseudo_bytes(100));
        $route = 'oauth2_server_mypage_authorize';
        if ($User instanceof \Eccube\Entity\Member) {
            $route = 'oauth2_server_admin_authorize';
        }

        $path = $this->app->path($route);
        $params = array(
                    'client_id' => $this->OAuth2Client->getClientIdentifier(),
                    'redirect_uri' => $this->OAuth2Client->getRedirectUri(),
                    'response_type' => 'token',
                    'state' => $this->state,
                    'nonce' => $this->nonce,
                    'scope' => $scope_granted,
        );

        // GET でリクエストし, 認可画面を表示
        $crawler = $client->request('GET', $path, $params);

        // 認可要求
        $params['authorized'] = 1;
        $params['_token'] = 'dummy';
        // ここで nonce をクエリストリングに含めなくてはならない
        $crawler = $client->request('POST', $path.'?nonce='.$this->nonce, $params);
        if ($client->getResponse()->getStatusCode() !== 302) {
            // アクセストークンが取得できない場合はステータスコードを返す
            return $client->getResponse()->getStatusCode();
        }
        $location = $client->getResponse()->headers->get('location');
        $Url = parse_url($location);
        $Fragments = array();
        foreach (explode('&', $Url['fragment']) as $fragment) {
            $params = explode('=', $fragment);
            $Fragments[$params[0]] = urldecode($params[1]);
        }
        $access_token = $Fragments['access_token'];
        return $access_token;
    }

    protected function createEntities()
    {
        $this->Product = $this->createProduct();
        $this->Order = $this->createOrder($this->Customer);
        $faker = $this->getFaker();

        $this->MailHistories = array();
        $this->MailTemplate = new MailTemplate();
        $this->MailTemplate
            ->setName($faker->word)
            ->setHeader($faker->word)
            ->setFooter($faker->word)
            ->setSubject($faker->word)
            ->setCreator($this->Member)
            ->setDelFlg(Constant::DISABLED);
        $this->app['orm.em']->persist($this->MailTemplate);
        $this->app['orm.em']->flush($this->MailTemplate);
        for ($i = 0; $i < 3; $i++) {
            $this->MailHistories[$i] = new MailHistory();
            $this->MailHistories[$i]
                ->setMailTemplate($this->MailTemplate)
                ->setOrder($this->Order)
                ->setSendDate(new \DateTime())
                ->setMailBody($faker->realText())
                ->setCreator($this->Member)
                ->setSubject('subject-'.$i);

            $this->app['orm.em']->persist($this->MailHistories[$i]);
            $this->app['orm.em']->flush($this->MailHistories[$i]);
        }

        $this->CustomerFavoriteProduct = new CustomerFavoriteProduct();
        $this->CustomerFavoriteProduct->setCustomer($this->Customer);
        $this->CustomerFavoriteProduct->setProduct($this->Product);
        $this->CustomerFavoriteProduct->setDelFlg(0);
        $this->app['orm.em']->persist($this->CustomerFavoriteProduct);
        $this->app['orm.em']->flush($this->CustomerFavoriteProduct);

        $Tag = $this->app['eccube.repository.master.tag']->find(1);
        $this->ProductTag = new ProductTag();
        $this->ProductTag->setProduct($this->Product);
        $this->ProductTag->setCreator($this->Member);
        $this->ProductTag->setTag($Tag);
        $this->ProductTag->setCreateDate(new \Datetime());
        $this->app['orm.em']->persist($this->ProductTag);
        $this->app['orm.em']->flush($this->ProductTag);

        $Authority = $this->app['eccube.repository.master.authority']->find(1);
        $this->AuthorityRole = new AuthorityRole();
        $this->AuthorityRole->setDenyUrl($faker->url);
        $this->AuthorityRole->setCreateDate(new \Datetime());
        $this->AuthorityRole->setUpdateDate(new \Datetime());
        $this->AuthorityRole->setCreator($this->Member);
        $this->AuthorityRole->setAuthority($Authority);
        $this->app['orm.em']->persist($this->AuthorityRole);
        $this->app['orm.em']->flush($this->AuthorityRole);

        // XXX 未使用
        $this->Zip = new \Eccube\Entity\Master\Zip();
        $this->Zip->setId(1);
        $this->Zip->setZipcode($faker->postCode);
        $this->Zip->setState($faker->prefecture);
        $this->Zip->setCity($faker->city);
        $this->Zip->setTown($faker->streetAddress);
        $this->app['orm.em']->persist($this->Zip);
        $this->app['orm.em']->flush($this->Zip);
    }

    protected function createProperties($metadata)
    {
        $faker = $this->getFaker();
        $properties = array();
        foreach ($metadata->fieldMappings as $field => $mapping) {
            // id 列は除外
            if (array_key_exists('id', $mapping) === true
                && $mapping['id'] === true) {
                continue;
            }
            // 更新不可なフィールドは除外
            switch ($mapping['fieldName']) {
                case 'create_date':
                case 'update_date':
                case 'del_flg':
                    continue 2;
                default:
            }
            switch ($mapping['type']) {
                case 'text':
                case 'string':
                    $properties[$mapping['fieldName']] = $faker->word;
                    break;
                case 'integer':
                    $properties[$mapping['fieldName']] = $faker->numberBetween(1000, 9000);
                    break;
                case 'decimal':
                    if (array_key_exists('scale', $mapping) && $mapping['scale'] === 0) {
                        $properties[$mapping['fieldName']] = $faker->numberBetween(1000, 9000);
                    } else {
                        $properties[$mapping['fieldName']] = $faker->randomFloat(2, 1, 100);
                    }
                    break;
                case 'smallint':
                    $properties[$mapping['fieldName']] = 0;
                    break;
                case 'datetime':
                case 'datetimetz':
                    $properties[$mapping['fieldName']] = $faker->dateTimeThisYear();
                    break;
                default:
            }
        }

        return $properties;
    }
}
