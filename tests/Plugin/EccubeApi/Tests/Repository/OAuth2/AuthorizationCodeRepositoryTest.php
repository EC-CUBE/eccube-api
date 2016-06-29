<?php

/*
 * This file is part of the EccubeApi
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\EccubeApi\Tests\Repository\OAuth2;

use Eccube\Application;
use Plugin\EccubeApi\Tests\AbstractEccubeApiTestCase;


/**
 * AuthorizatoinCodeRepositoryTest test cases.
 *
 * @author Kentaro Ohkouchi
 */
class AuthorizatoinCodeRepositoryTest extends AbstractEccubeApiTestCase
{
    protected $Client;
    protected $Customer;
    protected $code;
    protected $expires;
    protected $scope;
    protected $redirect_uri;

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

        $this->code = 'token-string';
        $this->expires = time() + 3600;
        $this->scope = 'openid offline_access';
        $this->redirect_uri = 'https://example.com';
        // TODO http://ec-cube.github.io/api_authorization.html の id_token サンプルを流用
        $this->id_token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xOTIuMTY4LjU2LjEwMTo4MDgxIiwic3ViIjoiNG53c25ic3pJSDRYdVFuWHdpZ3RQOEhhS1FwamVHeDQ5OXMwQTlJMXFrbyIsImF1ZCI6IjJlODE1MzAyM2Q2YWZiMmZiMjk5MzFkYmY5YTI3NWVkNDcxNWYzODQiLCJpYXQiOjE0NjA1MzU1MjMsImV4cCI6MTQ2MDUzOTEyMywiYXV0aF90aW1lIjoxNDYwNTM1NTIzLCJub25jZSI6InJhbmRvbV9ub25jZSJ9.D3RE1i-Oc_bCANI28BwqT-6voLk645kqGZCs3PCOfDRATUX6_hvyBOc3PvfrH6BCaNfYX8m8sGQPD2g-GRUJ-j6OMCHp1KHcycsN5OS6QoZOucvM_gDKITivwW0q3BvLYsc-zK00DRlYuAhSW1pCqdWGRGk-3LWbqfasttYvx34KoSazfCsIyMqxC_zQ4qDoYaReeuCjiMX1xW3vXueEidMQ9_5s7SQgJwtwMnqOdDoEHUQce65wWa2yNXBHaohrGwXmg9Sbd5pD_Anhrh7WIAnYEbDoHc1rb40oUT-kye5cplYUTd4F9y88PnyXeWN3-vGRVxsvMRdJQmiTqzwVvA';
    }

    public function testSetAccessToken()
    {
        $this->app['eccube.repository.oauth2.authorization_code']->setAuthorizationCode($this->code, 'test-client-id', $this->UserInfo->getSub(), $this->redirect_uri, $this->expires, $this->scope, $this->id_token);

        $AuthorizationCode = $this->app['eccube.repository.oauth2.authorization_code']->findOneBy(array('code' => $this->code));

        $this->expected = $this->code;
        $this->actual = $AuthorizationCode->getCode();
        $this->verify();

        $this->expected = $this->Client;
        $this->actual = $AuthorizationCode->getClient();
        $this->verify();

        $this->expected = $this->UserInfo;
        $this->actual = $AuthorizationCode->getUser();
        $this->verify();

        $this->expected = $this->redirect_uri;
        $this->actual = $AuthorizationCode->getRedirectUri();
        $this->verify();

        $ts = new \DateTime();
        $this->expected = $ts->setTimestamp($this->expires);
        $this->actual = $AuthorizationCode->getExpires();
        $this->verify();

        $this->expected = $this->scope;
        $this->actual = $AuthorizationCode->getScope();
        $this->verify();

        $this->expected = $this->id_token;
        $this->actual = $AuthorizationCode->getIdToken();
        $this->verify();
    }

    public function testGetAuthorizationCode()
    {
        $this->app['eccube.repository.oauth2.authorization_code']->setAuthorizationCode($this->code, 'test-client-id', $this->UserInfo->getSub(), $this->redirect_uri, $this->expires, $this->scope, $this->id_token);

        $AuthorizationCode = $this->app['eccube.repository.oauth2.authorization_code']->getAuthorizationCode($this->code);

        $this->expected = $this->code;
        $this->actual = $AuthorizationCode['code'];
        $this->verify();

        $this->expected = $this->redirect_uri;
        $this->actual = $AuthorizationCode['redirect_uri'];
        $this->verify();

        $this->expected = $this->expires;
        $this->actual = $AuthorizationCode['expires'];
        $this->verify();

        $this->expected = $this->scope;
        $this->actual = $AuthorizationCode['scope'];
        $this->verify();

        $this->expected = $this->id_token;
        $this->actual = $AuthorizationCode['id_token'];
        $this->verify();
    }

    public function testExpireAuthorizationCode()
    {
        $this->expires = time() - 100; // 現在時より前に設定

        $this->app['eccube.repository.oauth2.authorization_code']->setAuthorizationCode($this->code, 'test-client-id', $this->UserInfo->getSub(), $this->redirect_uri, $this->expires, $this->scope, $this->id_token);

        $this->app['eccube.repository.oauth2.authorization_code']->expireAuthorizationCode($this->code);

        $AuthorizationCode = $this->app['eccube.repository.oauth2.authorization_code']->findOneBy(array('code' => $this->code));

        $this->assertNull($AuthorizationCode);
    }

    public function testExpireAuthorizationCodeWithoutExpires()
    {
        $this->expires = time() + 100; // 現在時より後に設定

        $this->app['eccube.repository.oauth2.authorization_code']->setAuthorizationCode($this->code, 'test-client-id', $this->UserInfo->getSub(), $this->redirect_uri, $this->expires, $this->scope, $this->id_token);

        $this->app['eccube.repository.oauth2.authorization_code']->expireAuthorizationCode($this->code);

        $AuthorizationCode = $this->app['eccube.repository.oauth2.authorization_code']->findOneBy(array('code' => $this->code));

        $this->assertNotNull($AuthorizationCode);
    }

    public function testExpireAuthorizationCodeWithNotfound()
    {
        $this->expires = time() - 100;
        $this->code = 'code-not-found';

        try {
            $this->app['eccube.repository.oauth2.authorization_code']->expireAuthorizationCode($this->code);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }
}
