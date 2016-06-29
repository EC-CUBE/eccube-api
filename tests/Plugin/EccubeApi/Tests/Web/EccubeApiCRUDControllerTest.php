<?php

/*
 * This file is part of the EccubeApi
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
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

class EccubeApiCRUDControllerTest extends AbstractEccubeApiWebTestCase
{

    protected $Customer;
    protected $Product;
    protected $Order;
    protected $MailTemplate;
    protected $MailHistories;
    protected $CustomerFavoriteProduct;
    protected $ProductTag;
    protected $AuthorityRole;
    protected $tables;
    protected $AccessToken;
    protected $UserInfo;
    protected $OAuth2Client;

    public function setUp()
    {
        parent::setUp();

        $this->createEntities();

        // OAuth2.0 認証処理
        $client = $this->loginTo($this->Member);
        $this->UserInfo = $this->createUserInfo($this->Member);
        $this->OAuth2Client = $this->createApiClient(
            $this->Member,
            'test-client-name',
            'test-client-id',
            'test-client-secret',
            'http://example.com/redirect_uri'
        );

    }

    public function testFindAll()
    {
        $client = $this->client;
        $app = $this->app;
        $this->verifyFind(function ($table_name, $Entity, $access_token) use ($app, $client) {
            $crawler = $client->request(
                'GET',
                $app->path('api_operation_findall', array('table' => $table_name)),
                array(),
                array(),
                array(
                    'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
                    'CONTENT_TYPE' => 'application/json',
                )
            );

            return json_decode($client->getResponse()->getContent(), true);
        });
    }

    public function testFindOnce()
    {
        $client = $this->client;
        $app = $this->app;
        $this->verifyFind(function ($table_name, $Entity, $access_token) use ($app, $client) {

            // XXX 複合キーのテーブルは除外
            switch ($table_name) {
                case 'block_position':
                case 'payment_option':
                case 'product_category':
                case 'category_total_count':
                case 'category_count':
                    return array($table_name => array());
                    break;
                default:
            }

            $crawler = $client->request(
                'GET',
                $app->path('api_operation_find', array('table' => $table_name, 'id' => $Entity->getId())),
                array(),
                array(),
                array(
                    'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
                    'CONTENT_TYPE' => 'application/json',
                )

            );
            $content = json_decode($client->getResponse()->getContent(), true);
            $Result = array($table_name => array($content[$table_name]));

            return $Result;
        });
    }

    public function testFindProductCategory()
    {
        $client = $this->client;
        $app = $this->app;

        $this->verifyFind(function ($table_name, $Entity, $access_token) use ($app, $client) {

            $crawler = $client->request(
                'GET',
                $app->path('api_operation_find_product_category',
                           array(
                               'product_id' => $Entity->getProductId(),
                               'category_id' => $Entity->getCategoryId()
                           )),
                array(),
                array(),
                array(
                    'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
                    'CONTENT_TYPE' => 'application/json',
                )

            );
            $content = json_decode($client->getResponse()->getContent(), true);
            $Result = array($table_name => array($content[$table_name]));
            return $Result;
        }, 'product_category');
    }

    public function testFindPaymentOption()
    {
        $client = $this->client;
        $app = $this->app;

        $this->verifyFind(function ($table_name, $Entity, $access_token) use ($app, $client) {

            $crawler = $client->request(
                'GET',
                $app->path('api_operation_find_payment_option',
                           array(
                               'delivery_id' => $Entity->getDeliveryId(),
                               'payment_id' => $Entity->getPaymentId()
                           )),
                array(),
                array(),
                array(
                    'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
                    'CONTENT_TYPE' => 'application/json',
                )

            );
            $content = json_decode($client->getResponse()->getContent(), true);
            $Result = array($table_name => array($content[$table_name]));
            return $Result;
        }, 'payment_option');
    }

    public function testFindBlockPosition()
    {
        $client = $this->client;
        $app = $this->app;

        $this->verifyFind(function ($table_name, $Entity, $access_token) use ($app, $client) {

            $crawler = $client->request(
                'GET',
                $app->path('api_operation_find_block_position',
                           array(
                               'page_id' => $Entity->getPageId(),
                               'target_id' => $Entity->getTargetId(),
                               'block_id' => $Entity->getBlockId()
                           )),
                array(),
                array(),
                array(
                    'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
                    'CONTENT_TYPE' => 'application/json',
                )

            );
            $content = json_decode($client->getResponse()->getContent(), true);
            $Result = array($table_name => array($content[$table_name]));
            return $Result;
        }, 'block_position');
    }

    public function testCreate()
    {
        $faker = $this->getFaker();
        $client = $this->client;
        $metadatas = $this->app['orm.em']->getMetadataFactory()->getAllMetadata();
        foreach ($metadatas as $metadata) {
            $className = $metadata->getName();
            if (strpos($metadata->table['name'], 'dtb_') === false
                && strpos($metadata->table['name'], 'mtb_') === false) {
                // dtb_ or mtb_ 以外のテーブルは除外
                continue;
            }

            $table_name = EntityUtil::shortTableName($metadata->table['name']);
            // XXX 複合キーのテーブルは除外
            switch ($table_name) {
                case 'block_position':
                case 'payment_option':
                case 'product_category':
                case 'category_total_count':
                case 'category_count':
                    continue 2;
                default:
            }

            // FIXME https://github.com/EC-CUBE/ec-cube/pull/1576
            switch ($table_name) {
                case 'plugin':
                case 'plugin_event_handler':
                    continue 2;
                default:
            }

            // FIXME https://github.com/EC-CUBE/ec-cube/issues/1580
            switch ($table_name) {
                case 'help':
                    continue 2;
                default:
            }

            // 未使用のため除外
            switch ($table_name) {
                case 'country':
                case 'zip':
                case 'authority':
                case 'category_count':
                case 'category_total_count':
                    continue 2;
                default:
            }


            $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, $table_name.'_read '.$table_name.'_write');

            $Entity = $this->app['orm.em']->getRepository($className)->findOneBy(array());
            // 各テーブル特有の処理
            switch ($table_name) {
                case 'customer':
                    $Entity->setSecretKey($this->app['eccube.repository.customer']->getUniqueSecretKey($this->app));
                    break;
                case 'block':
                    $Entity->setFileName($faker->word);
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

            $url = $this->app->url('api_operation_create', array('table' => $table_name));
            $crawler = $this->client->request(
                'POST',
                $url,
                array(),
                array(),
                array(
                    'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                    'CONTENT_TYPE' => 'application/json',
                ),
                json_encode($arrayEntity)
            );

            $this->expected = 201;
            $this->actual = $this->client->getResponse()->getStatusCode();
            $this->verify($this->client->getResponse()->getContent());
            $this->assertTrue($this->client->getResponse()->isSuccessful());

            $this->assertTrue(preg_match('/'.preg_quote($url, '/').'\/([0-9]+)/',
                                         $client->getResponse()->headers->get('Location'), $matched) > 0,
                              'Location ヘッダが一致するか？');

            $this->app['orm.em']->detach($Entity); // キャッシュを取得しないように detach する
            $Created = $this->app['orm.em']->getRepository($className)->find($matched[1]);
            $this->verifyProperties($properties, $Created);
        }
    }

    public function testCreateProductCategory()
    {
        $faker = $this->getFaker();
        $client = $this->client;
        $metadata = EntityUtil::findMetadata($this->app, 'product_category');
        $className = $metadata->getName();

        $Product = $this->createProduct();
        $Category = new \Eccube\Entity\Category();
        $Category->setName($faker->word);
        $Category->setRank(999);
        $Category->setLevel(1);
        $Category->setCreator($this->Member);
        $Category->setDelFlg(0);
        $Category->setCreateDate(new \DateTime());
        $Category->setUpdateDate(new \DateTime());
        $this->app['orm.em']->persist($Category);
        $this->app['orm.em']->flush($Category);

        $Entity = new \Eccube\Entity\ProductCategory();
        $Entity->setProduct($Product);
        $Entity->setProductId($Product->getId());
        $Entity->setCategory($Category);
        $Entity->setCategoryId($Category->getId());
        $Entity->setRank(999);

        $arrayEntity = EntityUtil::entityToArray($this->app, $Entity);

        $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, 'product_category_read product_category_write');
        $url = $this->app->url('api_operation_create_product_category');
        $crawler = $this->client->request(
            'POST',
            $url,
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                'CONTENT_TYPE' => 'application/json',
            ),
            json_encode($arrayEntity)
        );

        $this->expected = 201;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify($this->client->getResponse()->getContent());
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->expected = $url.'/product_id/'.$Product->getId().'/category_id/'.$Category->getId();
        $this->actual = $client->getResponse()->headers->get('Location');
        $this->verify('Location ヘッダが一致するか？');

        $this->app['orm.em']->detach($Entity); // キャッシュを取得しないように detach する
        $Created = $this->app['orm.em']->getRepository($className)->findOneBy(
            array(
                'product_id' => $Product->getId(),
                'category_id' => $Category->getId()
            )
        );
        $this->expected = 999;
        $this->actual = $Created->getRank();
        $this->verify();
    }

    public function testCreatePaymentOption()
    {
        $faker = $this->getFaker();
        $client = $this->client;
        $metadata = EntityUtil::findMetadata($this->app, 'payment_option');
        $className = $metadata->getName();

        $Delivery = $this->app['eccube.repository.delivery']->find(1);
        $Payment = new \Eccube\Entity\Payment();
        $Payment
            ->setMethod($faker->word)
            ->setCharge($faker->numberBetween(1, 99999))
            ->setRuleMin($faker->numberBetween(1, 99999))
            ->setRuleMax($faker->numberBetween(1, 99999))
            ->setCreator($this->Member)
            ->setDelFlg(Constant::DISABLED);
        $this->app['orm.em']->persist($Payment);
        $this->app['orm.em']->flush($Payment);

        $Entity = new \Eccube\Entity\PaymentOption();
        $Entity->setPayment($Payment);
        $Entity->setPaymentId($Payment->getId());
        $Entity->setDelivery($Delivery);
        $Entity->setDeliveryId($Delivery->getId());

        $arrayEntity = EntityUtil::entityToArray($this->app, $Entity);
        $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, 'payment_option_read payment_option_write');

        $url = $this->app->url('api_operation_create_payment_option');
        $crawler = $this->client->request(
            'POST',
            $url,
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                'CONTENT_TYPE' => 'application/json',
            ),
            json_encode($arrayEntity)
        );

        $this->expected = 201;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify($this->client->getResponse()->getContent());
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->expected = $url.'/delivery_id/'.$Delivery->getId().'/payment_id/'.$Payment->getId();
        $this->actual = $client->getResponse()->headers->get('Location');
        $this->verify('Location ヘッダが一致するか？');

        $this->app['orm.em']->detach($Entity); // キャッシュを取得しないように detach する
        $Created = $this->app['orm.em']->getRepository($className)->findOneBy(
            array(
                'delivery_id' => $Delivery->getId(),
                'payment_id' => $Payment->getId()
            )
        );
        $this->expected = $arrayEntity;
        $this->actual = EntityUtil::entityToArray($this->app, $Created);
        $this->verify();
    }

    public function testCreateBlockPosition()
    {
        $faker = $this->getFaker();
        $client = $this->client;
        $metadata = EntityUtil::findMetadata($this->app, 'block_position');
        $className = $metadata->getName();

        $Page = $this->app['eccube.repository.page_layout']->find(1);
        $Block = $this->app['eccube.repository.block']->find(1);
        $target_id = 999;

        $Entity = new \Eccube\Entity\BlockPosition();
        $Entity->setPageId($Page->getId());
        $Entity->setPageLayout($Page);
        $Entity->setBlockId($Block->getId());
        $Entity->setBlock($Block);
        $Entity->setTargetId($target_id);
        $Entity->setAnywhere(0);
        $Entity->setBlockRow(100);

        $arrayEntity = EntityUtil::entityToArray($this->app, $Entity);
        $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, 'block_position_read block_position_write');
        $url = $this->app->url('api_operation_create_block_position');
        $crawler = $this->client->request(
            'POST',
            $url,
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                'CONTENT_TYPE' => 'application/json',
            ),
            json_encode($arrayEntity)
        );

        $this->expected = 201;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify($this->client->getResponse()->getContent());
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->expected = $url.'/page_id/'.$Page->getId().'/target_id/'.$target_id.'/block_id/'.$Block->getId();
        $this->actual = $client->getResponse()->headers->get('Location');
        $this->verify('Location ヘッダが一致するか？');

        $this->app['orm.em']->detach($Entity); // キャッシュを取得しないように detach する
        $Created = $this->app['orm.em']->getRepository($className)->findOneBy(
            array(
                'page_id' => $Page->getId(),
                'target_id' => $target_id,
                'block_id' => $Block->getId()
            )
        );
        $this->expected = 100;
        $this->actual = $Created->getBlockRow();
        $this->verify();
    }

    public function testUpdateProductCategory()
    {
        $faker = $this->getFaker();
        $client = $this->client;
        $metadata = EntityUtil::findMetadata($this->app, 'product_category');
        $className = $metadata->getName();

        $Product = $this->createProduct();
        $Category = new \Eccube\Entity\Category();
        $Category->setName($faker->word);
        $Category->setRank(999);
        $Category->setLevel(1);
        $Category->setCreator($this->Member);
        $Category->setDelFlg(0);
        $Category->setCreateDate(new \DateTime());
        $Category->setUpdateDate(new \DateTime());
        $this->app['orm.em']->persist($Category);
        $this->app['orm.em']->flush($Category);

        $Entity = new \Eccube\Entity\ProductCategory();
        $Entity->setProduct($Product);
        $Entity->setProductId($Product->getId());
        $Entity->setCategory($Category);
        $Entity->setCategoryId($Category->getId());
        $Entity->setRank(999);
        $this->app['orm.em']->persist($Entity);
        $this->app['orm.em']->flush($Entity);

        $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, 'product_category_read product_category_write');
        $arrayEntity = EntityUtil::entityToArray($this->app, $Entity);
        $arrayEntity['rank'] = 888;

        $url = $this->app->url('api_operation_update_product_category',
                               array(
                                   'product_id' => $Product->getId(),
                                   'category_id' => $Category->getId()
                               )
        );
        $crawler = $this->client->request(
            'PUT',
            $url,
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                'CONTENT_TYPE' => 'application/json',
            ),
            json_encode($arrayEntity)
        );

        $this->expected = 204;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify($this->client->getResponse()->getContent());
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->app['orm.em']->detach($Entity); // キャッシュを取得しないように detach する
        $Created = $this->app['orm.em']->getRepository($className)->findOneBy(
            array(
                'product_id' => $Product->getId(),
                'category_id' => $Category->getId()
            )
        );
        $this->expected = 888;
        $this->actual = $Created->getRank();
        $this->verify();
    }

    public function testUpdateBlockPosition()
    {
        $faker = $this->getFaker();
        $client = $this->client;
        $metadata = EntityUtil::findMetadata($this->app, 'block_position');
        $className = $metadata->getName();

        $Page = $this->app['eccube.repository.page_layout']->find(1);
        $Block = $this->app['eccube.repository.block']->find(1);
        $target_id = 999;

        $Entity = new \Eccube\Entity\BlockPosition();
        $Entity->setPageId($Page->getId());
        $Entity->setPageLayout($Page);
        $Entity->setBlockId($Block->getId());
        $Entity->setBlock($Block);
        $Entity->setTargetId($target_id);
        $Entity->setAnywhere(0);
        $Entity->setBlockRow(100);
        $this->app['orm.em']->persist($Entity);
        $this->app['orm.em']->flush($Entity);

        $arrayEntity = EntityUtil::entityToArray($this->app, $Entity);
        $arrayEntity['block_row'] = 777;
        $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, 'block_position_read block_position_write');
        $url = $this->app->url('api_operation_update_block_position',
                               array(
                                   'page_id' => $Page->getId(),
                                   'target_id' => $target_id,
                                   'block_id' => $Block->getId()
                               )
        );
        $crawler = $this->client->request(
            'PUT',
            $url,
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                'CONTENT_TYPE' => 'application/json',
            ),
            json_encode($arrayEntity)
        );

        $this->expected = 204;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify($this->client->getResponse()->getContent());
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->app['orm.em']->detach($Entity); // キャッシュを取得しないように detach する
        $Created = $this->app['orm.em']->getRepository($className)->findOneBy(
            array(
                'page_id' => $Page->getId(),
                'target_id' => $target_id,
                'block_id' => $Block->getId()
            )
        );
        $this->expected = 777;
        $this->actual = $Created->getBlockRow();
        $this->verify();
    }

    public function testUpdate()
    {
        $faker = $this->getFaker();
        $client = $this->client;
        $metadatas = $this->app['orm.em']->getMetadataFactory()->getAllMetadata();
        foreach ($metadatas as $metadata) {
            $className = $metadata->getName();
            if (strpos($metadata->table['name'], 'dtb_') === false
                && strpos($metadata->table['name'], 'mtb_') === false) {
                // dtb_ or mtb_ 以外のテーブルは除外
                continue;
            }

            $table_name = EntityUtil::shortTableName($metadata->table['name']);
            // XXX 複合キーのテーブルは除外
            switch ($table_name) {
                case 'block_position':
                case 'payment_option':
                case 'product_category':
                case 'category_total_count':
                case 'category_count':
                    continue 2;
                default:
            }

            // FIXME https://github.com/EC-CUBE/ec-cube/pull/1576
            switch ($table_name) {
                case 'plugin':
                case 'plugin_event_handler':
                    continue 2;
                default:
            }

            // 未使用のため除外
            switch ($table_name) {
                case 'country':
                case 'zip':
                case 'authority':
                case 'category_count':
                case 'category_total_count':
                    continue 2;
                default:
            }
            $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, $table_name.'_read '.$table_name.'_write');
            $properties = $this->createProperties($metadata);
            $Entity = $this->app['orm.em']->getRepository($className)->findOneBy(array());
            $Entity->setPropertiesFromArray($properties);
            $id = $Entity->getId();

            $arrayEntity = EntityUtil::entityToArray($this->app, $Entity);
            // XXX 複合キーの対応
            $url = $this->app->url('api_operation_put', array('table' => $table_name, 'id' => $Entity->getId()));
            $crawler = $this->client->request(
                'PUT',
                $url,
                array(),
                array(),
                array(
                    'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                    'CONTENT_TYPE' => 'application/json',
                ),
                json_encode($arrayEntity)
            );

            $this->expected = 204;
            $this->actual = $this->client->getResponse()->getStatusCode();
            $this->verify($this->client->getResponse()->getContent());
            $this->assertTrue($this->client->getResponse()->isSuccessful());

            $this->app['orm.em']->detach($Entity); // キャッシュを取得しないように detach する
            $Updated = $this->app['orm.em']->getRepository($className)->find($id);
            $this->verifyProperties($properties, $Updated);
        }
    }

    public function testDelete()
    {
        $faker = $this->getFaker();
        $client = $this->client;
        $metadatas = $this->app['orm.em']->getMetadataFactory()->getAllMetadata();
        foreach ($metadatas as $metadata) {
            $className = $metadata->getName();
            if (strpos($metadata->table['name'], 'dtb_') === false
                && strpos($metadata->table['name'], 'mtb_') === false) {
                // dtb_ or mtb_ 以外のテーブルは除外
                continue;
            }

            $table_name = EntityUtil::shortTableName($metadata->table['name']);
            // XXX 複合キーのテーブルは除外
            switch ($table_name) {
                case 'block_position':
                case 'payment_option':
                case 'product_category':
                case 'category_total_count':
                case 'category_count':
                    continue 2;
                default:
            }

            // FIXME https://github.com/EC-CUBE/ec-cube/pull/1576
            switch ($table_name) {
                case 'plugin':
                case 'plugin_event_handler':
                    continue 2;
                default:
            }

            // 未使用のため除外
            switch ($table_name) {
                case 'country':
                case 'zip':
                case 'authority':
                case 'category_count':
                case 'category_total_count':
                    continue 2;
                default:
            }

            // 自分自身を削除しないよう除外
            switch ($table_name) {
                case 'member':
                    continue 2;
            }

            $Entity = $this->app['orm.em']->getRepository($className)->findOneBy(array());
            dump($className.' '.$table_name.' '.$metadata->table['name']);
            if ($table_name == 'order') {
                dump($metadata);
            }

            $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, $table_name.'_read '.$table_name.'_write');
            // XXX 複合キーの対応
            $url = $this->app->url('api_operation_delete', array('table' => $table_name, 'id' => $Entity->getId()));
            $crawler = $this->client->request(
                'DELETE',
                $url,
                array(),
                array(),
                array(
                    'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                    'CONTENT_TYPE' => 'application/json',
                )
            );

            if (!array_key_exists('del_flg', $metadata->fieldMappings)) {
                $this->expected = 405;
                $this->actual = $this->client->getResponse()->getStatusCode();
                $this->verify('DELETE method not allowed');
            } else {
                $this->expected = 204;
                $this->actual = $this->client->getResponse()->getStatusCode();
                $this->verify($this->client->getResponse()->getContent());
                $this->assertTrue($this->client->getResponse()->isSuccessful());

                $Entity2 = $this->app['orm.em']->getRepository($className)->find($Entity->getId());
                $this->expected = 1;
                $this->actual = $Entity2->getDelFlg();
                $this->verify();
            }
        }
    }

    public function testFindOnceWithNotFound()
    {
        $crawler = $this->client->request(
            'GET',
            $this->app->path('api_operation_find', array('table' => 'product', 'id' => 999999999)),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
            )
        );

        $this->expected = 404;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    public function testFindMultipleIdWithNotFound()
    {
        $crawler = $this->client->request(
            'GET',
            $this->app->path('api_operation_find', array('table' => 'product_category', 'id' => 999999999)),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
            )
        );

        $this->expected = 400;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    public function testFindOnceWithNoAuthorization()
    {
        $crawler = $this->client->request(
            'GET',
            $this->app->path('api_operation_find', array('table' => 'product_class', 'id' => 5)),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
            )
        );

        $this->expected = 200;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    public function testUpdateWithNotFound()
    {
        $Entity = $this->app['eccube.repository.product']->find(1);
        $arrayEntity = EntityUtil::entityToArray($this->app, $Entity);
        $url = $this->app->url('api_operation_put', array('table' => 'product', 'id' => 999999999));
        $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, 'product_read product_write');
        $crawler = $this->client->request(
            'PUT',
            $url,
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                'CONTENT_TYPE' => 'application/json',
            ),
            json_encode($arrayEntity)
        );

        $this->expected = 404;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    public function testUpdateMultipleIdWithNotFound()
    {
        $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, 'product_category_read product_category_write');
        $Entity = $this->app['orm.em']->getRepository('\\Eccube\\Entity\\ProductCategory')->findOneBy(array());
        $arrayEntity = EntityUtil::entityToArray($this->app, $Entity);
        $url = $this->app->url('api_operation_put', array('table' => 'product_category', 'id' => 999999999));
        $crawler = $this->client->request(
            'PUT',
            $url,
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                'CONTENT_TYPE' => 'application/json',
            ),
            json_encode($arrayEntity)
        );

        $this->expected = 400;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    public function testDeleteWithNotFound()
    {
        $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, 'product_read product_write');
        $Entity = $this->app['eccube.repository.product']->find(1);
        $arrayEntity = EntityUtil::entityToArray($this->app, $Entity);
        $url = $this->app->url('api_operation_delete', array('table' => 'product', 'id' => 999999999));
        $crawler = $this->client->request(
            'DELETE',
            $url,
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                'CONTENT_TYPE' => 'application/json',
            ),
            json_encode($arrayEntity)
        );

        $this->expected = 404;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    public function testDeleteMultipleWithNotFound()
    {
        $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, 'product_category_read product_category_write');
        $Entity = $this->app['eccube.repository.product']->find(1);
        $arrayEntity = EntityUtil::entityToArray($this->app, $Entity);
        $url = $this->app->url('api_operation_delete', array('table' => 'product_category', 'id' => 999999999));
        $crawler = $this->client->request(
            'DELETE',
            $url,
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                'CONTENT_TYPE' => 'application/json',
            ),
            json_encode($arrayEntity)
        );

        $this->expected = 405;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    public function testFindAllWithTableNotFound()
    {
        $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, 'product_read product_write');
        $crawler = $this->client->request(
            'GET',
            $this->app->path('api_operation_findall', array('table' => 'pro')),
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                'CONTENT_TYPE' => 'application/json',
            )
        );

        $this->expected = 404;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    public function testFindOnceWithTableNotFound()
    {
        $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, 'product_read product_write');
        $crawler = $this->client->request(
            'GET',
            $this->app->path('api_operation_find', array('table' => 'pro', 'id' => 1)),
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                'CONTENT_TYPE' => 'application/json',
            )
        );

        $this->expected = 404;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    public function testCreateWithTableNotFound()
    {
        $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, 'product_read product_write');
        $crawler = $this->client->request(
            'POST',
            $this->app->path('api_operation_create', array('table' => 'pro')),
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                'CONTENT_TYPE' => 'application/json',
            ),
            json_encode(array('aaa' => 5))
        );

        $this->expected = 404;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    public function testUpdateWithTableNotFound()
    {
        $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, 'product_read product_write');
        $crawler = $this->client->request(
            'PUT',
            $this->app->path('api_operation_put', array('table' => 'pro', 'id' => 1)),
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                'CONTENT_TYPE' => 'application/json',
            ),
            json_encode(array('aaa' => 5))
        );

        $this->expected = 404;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    public function testDeleteWithTableNotFound()
    {
        $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, 'product_read product_write');
        $crawler = $this->client->request(
            'DELETE',
            $this->app->path('api_operation_delete', array('table' => 'pro', 'id' => 1)),
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                'CONTENT_TYPE' => 'application/json',
            )
        );

        $this->expected = 404;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    /**
     * 存在しないフィールドを含めて更新する.
     */
    public function testUpdateWithUnknownField()
    {
        $Product = $this->createProduct();
        $product_id = $Product->getId();
        $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, 'product_read product_write');
        $crawler = $this->client->request(
            'PUT',
            $this->app->path('api_operation_put', array('table' => 'product', 'id' => $product_id)),
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                'CONTENT_TYPE' => 'application/json',
            ),
            json_encode(
                array(
                    'aaa' => 5,
                    'name' => 'aaaa',
                )
            )
        );

        $this->expected = 204;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();

        $Result = $this->app['eccube.repository.product']->find($product_id);
        $this->expected = 'aaaa';
        $this->actual = $Result->getName();
        $this->verify();
    }

    /**
     * SQLエラーを発生させる.
     */
    public function testUpdateWithException()
    {
        $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, 'db_read db_write');
        $crawler = $this->client->request(
            'PUT',
            $this->app->path('api_operation_put', array('table' => 'db', 'id' => 1)),
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                'CONTENT_TYPE' => 'application/json',
            ),
            json_encode(
                array(
                    'id' => 'aaaa',
                    'name' => 'aaaa',
                    'rank' => 'aaaa'
                )
            )
        );

        $this->expected = 400;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    /**
     * SQLエラーを発生させる.
     */
    public function testUpdateWithNotNullException()
    {
        $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, 'db_read db_write');
        $crawler = $this->client->request(
            'PUT',
            $this->app->path('api_operation_put', array('table' => 'db', 'id' => 1)),
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                'CONTENT_TYPE' => 'application/json',
            ),
            json_encode(
                array(
                    'name' => 'aaaa',
                    'rank' => null
                )
            )
        );

        $this->expected = 400;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    /**
     * SQLエラーを発生させる.
     */
    public function testUpdateWithEmptyException()
    {
        $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, 'db_read db_write');
        $crawler = $this->client->request(
            'PUT',
            $this->app->path('api_operation_put', array('table' => 'db', 'id' => 1)),
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$this->AccessToken['token'],
                'CONTENT_TYPE' => 'application/json',
            ),
            json_encode(
                array(
                    'name' => 'aaaa',
                    'rank' => ''
                )
            )
        );

        $this->expected = 400;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    protected function verifyFind($callback, $target_table_name = null)
    {
        $client = $this->client;
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
            if (!is_null($target_table_name) && $target_table_name != $table_name) {
                // $target_table_name が指定されていたら, それ以外は除外
                continue;
            }

            // 未使用のため除外
            switch ($table_name) {
                case 'country':
                case 'zip':
                case 'authority':
                case 'category_count':
                case 'category_total_count':
                    continue 2;
            }
            $this->AccessToken = $this->doAuthorized($this->UserInfo, $this->OAuth2Client, $table_name.'_read');
            // Entity のデータチェックのため、1件だけ取得する
            $Entity = $this->app['orm.em']->getRepository($className)->findOneBy(array());
            $ApiResult = call_user_func($callback, $table_name, $Entity, $this->AccessToken['token']);

            $this->expected = 200;
            $this->actual = $client->getResponse()->getStatusCode();
            $this->verify($client->getResponse()->getContent());

            $Lists = $ApiResult[$table_name];
            $this->assertTrue(is_array($Lists));
            $idFields = array();
            // IDのキー名を取得
            foreach ($metadata->fieldMappings as $field => $value) {
                if (array_key_exists('id', $value) && $value['id'] === true) {
                    $idFields[] = $value['fieldName'];
                }
            }

            foreach ($Lists as $Result) {
                if (count($idFields) < 2 && array_key_exists($idFields[0], $Result)) {
                    $Entity = $this->app['orm.em']->getRepository($className)->find($Result[$idFields[0]]);
                    $this->assertNotNull($Entity);
                } else {
                    // ここは複合キーの結果が入ってくる
                    switch ($table_name) {
                        case 'product_category':
                            $Entity = $this->app['orm.em']->getRepository($className)->findOneBy(
                                array(
                                    'product_id' => $Result['product_id'],
                                    'category_id' => $Result['category_id']
                                )
                            );
                            break;
                        default:
                            continue 2;
                    }
                    $this->assertNotNull($Entity);
                    // TODO 複合キーの場合の対応
                    // $Entity = $this->app['orm.em']->getRepository($className)->find($Result['id']);
                    // $this->assertNotNull($Entity);
                }

                foreach ($Result as $field => $value) {
                    $Reflect = new \ReflectionClass($Entity);
                    if ($Entity instanceof \Doctrine\ORM\Proxy\Proxy) {
                        // Proxy の場合は親クラスを取得
                        $Reflect = $Reflect->getParentClass();
                    }

                    // 値が配列の場合は、オブジェクトのIDか PersistentCollection の結果が格納されている
                    if (is_array($value)) {
                        foreach ($value as $key => $child) {
                            $this->assertRegExp('/(id$|[0-9]+)/', (string)$key, 'キーは id を含む文字列または数値');
                            if (is_array($child)) {
                                foreach ($child as $child_key => $child_value) {
                                    $this->assertRegExp('/(id$|[0-9]+)/', (string)$child_key, 'キーは id を含む文字列または数値');
                                    $this->assertTrue(is_numeric($child_value), 'IDの値は数値');
                                }
                            } else {
                                $this->assertTrue(is_numeric($child), 'IDの値は数値');
                            }
                        }
                        continue;
                    }

                    try {
                        $Property = $Reflect->getProperty($field);
                        $Property->setAccessible(true);
                        $this->expected = $Property->getValue($Entity);
                        if ($this->expected instanceof \DateTime) {
                            $this->expected = $this->expected->format(\Datetime::ATOM);
                        }
                        $this->actual = $Result[$field];
                        $this->verify($table_name.': '.$field.' '.print_r($Result, true));
                    } catch (\ReflectionException $e) {
                        $this->fail($Reflect->getName().' '.$e->getMessage());
                    }
                }
            }
        }
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

    protected function verifyProperties(array $expectedProperties, AbstractEntity $actualEntity)
    {
        $Reflect = new \ReflectionClass($actualEntity);
        if ($actualEntity instanceof \Doctrine\ORM\Proxy\Proxy) {
            // Proxy の場合は親クラスを取得
            $Reflect = $Reflect->getParentClass();
        }
        foreach ($expectedProperties as $field => $value) {
            $Property = $Reflect->getProperty($field);
            $Property->setAccessible(true);

            $this->expected = $value;
            $this->actual = $Property->getValue($actualEntity);
            // $this->app->log(($Reflect->getName().'::'.$field.' = '.print_r($value, true).' = '.print_r($this->actual, true)));
            $this->verify($Reflect->getName().'::'.$field);
        }
    }

    protected function createEntities()
    {
        $this->Customer = $this->createCustomer();
        $this->Product = $this->createProduct();
        $this->Order = $this->createOrder($this->Customer);
        $this->Member = $this->app['eccube.repository.member']->find(2);
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
}
