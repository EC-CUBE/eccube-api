<?php
namespace Plugin\EccubeApi\Tests\Repository;

use Eccube\Application;
use Plugin\EccubeApi\Tests\AbstractEccubeApiTestCase;


/**
 * AccessTokenRepositoryRepository test cases.
 *
 * @author Kentaro Ohkouchi
 */
class AccessTokenRepositoryTest extends AbstractEccubeApiTestCase
{
    protected $Client;
    protected $Customer;

    public function setUp()
    {
        parent::setUp();
        $faker = $this->getFaker();
        $this->Customer = $this->createCustomer();

        $this->Client = $this->createApiClient(
            $this->Customer,
            'test-client-name',
            'test-client-id',
            'test-client-secret',
            'http://example.com/redirect_uri'
        );
    }

    public function testGetBaseInfoWithId()
    {
        $AccessTokens = $this->app['eccube.repository.oauth2.access_token']->findAll();
    }
}
