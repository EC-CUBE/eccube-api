<?php

namespace Plugin\EccubeApi\Tests\Web;

use Eccube\Common\Constant;
use Eccube\Tests\Mock\CsrfTokenMock;
use Plugin\EccubeApi\Tests\AbstractEccubeApiTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpKernel\Client;


class AbstractEccubeApiWebTestCase extends AbstractEccubeApiTestCase
{

    /**
     * User をログインさせてHttpKernel\Client を返す.
     *
     * @param UserInterface $User ログインさせる User
     * @return Symfony\Component\HttpKernel\Client
     */
    protected function loginTo(UserInterface $User)
    {
        $firewall = 'admin';
        $role = array('ROLE_ADMIN');
        if ($User instanceof \Eccube\Entity\Customer) {
            $firewall = 'customer';
            $role = array('ROLE_USER');
        }
        $token = new UsernamePasswordToken($User, null, $firewall, $role);

        $this->app['session']->set('_security_' . $firewall, serialize($token));
        $this->app['session']->save();

        $cookie = new Cookie($this->app['session']->getName(), $this->app['session']->getId());
        $this->client->getCookieJar()->set($cookie);
        return $this->client;
    }

    /**
     * AccessToken を生成して返す.
     *
     * @param UserInterface $User ログインさせる User
     */
    protected function doAuthorized(UserInterface $User)
    {
        $UserInfo = $this->createUserInfo($User);
        $Client = $this->createApiClient(
            $User,
            'test-client-name',
            'test-client-id',
            'test-client-secret',
            'http://example.com/redirect_uri'
        );

        $Scopes = $this->app['eccube.repository.oauth2.scope']->findAll();
        $scope_granted = '';
        foreach ($Scopes as $Scope) {
            $this->addClientScope($Client, $Scope->getScope());
            $scope_granted .= ' '.$Scope->getScope();
        }
        $scope_granted = trim($scope_granted);
        $token = 'test-token';

        $this->app['eccube.repository.oauth2.access_token']->setAccessToken($token, 'test-client-id', $UserInfo->getId(), time() + 3600, $scope_granted);

        $AccessToken = $this->app['eccube.repository.oauth2.access_token']->getAccessToken($token);
        return $AccessToken;
    }
}
