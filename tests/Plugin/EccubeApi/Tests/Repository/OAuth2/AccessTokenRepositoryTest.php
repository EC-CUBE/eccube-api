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
 * AccessTokenRepositoryRepository test cases.
 *
 * @author Kentaro Ohkouchi
 */
class AccessTokenRepositoryTest extends AbstractEccubeApiTestCase
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

    public function testSetAccessToken()
    {
        $this->app['eccube.repository.oauth2.access_token']->setAccessToken($this->token, 'test-client-id', $this->UserInfo->getId(), $this->expires, $this->scope);

        $AccessToken = $this->app['eccube.repository.oauth2.access_token']->findOneBy(array('token' => $this->token));

        $this->expected = $this->token;
        $this->actual = $AccessToken->getToken();
        $this->verify();

        $this->expected = $this->Client;
        $this->actual = $AccessToken->getClient();
        $this->verify();

        $this->expected = $this->UserInfo;
        $this->actual = $AccessToken->getUser();
        $this->verify();

        $ts = new \DateTime();
        $this->expected = $ts->setTimestamp($this->expires);
        $this->actual = $AccessToken->getExpires();
        $this->verify();

        $this->expected = $this->scope;
        $this->actual = $AccessToken->getScope();
        $this->verify();
    }

    public function testGetAccessToken()
    {
        $this->app['eccube.repository.oauth2.access_token']->setAccessToken($this->token, 'test-client-id', $this->UserInfo->getId(), $this->expires, $this->scope);

        $AccessToken = $this->app['eccube.repository.oauth2.access_token']->getAccessToken($this->token);
        $this->assertTrue(is_array($AccessToken));

        $this->expected = $this->token;
        $this->actual = $AccessToken['token'];
        $this->verify();

        $ts = new \DateTime();
        $this->expected = $this->expires;
        $this->actual = $AccessToken['expires'];
        $this->verify();

        $this->expected = $this->scope;
        $this->actual = $AccessToken['scope'];
        $this->verify();
    }
}
