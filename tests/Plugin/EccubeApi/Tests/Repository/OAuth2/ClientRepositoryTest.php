<?php
namespace Plugin\EccubeApi\Tests\Repository;

use Eccube\Tests\EccubeTestCase;
use Eccube\Application;
use Plugin\EccubeApi\Entity\OAuth2\Client as OAuth2Client;
use Plugin\EccubeApi\Entity\OAuth2\ClientScope;


/**
 * ClientRepositoryRepository test cases.
 *
 * @author Kentaro Ohkouchi
 */
class ClientRepositoryTest extends EccubeTestCase
{
    protected $Client;
    protected $Customer;

    public function setUp()
    {
        parent::setUp();
        $faker = $this->getFaker();
        $this->Customer = $this->createCustomer();

        $this->Client = new OAuth2Client();
        $this->Client->setCustomer($this->Customer);
        $this->Client->setAppName('test-client-name');
        $this->Client->setRedirectUri('http://example.com/redirect_uri');
        $this->Client->setClientIdentifier('test-client-id');
        $this->Client->setClientSecret('test-client-secret');
        $this->app['orm.em']->persist($this->Client);
        $this->app['orm.em']->flush($this->Client);
    }

    public function testGetClientDetails()
    {
        $Client = $this->app['eccube.repository.oauth2.client']->getClientDetails('test-client-id');

        $this->assertTrue(is_array($Client));

        $this->expected = 'test-client-secret';
        $this->actual = $Client['client_secret'];
        $this->verify();

        $this->expected = 'test-client-id';
        $this->actual = $Client['client_identifier'];
        $this->verify();

        $this->expected = 'test-client-name';
        $this->actual = $Client['app_name'];
        $this->verify();

        $this->expected = 'http://example.com/redirect_uri';
        $this->actual = $Client['redirect_uri'];
        $this->verify();
    }

    public function testGetClientDetailsWithNotFound()
    {
        $Client = $this->app['eccube.repository.oauth2.client']->getClientDetails('test-client-notfound');
        $this->assertNull($Client);
    }

    public function testCheckClientCredentials()
    {
        $this->expected = true;
        $this->actual = $this->app['eccube.repository.oauth2.client']->checkClientCredentials('test-client-id', 'test-client-secret');
        $this->verify();
    }

    public function testCheckClientCredentialsWithFailure()
    {
        $this->expected = false;
        $this->actual = $this->app['eccube.repository.oauth2.client']->checkClientCredentials('test-client-id', 'test-client-secret-bad');
        $this->verify();
    }

    public function testCheckClientCredentialsWithNull()
    {
        $this->expected = false;
        $this->actual = $this->app['eccube.repository.oauth2.client']->checkClientCredentials('test-client-id');
        $this->verify();
    }

    public function testCheckRestrictedGrantType()
    {
        $grantTypes = array(
            'refresh_token',
            'authorization_code',
            'implicit'
        );
        foreach ($grantTypes as $grantType) {
            $this->expected = true;
            $this->actual = $this->app['eccube.repository.oauth2.client']->checkRestrictedGrantType('test-client-id', $grantType);
            $this->verify();
        }
    }

    public function testCheckRestrictedGrantTypeWithFailure()
    {
        $grantTypes = array(
            'client_credentials',
            'password',
            'other'
        );
        foreach ($grantTypes as $grantType) {
            $this->expected = false;
            $this->actual = $this->app['eccube.repository.oauth2.client']->checkRestrictedGrantType('test-client-id', $grantType);
            $this->verify();
        }
    }

    public function testIsPublicClient()
    {
        $this->expected = false;
        $this->actual = $this->app['eccube.repository.oauth2.client']->isPublicClient('test-client-id');
        $this->verify();
    }

    public function testGetClientScope()
    {
        $Scopes = $this->app['eccube.repository.oauth2.scope']->findAll();
        foreach ($Scopes as $Scope) {
            if (in_array($Scope->getScope(), array('openid', 'offline_access'))) {
                $ClientScope = new ClientScope();
                $ClientScope->setClientId($this->Client->getId());
                $ClientScope->setClient($this->Client);
                $ClientScope->setScopeId($Scope->getId());
                $ClientScope->setScope($Scope);
                $this->app['orm.em']->persist($ClientScope);
                $this->Client->addClientScope($ClientScope);
                $this->app['orm.em']->flush();
            }
        }
        $this->expected = 'openid offline_access';
        $this->actual = $this->app['eccube.repository.oauth2.client']->getClientScope('test-client-id');
        $this->verify();
    }

    public function testGetClientScopeWithEmpty()
    {
        $this->actual = $this->app['eccube.repository.oauth2.client']->getClientScope('test-client-id');
        $this->assertTrue($this->actual === '');
    }

    public function testGetClientScopeWithNull()
    {
        $this->actual = $this->app['eccube.repository.oauth2.client']->getClientScope('test-client-id-notfound');
        $this->assertTrue($this->actual === null);
    }
}
