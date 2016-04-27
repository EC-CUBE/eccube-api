<?php
namespace Plugin\EccubeApi\Tests\Repository\OAuth2\OpenID;

use Plugin\EccubeApi\Tests\AbstractEccubeApiTestCase;

/**
 * PublicKeyRepositoryRepository test cases.
 *
 * @author Kentaro Ohkouchi
 */
class PubilcKeyRepositoryTest extends AbstractEccubeApiTestCase
{
    protected $Customer;
    protected $UserInfo;
    protected $Client;

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

        $this->PublicKey = $this->app['eccube.repository.oauth2.openid.public_key']->findOneBy(array('UserInfo' => $this->UserInfo));
    }

    public function testGetPublicKeyByClientId()
    {
        $this->expected = $this->PublicKey;
        $this->actual = $this->app['eccube.repository.oauth2.openid.public_key']->getPublicKeyByClientId('test-client-id');
        $this->verify();
    }

    public function testGetPublicKeyByClientIdWithNull()
    {
        $this->expected = null;
        $this->actual = $this->app['eccube.repository.oauth2.openid.public_key']->getPublicKeyByClientId('test-bad-id');
        $this->verify();
    }

    public function testGetPublicKey()
    {
        $this->expected = $this->PublicKey->getPublicKey();
        $this->actual = $this->app['eccube.repository.oauth2.openid.public_key']->getPublicKey('test-client-id');
        $this->verify();
    }

    public function testGetPublicKeyWithNull()
    {
        $this->expected = null;
        $this->actual = $this->app['eccube.repository.oauth2.openid.public_key']->getPublicKey();
        $this->verify();
    }

    public function testGetPrivateKey()
    {
        $this->expected = $this->PublicKey->getPrivateKey();
        $this->actual = $this->app['eccube.repository.oauth2.openid.public_key']->getPrivateKey('test-client-id');
        $this->verify();
    }

    public function testGetPrivateKeyWithNull()
    {
        $this->expected = null;
        $this->actual = $this->app['eccube.repository.oauth2.openid.public_key']->getPrivateKey();
        $this->verify();
    }

    public function testGetEncryptionAlgorithm()
    {
        $this->expected = 'RS256';
        $this->actual = $this->app['eccube.repository.oauth2.openid.public_key']->getEncryptionAlgorithm('test-client-id');
        $this->verify();
    }

    public function testGetEncryptionAlgorithmWithNull()
    {
        $this->expected = null;
        $this->actual = $this->app['eccube.repository.oauth2.openid.public_key']->getEncryptionAlgorithm();
        $this->verify();
    }
}
