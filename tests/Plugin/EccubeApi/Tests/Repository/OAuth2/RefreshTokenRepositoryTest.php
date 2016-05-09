<?php
namespace Plugin\EccubeApi\Tests\Repository\OAuth2;

use Eccube\Application;
use Plugin\EccubeApi\Tests\AbstractEccubeApiTestCase;


/**
 * RefreshTokenRepositoryRepository test cases.
 *
 * @author Kentaro Ohkouchi
 */
class RefreshTokenRepositoryTest extends AbstractEccubeApiTestCase
{
    protected $Client;
    protected $Customer;
    protected $token;

    public function setUp()
    {
        parent::setUp();

        $this->Customer = $this->createCustomer();
        $this->UserInfo = $this->createUserInfo($this->Customer);
        $this->Client = $this->createApiClient(
            $this->Customer,
            'test-client-name',
            'test-client-id',
            'test-client-secret',
            'http://example.com/redirect_uri'
        );

        $this->token = 'token-string';
        $this->expires = time() + 3600;
        $this->scope = 'openid offline_access';
    }

    public function testSetRefreshToken()
    {
        $this->app['eccube.repository.oauth2.refresh_token']->setRefreshToken($this->token, 'test-client-id', $this->UserInfo->getId(), $this->expires, $this->scope);

        $RefreshToken = $this->app['eccube.repository.oauth2.refresh_token']->findOneBy(array('refresh_token' => $this->token));

        $this->expected = $this->token;
        $this->actual = $RefreshToken->getRefreshToken();
        $this->verify();

        $this->expected = $this->Client;
        $this->actual = $RefreshToken->getClient();
        $this->verify();

        $this->expected = $this->UserInfo;
        $this->actual = $RefreshToken->getUser();
        $this->verify();

        $ts = new \DateTime();
        $this->expected = $ts->setTimestamp($this->expires);
        $this->actual = $RefreshToken->getExpires();
        $this->verify();

        $this->expected = $this->scope;
        $this->actual = $RefreshToken->getScope();
        $this->verify();
    }

    public function testGetRefreshToken()
    {
        $this->app['eccube.repository.oauth2.refresh_token']->setRefreshToken($this->token, 'test-client-id', $this->UserInfo->getId(), $this->expires, $this->scope);

        $RefreshToken = $this->app['eccube.repository.oauth2.refresh_token']->getRefreshToken($this->token);
        $this->assertTrue(is_array($RefreshToken));

        $this->expected = $this->token;
        $this->actual = $RefreshToken['refresh_token'];
        $this->verify();

        $ts = new \DateTime();
        $this->expected = $this->expires;
        $this->actual = $RefreshToken['expires'];
        $this->verify();

        $this->expected = $this->scope;
        $this->actual = $RefreshToken['scope'];
        $this->verify();
    }

    public function testUnsetRefreshToken()
    {
        $this->app['eccube.repository.oauth2.refresh_token']->setRefreshToken($this->token, 'test-client-id', $this->UserInfo->getId(), $this->expires, $this->scope);

        $this->app['eccube.repository.oauth2.refresh_token']->unsetRefreshToken($this->token);

        $RefreshToken = $this->app['eccube.repository.oauth2.refresh_token']->getRefreshToken($this->token);
        $this->assertNull($RefreshToken);
    }

    public function testUnsetRefreshTokenWithNotFound()
    {
        try {
            $this->app['eccube.repository.oauth2.refresh_token']->unsetRefreshToken('not-refresh-token');
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }
}
