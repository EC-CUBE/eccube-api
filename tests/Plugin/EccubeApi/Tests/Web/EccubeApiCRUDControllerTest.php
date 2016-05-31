<?php

namespace Plugin\EccubeApi\Tests\Web;

use Eccube\Common\Constant;
use Eccube\Entity\MailHistory;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\CustomerFavoriteProduct;
use Eccube\Entity\ProductTag;
use Eccube\Entity\AuthorityRole;
use Eccube\Tests\Web\AbstractWebTestCase;

class EccubeApiCRUDControllerTest extends AbstractWebTestCase
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

    public function setUp()
    {
        parent::setUp();
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
        $this->app['orm.em']->flush();
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
            $this->app['orm.em']->flush();
        }

        $this->CustomerFavoriteProduct = new CustomerFavoriteProduct();
        $this->CustomerFavoriteProduct->setCustomer($this->Customer);
        $this->CustomerFavoriteProduct->setProduct($this->Product);
        $this->CustomerFavoriteProduct->setDelFlg(0);
        $this->app['orm.em']->persist($this->CustomerFavoriteProduct);
        $this->app['orm.em']->flush();

        $Tag = $this->app['eccube.repository.master.tag']->find(1);
        $this->ProductTag = new ProductTag();
        $this->ProductTag->setProduct($this->Product);
        $this->ProductTag->setCreator($this->Member);
        $this->ProductTag->setTag($Tag);
        $this->ProductTag->setCreateDate(new \Datetime());
        $this->app['orm.em']->persist($this->ProductTag);
        $this->app['orm.em']->flush();

        // XXX 未使用
        $this->Zip = new \Eccube\Entity\Master\Zip();
        $this->Zip->setId(1);
        $this->Zip->setZipcode($faker->postCode);
        $this->Zip->setState($faker->prefecture);
        $this->Zip->setCity($faker->city);
        $this->Zip->setTown($faker->streetAddress);
        $this->app['orm.em']->persist($this->Zip);
        $this->app['orm.em']->flush();

        $Authority = $this->app['eccube.repository.master.authority']->find(1);
        $this->AuthorityRole = new AuthorityRole();
        $this->AuthorityRole->setDenyUrl($faker->url);
        $this->AuthorityRole->setCreateDate(new \Datetime());
        $this->AuthorityRole->setUpdateDate(new \Datetime());
        $this->AuthorityRole->setCreator($this->Member);
        $this->AuthorityRole->setAuthority($Authority);
        $this->app['orm.em']->persist($this->AuthorityRole);
        $this->app['orm.em']->flush();
    }

    public function testFindAll()
    {
        $client = $this->client;
        $app = $this->app;
        $this->verifyFind(function ($table_name, $Entity) use ($app, $client) {
            $crawler = $client->request(
                'GET',
                $app->path('api_operation_findall', array('table' => $table_name)));
            return json_decode($client->getResponse()->getContent(), true);
        });
    }

    public function testFindOnce()
    {
        $client = $this->client;
        $app = $this->app;
        $this->verifyFind(function ($table_name, $Entity) use ($app, $client) {
            // XXX 複合キーのテーブルは除外
            if ($table_name == 'block_position'
                || $table_name == 'payment_option'
                || $table_name == 'product_category'
                || $table_name == 'category_total_count'
                || $table_name == 'category_count'
            ) {
                return array($table_name => array());
            }

            $crawler = $client->request(
                'GET',
                $app->path('api_operation_find', array('table' => $table_name, 'id' => $Entity->getId())));
            $content = json_decode($client->getResponse()->getContent(), true);
            $Result = array($table_name => array($content[$table_name]));
            return $Result;
        });
    }

    protected function verifyFind($callback)
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

            // Entity のデータチェックのため、1件だけ取得する
            $Entity = $this->app['orm.em']->getRepository($className)->findOneBy(array());
            $ApiResult = call_user_func($callback, $table_name, $Entity);

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
                    continue;
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
}
