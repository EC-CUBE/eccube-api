<?php

namespace Plugin\EccubeApi\Tests\Web;

use Eccube\Common\Constant;
use Eccube\Tests\Web\AbstractWebTestCase;

class EccubeApiCRUDControllerTest extends AbstractWebTestCase
{

    protected $Customer;
    protected $Product;
    protected $Order;
    protected $tables;

    public function setUp()
    {
        parent::setUp();
        $this->Customer = $this->createCustomer();
        $this->Product = $this->createProduct();
        $this->Order = $this->createOrder($this->Customer);
    }

    public function testFindAll()
    {
        $client = $this->client;
        $metadatas = $this->app['orm.em']->getMetadataFactory()->getAllMetadata();
        foreach ($metadatas as $metadata) {
            $className = $metadata->getName();
            $Reflect = new \ReflectionClass($className);
            if (!$Reflect->hasMethod('toArray')) {
                // TODO API 側でもチェックする
                continue;
            }
            if (strpos($metadata->table['name'], 'dtb_') === false
                && strpos($metadata->table['name'], 'mtb_') === false) {
                continue;
            }
            $table_name = str_replace(array('dtb_', 'mtb_'), '', $metadata->table['name']);
            // XXX 複合キーのテーブルは除外
            if ($table_name == 'block_position'
                || $table_name == 'payment_option'
                || $table_name == 'product_category') {
                continue;
            }

            $crawler = $client->request(
                'GET',
                $this->app->path('api_operation_findall', array('table' => $table_name)));
            $ApiResult = json_decode($this->client->getResponse()->getContent(), true);
            $Lists = $ApiResult[$table_name];
            $this->assertTrue(is_array($Lists));
            $idField = '';
            $idValue = '';
            foreach ($metadata->fieldMappings as $field => $value) {
                if (array_key_exists('id', $value) && $value['id'] === true) {
                    $idField = $value['columnName'];
                    $idValue = $value;
                    break;
                }
            }

            foreach ($Lists as $Result) {
                if (array_key_exists($idField, $Result)) {
                    $Entity = $this->app['orm.em']->getRepository($className)->find($Result[$idField]);
                    $this->assertNotNull($Entity);
                } else {
                    $Entity = $this->app['orm.em']->getRepository($className)->find($Result['id']);
                    $this->assertNotNull($Entity);
                }

                foreach ($Result as $field => $value) {
                    $Reflect = new \ReflectionClass($Entity);
                    try {
                        $Property = $Reflect->getProperty($field);
                        $Property->setAccessible(true);
                        $this->expected = $Property->getValue($Entity);
                        $this->actual = $Result[$field];
                        if (!is_object($this->expected)) { // TODO Datetime などは変換する
                            $this->verify($table_name.': '.$field.' '.print_r($Result, true));
                        }
                    } catch (\ReflectionException $e) {
                        // FIXME プロパティが見つからないケースがある
                        var_dump($e->getMessage());
                    }
                }
            }
        }
    }
}
