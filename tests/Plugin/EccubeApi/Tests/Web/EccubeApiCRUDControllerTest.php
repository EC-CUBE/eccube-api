<?php

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

    public function setUp()
    {
        parent::setUp();

        $this->createEntities();

        // OAuth2.0 認証処理
        $client = $this->loginTo($this->Member);
        $this->AccessToken = $this->doAuthorized($this->Member);
        // $_SERVER に入れておかないと認証されない
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer '.$this->AccessToken['token'];
    }

    public function testFindAll()
    {
        $client = $this->client;
        $AccessToken = $this->AccessToken;
        $app = $this->app;
        $this->verifyFind(function ($table_name, $Entity) use ($app, $client, $AccessToken) {
            $crawler = $client->request(
                'GET',
                $app->path('api_operation_findall', array('table' => $table_name)),
                array(),
                array(),
                array(
                    'HTTP_AUTHORIZATION' => 'Bearer '.$AccessToken['token'],
                    'CONTENT_TYPE' => 'application/json',
                )
            );

            return json_decode($client->getResponse()->getContent(), true);
        });
    }

    public function testFindOnce()
    {
        $client = $this->client;
        $AccessToken = $this->AccessToken;
        $app = $this->app;
        $this->verifyFind(function ($table_name, $Entity) use ($app, $client, $AccessToken) {

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
                    'HTTP_AUTHORIZATION' => 'Bearer '.$AccessToken['token'],
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
        $AccessToken = $this->AccessToken;
        $app = $this->app;

        $this->verifyFind(function ($table_name, $Entity) use ($app, $client, $AccessToken) {

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
                    'HTTP_AUTHORIZATION' => 'Bearer '.$AccessToken['token'],
                    'CONTENT_TYPE' => 'application/json',
                )

            );
            $content = json_decode($client->getResponse()->getContent(), true);
            $Result = array($table_name => array($content[$table_name]));
            return $Result;
        }, 'product_category');
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

            $Entity = $this->app['orm.em']->getRepository($className)->findOneBy(array());

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

            // Entity のデータチェックのため、1件だけ取得する
            $Entity = $this->app['orm.em']->getRepository($className)->findOneBy(array());
            $ApiResult = call_user_func($callback, $table_name, $Entity);

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
