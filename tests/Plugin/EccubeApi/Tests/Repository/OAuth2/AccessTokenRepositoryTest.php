<?php
namespace Plugin\EccubeApi\Tests\Repository;

use Eccube\Tests\EccubeTestCase;
use Eccube\Application;
use Eccube\Entity\BaseInfo;


/**
 * AccessTokenRepositoryRepository test cases.
 *
 * @author Kentaro Ohkouchi
 */
class AccessTokenRepositoryTest extends EccubeTestCase
{
    public function setUp()
    {
        parent::setUp();


    }

    public function testGetBaseInfoWithId()
    {
        $AccessTokens = $this->app['eccube.repository.oauth2.access_token']->findAll();
    }
}
