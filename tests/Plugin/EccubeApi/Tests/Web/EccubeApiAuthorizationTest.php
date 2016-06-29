<?php

/*
 * This file is part of the EccubeApi
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\EccubeApi\Tests\Web;

use Plugin\EccubeApi\Util\EntityUtil;

class EccubeApiAuthorizationTest extends AbstractEccubeApiWebTestCase
{
    protected $Customer;
    protected $CustomerClient;
    protected $CustomerUserInfo;
    protected $Member;
    protected $MemberClient;
    protected $MemberUserInfo;
    protected $scope_granted;
    protected $state;
    protected $nonce;

    public function setUp()
    {
        parent::setUp();
        /** Member, Customer 共に選択可能な scope */
        $this->scope_granted = 'openid email customer_read customer_write customer_address_read';
        $this->Customer = $this->createCustomer();
        $this->CustomerUserInfo = $this->createUserInfo($this->Customer);
        $this->CustomerClient = $this->createApiClient($this->Customer);

        $this->Member = $this->app['eccube.repository.member']->find(2);
        $this->MemberInfo = $this->createUserInfo($this->Member);
        $this->MemberClient = $this->createApiClient($this->Member);

        $Scopes = $this->app['eccube.repository.oauth2.scope']->findByString($this->scope_granted, $this->Member);
        foreach ($Scopes as $Scope) {
            $this->addClientScope($this->CustomerClient, $Scope->getScope());
            $this->addClientScope($this->MemberClient, $Scope->getScope());
        }

        $this->state = sha1(openssl_random_pseudo_bytes(100));
        $this->nonce = sha1(openssl_random_pseudo_bytes(100));
    }

    /**
     * Member で Authorization Code を取得する.
     */
    public function testAuthorizationEndPointWithMember()
    {
        $client = $this->logInTo($this->Member);
        $path = $this->app->path('oauth2_server_admin_authorize');
        $params = array(
                    'client_id' => $this->MemberClient->getClientIdentifier(),
                    'redirect_uri' => $this->MemberClient->getRedirectUri(),
                    'response_type' => 'code',
                    'state' => $this->state,
                    'nonce' => $this->nonce,
                    'scope' => $this->scope_granted,
        );

        // GET でリクエストし, 認可画面を表示
        $crawler = $client->request('GET', $path, $params);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertRegExp('/OpenID Connect 認証/u', $crawler->filter('ol#permissions')->text());
        $this->assertRegExp('/メールアドレス/u', $crawler->filter('ol#permissions')->text());

        // 認可要求
        $params['authorized'] = 1;
        $params['_token'] = 'dummy';
        $crawler = $client->request('POST', $path, $params);

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $location = $this->client->getResponse()->headers->get('location');
        $this->assertRegExp('/^'.preg_quote($this->MemberClient->getRedirectUri(), '/').'/', $location);
        preg_match('/^'.preg_quote($this->MemberClient->getRedirectUri(), '/').'\?code=(\w+)&state='.$this->state.'/', $location, $matched);

        $authorization_code = $matched[1];
        $AuthorizationCode = $this->app['eccube.repository.oauth2.authorization_code']->getAuthorizationCode($authorization_code);
        $this->assertTrue(is_array($AuthorizationCode));

        $this->expected = $this->scope_granted;
        $this->actual = $AuthorizationCode['scope'];
        $this->verify();

        $this->expected = $this->MemberClient->getClientIdentifier();
        $this->actual = $AuthorizationCode['client_id'];
        $this->verify();
    }

    /**
     * Customer で Authorization Code を取得する.
     */
    public function testAuthorizationEndPointWithCustomer()
    {
        $client = $this->logInTo($this->Customer);
        $path = $this->app->path('oauth2_server_mypage_authorize');
        $params = array(
                    'client_id' => $this->CustomerClient->getClientIdentifier(),
                    'redirect_uri' => $this->CustomerClient->getRedirectUri(),
                    'response_type' => 'code',
                    'state' => $this->state,
                    'nonce' => $this->nonce,
                    'scope' => $this->scope_granted,
        );

        // GET でリクエストし, 認可画面を表示
        $crawler = $client->request('POST', $path, $params);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertRegExp('/OpenID Connect 認証/u', $crawler->filter('ol#permissions')->text());
        $this->assertRegExp('/メールアドレス/u', $crawler->filter('ol#permissions')->text());

        // 認可要求
        $params['authorized'] = 1;
        $params['_token'] = 'dummy';
        $crawler = $client->request('POST', $path, $params);

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $location = $this->client->getResponse()->headers->get('location');
        $this->assertRegExp('/^'.preg_quote($this->CustomerClient->getRedirectUri(), '/').'/', $location);
        preg_match('/^'.preg_quote($this->CustomerClient->getRedirectUri(), '/').'\?code=(\w+)&state='.$this->state.'/', $location, $matched);

        $authorization_code = $matched[1];
        $AuthorizationCode = $this->app['eccube.repository.oauth2.authorization_code']->getAuthorizationCode($authorization_code);
        $this->assertTrue(is_array($AuthorizationCode));

        $this->expected = $this->scope_granted;
        $this->actual = $AuthorizationCode['scope'];
        $this->verify();

        $this->expected = $this->CustomerClient->getClientIdentifier();
        $this->actual = $AuthorizationCode['client_id'];
        $this->verify();
    }

    /**
     * Member で Authorization Code を取得する.
     */
    public function testAuthorizationEndPointWithMemberOob()
    {
        $redirect_uri = 'urn:ietf:wg:oauth:2.0:oob';
        $this->MemberClient->setRedirectUri($redirect_uri);
        $this->app['orm.em']->flush($this->MemberClient);

        $client = $this->logInTo($this->Member);
        $path = $this->app->path('oauth2_server_admin_authorize');
        $params = array(
                    'client_id' => $this->MemberClient->getClientIdentifier(),
                    'redirect_uri' => $this->MemberClient->getRedirectUri(),
                    'response_type' => 'code',
                    'state' => $this->state,
                    'nonce' => $this->nonce,
                    'scope' => $this->scope_granted,
        );

        // GET でリクエストし, 認可画面を表示
        $crawler = $client->request('GET', $path, $params);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertRegExp('/OpenID Connect 認証/u', $crawler->filter('ol#permissions')->text());
        $this->assertRegExp('/メールアドレス/u', $crawler->filter('ol#permissions')->text());

        // 認可要求
        $params['authorized'] = 1;
        $params['_token'] = 'dummy';
        $crawler = $client->request('POST', $path, $params);

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $location = $this->client->getResponse()->headers->get('location');
        $this->assertRegExp('/^'.preg_quote($this->app->url('oauth2_server_admin_authorize'), '/').'/', $location);
        preg_match('/^'.preg_quote($this->app->url('oauth2_server_admin_authorize'), '/').'\/(\w+)/', $location, $matched);

        $authorization_code = $matched[1];
        $AuthorizationCode = $this->app['eccube.repository.oauth2.authorization_code']->getAuthorizationCode($authorization_code);
        $this->assertTrue(is_array($AuthorizationCode));

        $this->expected = $this->scope_granted;
        $this->actual = $AuthorizationCode['scope'];
        $this->verify();

        $this->expected = $this->MemberClient->getClientIdentifier();
        $this->actual = $AuthorizationCode['client_id'];
        $this->verify();

        // AuthorizationCode 表示画面
        $crawler = $client->request('GET', $this->app->url('oauth2_server_admin_authorize_oob', array('code' => $matched[1])));
        $this->expected = $AuthorizationCode['code'];
        $this->actual = $crawler->filter('pre')->text();
        $this->verify();
    }

    /**
     * Customer で Authorization Code を取得する.
     */
    public function testAuthorizationEndPointWithCustomerOob()
    {
        $redirect_uri = 'urn:ietf:wg:oauth:2.0:oob';
        $this->CustomerClient->setRedirectUri($redirect_uri);
        $this->app['orm.em']->flush($this->CustomerClient);

        $client = $this->logInTo($this->Customer);
        $path = $this->app->path('oauth2_server_mypage_authorize');
        $params = array(
                    'client_id' => $this->CustomerClient->getClientIdentifier(),
                    'redirect_uri' => $this->CustomerClient->getRedirectUri(),
                    'response_type' => 'code',
                    'state' => $this->state,
                    'nonce' => $this->nonce,
                    'scope' => $this->scope_granted,
        );

        // GET でリクエストし, 認可画面を表示
        $crawler = $client->request('GET', $path, $params);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertRegExp('/OpenID Connect 認証/u', $crawler->filter('ol#permissions')->text());
        $this->assertRegExp('/メールアドレス/u', $crawler->filter('ol#permissions')->text());

        // 認可要求
        $params['authorized'] = 1;
        $params['_token'] = 'dummy';
        $crawler = $client->request('POST', $path, $params);

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $location = $this->client->getResponse()->headers->get('location');
        $this->assertRegExp('/^'.preg_quote($this->app->url('oauth2_server_mypage_authorize'), '/').'/', $location);
        preg_match('/^'.preg_quote($this->app->url('oauth2_server_mypage_authorize'), '/').'\/(\w+)/', $location, $matched);

        $authorization_code = $matched[1];
        $AuthorizationCode = $this->app['eccube.repository.oauth2.authorization_code']->getAuthorizationCode($authorization_code);
        $this->assertTrue(is_array($AuthorizationCode));

        $this->expected = $this->scope_granted;
        $this->actual = $AuthorizationCode['scope'];
        $this->verify();

        $this->expected = $this->CustomerClient->getClientIdentifier();
        $this->actual = $AuthorizationCode['client_id'];
        $this->verify();

        // AuthorizationCode 表示画面
        $crawler = $client->request('GET', $this->app->url('oauth2_server_mypage_authorize_oob', array('code' => $matched[1])));
        $this->expected = $AuthorizationCode['code'];
        $this->actual = $crawler->filter('pre')->text();
        $this->verify();
    }

    /**
     * Member で OAuth2.0 Authorization code Flow を使用してアクセストークンを取得する.
     */
    public function testOAuth2AuthorizationCodeFlowWithMember()
    {
        $this->scope_granted = 'customer_read customer_write';

        $client = $this->logInTo($this->Member);
        $path = $this->app->path('oauth2_server_admin_authorize');
        $params = array(
                    'client_id' => $this->MemberClient->getClientIdentifier(),
                    'redirect_uri' => $this->MemberClient->getRedirectUri(),
                    'response_type' => 'code',
                    'state' => $this->state,
                    'nonce' => $this->nonce,
                    'scope' => $this->scope_granted,
        );

        // POST でリクエストし, 認可画面を表示
        $crawler = $client->request('POST', $path, $params);

        // 認可要求
        $params['authorized'] = 1;
        $params['_token'] = 'dummy';
        $crawler = $client->request('POST', $path, $params);

        $location = $client->getResponse()->headers->get('location');
        preg_match('/\?code=(\w+)&state='.$this->state.'/', $location, $matched);

        $authorization_code = $matched[1];

        // Token リクエスト(Basic認証を使用)
        $crawler = $client->request(
            'POST',
            $this->app->path('oauth2_server_token'),
            array(
                'grant_type' => 'authorization_code',
                'code' => $authorization_code,
                // 'client_id' => $this->MemberClient->getClientIdentifier(),
                // 'client_secret' => $this->MemberClient->getClientSecret(),
                'state' => $this->state,
                'nonce' => $this->nonce,
                'redirect_uri' => $this->MemberClient->getRedirectUri()
            ),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Basic '.base64_encode($this->MemberClient->getClientIdentifier().':'.$this->MemberClient->getClientSecret()),
            )
        );

        $this->assertTrue($client->getResponse()->isSuccessful());
        $TokenResponse = json_decode($client->getResponse()->getContent(), true);

        $this->expected = 3600;
        $this->actual = $TokenResponse['expires_in'];
        $this->verify();

        $this->expected = 'bearer'; // XXX 小文字で返ってくる
        $this->actual = $TokenResponse['token_type'];
        $this->verify();

        $this->expected = $this->scope_granted;
        $this->actual = $TokenResponse['scope'];
        $this->verify();

        $this->assertTrue(array_key_exists('refresh_token', $TokenResponse));

        $access_token = $TokenResponse['access_token'];

        // API Request
        $crawler = $client->request(
                'GET',
                $this->app->path('api_operation_find', array('table' => 'customer', 'id' => $this->Customer->getId())),
                array(),
                array(),
                array(
                    'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
                    'CONTENT_TYPE' => 'application/json',
                )

            );
        $content = json_decode($client->getResponse()->getContent(), true);

        $this->expected = $this->Customer->getName01();
        $this->actual = $content['customer']['name01'];
        $this->verify();

        $this->expected = $this->Customer->getName02();
        $this->actual = $content['customer']['name02'];
        $this->verify();
    }

    /**
     * Customer で OpenID Connect Authorization code Flow を使用してアクセストークンを取得する.
     */
    public function testOpenIdConnectAuthorizationCodeFlowWithCustomer()
    {
        $this->scope_granted = 'openid customer_read customer_write';

        $client = $this->logInTo($this->Customer);
        $path = $this->app->path('oauth2_server_mypage_authorize');
        $params = array(
                    'client_id' => $this->CustomerClient->getClientIdentifier(),
                    'redirect_uri' => $this->CustomerClient->getRedirectUri(),
                    'response_type' => 'code',
                    'state' => $this->state,
                    'nonce' => $this->nonce,
                    'scope' => $this->scope_granted,
        );

        // GET でリクエストし, 認可画面を表示
        $crawler = $client->request('GET', $path, $params);

        // 認可要求
        $params['authorized'] = 1;
        $params['_token'] = 'dummy';
        // ここで nonce をクエリストリングに含めなくてはならない
        $crawler = $client->request('POST', $path.'?nonce='.$this->nonce, $params);

        $location = $client->getResponse()->headers->get('location');
        preg_match('/\?code=(\w+)&state='.$this->state.'/', $location, $matched);

        $authorization_code = $matched[1];

        // Token リクエスト
        $crawler = $client->request(
            'POST',
            $this->app->path('oauth2_server_token'),
            array(
                'grant_type' => 'authorization_code',
                'code' => $authorization_code,
                'client_id' => $this->CustomerClient->getClientIdentifier(),
                'client_secret' => $this->CustomerClient->getClientSecret(),
                'state' => $this->state,
                'nonce' => $this->nonce,
                'redirect_uri' => $this->CustomerClient->getRedirectUri()
            )
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $TokenResponse = json_decode($client->getResponse()->getContent(), true);

        $this->expected = 3600;
        $this->actual = $TokenResponse['expires_in'];
        $this->verify();

        $this->expected = 'bearer'; // XXX 小文字で返ってくる
        $this->actual = $TokenResponse['token_type'];
        $this->verify();

        $this->expected = $this->scope_granted;
        $this->actual = $TokenResponse['scope'];
        $this->verify();

        // scope=offline_access が無い場合は refresh_token が取得できない
        $this->assertFalse(array_key_exists('refresh_token', $TokenResponse));

        // verify id_token
        $crawler = $client->request(
            'GET',
            $this->app->url('oauth2_server_tokeninfo'),
            array('id_token' => $TokenResponse['id_token']),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
            )
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $TokenInfo = json_decode($client->getResponse()->getContent(), true);

        $this->expected = rtrim($this->app->url('homepage'), '/');
        $this->actual = $TokenInfo['iss'];
        $this->verify();

        $this->expected = $this->CustomerClient->getClientIdentifier();
        $this->actual = $TokenInfo['aud'];
        $this->verify();

        $this->expected = $this->CustomerUserInfo->getSub();
        $this->actual = $TokenInfo['sub'];
        $this->verify();

        $this->expected = $this->nonce;
        $this->actual = $TokenInfo['nonce'];
        $this->verify('id_token に 発行した nonce が含まれているかどうか');

        $PublicKey = $this->app['eccube.repository.oauth2.openid.public_key']->findOneBy(array('UserInfo' => $this->CustomerUserInfo));
        // verify id_token with JWS
        $jwt = \JOSE_JWT::decode($TokenResponse['id_token']);
        $jws = new \JOSE_JWS($jwt);
        try {
            $jws->verify($PublicKey->getPublicKey(), $this->CustomerClient->getEncryptionAlgorithm());
        } catch (\JOSE_Exception_VerificationFailed $e) {
            $this->fail($e->getMessage());
        }

        $access_token = $TokenResponse['access_token'];
        $arrayEntity = EntityUtil::entityToArray($this->app, $this->Customer);
        $faker = $this->getFaker();
        $arrayEntity['kana01'] = $faker->firstKanaName;
        $arrayEntity['kana02'] = $faker->lastKanaName;

        // API Request
        $crawler = $client->request(
            'PUT',
            $this->app->path('api_operation_put', array('table' => 'customer', 'id' => $this->Customer->getId())),
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
                'CONTENT_TYPE' => 'application/json',
            ),
            json_encode($arrayEntity)
        );

        $this->expected = 204;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();

        $Result = $this->app['eccube.repository.customer']->find($this->Customer->getId());

        $this->expected = $arrayEntity['kana01'];
        $this->actual = $Result->getKana01();
        $this->verify();

        $this->expected = $arrayEntity['kana02'];
        $this->actual = $Result->getKana02();
        $this->verify();
    }

    /**
     * Customer で OAuth2.0 Implicit Flow を使用してアクセストークンを取得する.
     */
    public function testOAuth2ImplicitFlowWithCustomer()
    {
        $this->scope_granted = 'customer_read customer_write';

        $client = $this->logInTo($this->Customer);
        $path = $this->app->path('oauth2_server_mypage_authorize');
        $params = array(
                    'client_id' => $this->CustomerClient->getClientIdentifier(),
                    'redirect_uri' => $this->CustomerClient->getRedirectUri(),
                    'response_type' => 'token',
                    'state' => $this->state,
                    'nonce' => $this->nonce,
                    'scope' => $this->scope_granted,
        );

        // GET でリクエストし, 認可画面を表示
        $crawler = $client->request('GET', $path, $params);

        // 認可要求
        $params['authorized'] = 1;
        $params['_token'] = 'dummy';
        // ここで nonce をクエリストリングに含めなくてはならない
        $crawler = $client->request('POST', $path.'?nonce='.$this->nonce, $params);

        $location = $client->getResponse()->headers->get('location');
        $Url = parse_url($location);

        $Fragments = array();
        foreach (explode('&', $Url['fragment']) as $fragment) {
            $params = explode('=', $fragment);
            $Fragments[$params[0]] = urldecode($params[1]);
        }

        $this->expected = $this->state;
        $this->actual = $Fragments['state'];
        $this->verify('state が一致するかどうか');

        $access_token = $Fragments['access_token'];
        $arrayEntity = EntityUtil::entityToArray($this->app, $this->Customer);
        $faker = $this->getFaker();
        $arrayEntity['kana01'] = $faker->firstKanaName;
        $arrayEntity['kana02'] = $faker->lastKanaName;

        // API Request
        $crawler = $client->request(
            'PUT',
            $this->app->path('api_operation_put', array('table' => 'customer', 'id' => $this->Customer->getId())),
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
                'CONTENT_TYPE' => 'application/json',
            ),
            json_encode($arrayEntity)
        );

        $this->expected = 204;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();

        $Result = $this->app['eccube.repository.customer']->find($this->Customer->getId());

        $this->expected = $arrayEntity['kana01'];
        $this->actual = $Result->getKana01();
        $this->verify();

        $this->expected = $arrayEntity['kana02'];
        $this->actual = $Result->getKana02();
        $this->verify();
    }

    /**
     * Customer で OpenID Connect Implicit Flow を使用してアクセストークンを取得する.
     */
    public function testOpenIDConnectImplicitFlowWithCustomer()
    {
        $this->scope_granted = 'openid customer_read customer_write';

        $client = $this->logInTo($this->Customer);
        $path = $this->app->path('oauth2_server_mypage_authorize');
        $params = array(
                    'client_id' => $this->CustomerClient->getClientIdentifier(),
                    'redirect_uri' => $this->CustomerClient->getRedirectUri(),
                    'response_type' => 'token id_token',
                    'state' => $this->state,
                    'nonce' => $this->nonce,
                    'scope' => $this->scope_granted,
        );

        // GET でリクエストし, 認可画面を表示
        $crawler = $client->request('GET', $path, $params);

        // 認可要求
        $params['authorized'] = 1;
        $params['_token'] = 'dummy';
        // ここで nonce をクエリストリングに含めなくてはならない
        $crawler = $client->request('POST', $path.'?nonce='.$this->nonce, $params);

        $location = $client->getResponse()->headers->get('location');
        $Url = parse_url($location);

        $Fragments = array();
        foreach (explode('&', $Url['fragment']) as $fragment) {
            $params = explode('=', $fragment);
            $Fragments[$params[0]] = urldecode($params[1]);
        }

        $this->expected = $this->state;
        $this->actual = $Fragments['state'];
        $this->verify('state が一致するかどうか');

        $PublicKey = $this->app['eccube.repository.oauth2.openid.public_key']->findOneBy(array('UserInfo' => $this->CustomerUserInfo));
        // verify id_token with JWS
        $jwt = \JOSE_JWT::decode($Fragments['id_token']);

        $this->expected = rtrim($this->app->url('homepage'), '/');
        $this->actual = $jwt->claims['iss'];
        $this->verify();

        $this->expected = $this->CustomerClient->getClientIdentifier();
        $this->actual = $jwt->claims['aud'];
        $this->verify();

        $this->expected = $this->CustomerUserInfo->getSub();
        $this->actual = $jwt->claims['sub'];
        $this->verify();

        $this->expected = $this->nonce;
        $this->actual = $jwt->claims['nonce'];
        $this->verify('id_token に 発行した nonce が含まれているかどうか');

        $jws = new \JOSE_JWS($jwt);
        try {
            $jws->verify($PublicKey->getPublicKey(), $this->CustomerClient->getEncryptionAlgorithm());
        } catch (\JOSE_Exception_VerificationFailed $e) {
            $this->fail($e->getMessage());
        }

        $access_token = $Fragments['access_token'];
        $arrayEntity = EntityUtil::entityToArray($this->app, $this->Customer);
        $faker = $this->getFaker();
        $arrayEntity['kana01'] = $faker->firstKanaName;
        $arrayEntity['kana02'] = $faker->lastKanaName;

        // API Request
        $crawler = $client->request(
            'PUT',
            $this->app->path('api_operation_put', array('table' => 'customer', 'id' => $this->Customer->getId())),
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
                'CONTENT_TYPE' => 'application/json',
            ),
            json_encode($arrayEntity)
        );

        $this->expected = 204;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();

        $Result = $this->app['eccube.repository.customer']->find($this->Customer->getId());

        $this->expected = $arrayEntity['kana01'];
        $this->actual = $Result->getKana01();
        $this->verify();

        $this->expected = $arrayEntity['kana02'];
        $this->actual = $Result->getKana02();
        $this->verify();
    }

    /**
     * Member でリフレッシュトークンを使用してアクセストークンを更新する.
     */
    public function testRefreshTokenWithMember()
    {
        $this->scope_granted = 'customer_read customer_write';

        $client = $this->logInTo($this->Member);
        $path = $this->app->path('oauth2_server_admin_authorize');
        $params = array(
                    'client_id' => $this->MemberClient->getClientIdentifier(),
                    'redirect_uri' => $this->MemberClient->getRedirectUri(),
                    'response_type' => 'code',
                    'state' => $this->state,
                    'nonce' => $this->nonce,
                    'scope' => $this->scope_granted,
        );

        // POST でリクエストし, 認可画面を表示
        $crawler = $client->request('POST', $path, $params);

        // 認可要求
        $params['authorized'] = 1;
        $params['_token'] = 'dummy';
        $crawler = $client->request('POST', $path, $params);

        $location = $client->getResponse()->headers->get('location');
        preg_match('/\?code=(\w+)&state='.$this->state.'/', $location, $matched);

        $authorization_code = $matched[1];

        // Token リクエスト
        $crawler = $client->request(
            'POST',
            $this->app->path('oauth2_server_token'),
            array(
                'grant_type' => 'authorization_code',
                'code' => $authorization_code,
                'client_id' => $this->MemberClient->getClientIdentifier(),
                'client_secret' => $this->MemberClient->getClientSecret(),
                'state' => $this->state,
                'nonce' => $this->nonce,
                'redirect_uri' => $this->MemberClient->getRedirectUri()
            )
        );

        $this->assertTrue($client->getResponse()->isSuccessful());
        $TokenResponse = json_decode($client->getResponse()->getContent(), true);

        $this->expected = 3600;
        $this->actual = $TokenResponse['expires_in'];
        $this->verify();

        $this->expected = 'bearer'; // XXX 小文字で返ってくる
        $this->actual = $TokenResponse['token_type'];
        $this->verify();

        $this->expected = $this->scope_granted;
        $this->actual = $TokenResponse['scope'];
        $this->verify();

        $this->assertTrue(array_key_exists('refresh_token', $TokenResponse));

        $first_access_token = $TokenResponse['access_token'];

        // Token リクエスト
        $crawler = $client->request(
            'POST',
            $this->app->path('oauth2_server_token'),
            array(
                'grant_type' => 'refresh_token',
                'client_id' => $this->MemberClient->getClientIdentifier(),
                'client_secret' => $this->MemberClient->getClientSecret(),
                'refresh_token' => $TokenResponse['refresh_token']
            )
        );

        $this->assertTrue($client->getResponse()->isSuccessful());
        $TokenResponse = json_decode($client->getResponse()->getContent(), true);

        $this->assertFalse($first_access_token === $TokenResponse['access_token']);

        // API Request
        $crawler = $client->request(
                'GET',
                $this->app->path('api_operation_find', array('table' => 'customer', 'id' => $this->Customer->getId())),
                array(),
                array(),
                array(
                    'HTTP_AUTHORIZATION' => 'Bearer '.$TokenResponse['access_token'],
                    'CONTENT_TYPE' => 'application/json',
                )

            );
        $content = json_decode($client->getResponse()->getContent(), true);

        $this->expected = $this->Customer->getName01();
        $this->actual = $content['customer']['name01'];
        $this->verify();

        $this->expected = $this->Customer->getName02();
        $this->actual = $content['customer']['name02'];
        $this->verify();
    }

    /**
     * Customer で OpenID Connect UserInfo を取得する.
     */
    public function testOpenIdConnectUserInfoWithCustomer()
    {
        $this->scope_granted = 'openid email profile phone address';
        $this->Customer->setTel01('090')->setTel02('9999')->setTel03('9999');
        $this->app['orm.em']->flush($this->Customer);

        $Scopes = $this->app['eccube.repository.oauth2.scope']->findByString('profile phone address', $this->Customer);
        foreach ($Scopes as $Scope) {
            $this->addClientScope($this->CustomerClient, $Scope->getScope());
        }

        $client = $this->logInTo($this->Customer);
        $path = $this->app->path('oauth2_server_mypage_authorize');
        $params = array(
                    'client_id' => $this->CustomerClient->getClientIdentifier(),
                    'redirect_uri' => $this->CustomerClient->getRedirectUri(),
                    'response_type' => 'code',
                    'state' => $this->state,
                    'nonce' => $this->nonce,
                    'scope' => $this->scope_granted,
        );

        // GET でリクエストし, 認可画面を表示
        $crawler = $client->request('GET', $path, $params);

        // 認可要求
        $params['authorized'] = 1;
        $params['_token'] = 'dummy';
        // ここで nonce をクエリストリングに含めなくてはならない
        $crawler = $client->request('POST', $path.'?nonce='.$this->nonce, $params);

        $location = $client->getResponse()->headers->get('location');
        preg_match('/\?code=(\w+)&state='.$this->state.'/', $location, $matched);

        $authorization_code = $matched[1];

        // Token リクエスト
        $crawler = $client->request(
            'POST',
            $this->app->path('oauth2_server_token'),
            array(
                'grant_type' => 'authorization_code',
                'code' => $authorization_code,
                'client_id' => $this->CustomerClient->getClientIdentifier(),
                'client_secret' => $this->CustomerClient->getClientSecret(),
                'state' => $this->state,
                'nonce' => $this->nonce,
                'redirect_uri' => $this->CustomerClient->getRedirectUri()
            )
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $TokenResponse = json_decode($client->getResponse()->getContent(), true);

        $this->expected = 3600;
        $this->actual = $TokenResponse['expires_in'];
        $this->verify();

        $this->expected = 'bearer'; // XXX 小文字で返ってくる
        $this->actual = $TokenResponse['token_type'];
        $this->verify();

        $this->expected = $this->scope_granted;
        $this->actual = $TokenResponse['scope'];
        $this->verify();

        // scope=offline_access が無い場合は refresh_token が取得できない
        $this->assertFalse(array_key_exists('refresh_token', $TokenResponse));

        // verify id_token
        $crawler = $client->request(
            'GET',
            $this->app->url('oauth2_server_tokeninfo'),
            array('id_token' => $TokenResponse['id_token']),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
            )
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $TokenInfo = json_decode($client->getResponse()->getContent(), true);

        $this->expected = rtrim($this->app->url('homepage'), '/');
        $this->actual = $TokenInfo['iss'];
        $this->verify();

        $this->expected = $this->CustomerClient->getClientIdentifier();
        $this->actual = $TokenInfo['aud'];
        $this->verify();

        $this->expected = $this->CustomerUserInfo->getSub();
        $this->actual = $TokenInfo['sub'];
        $this->verify();

        $this->expected = $this->nonce;
        $this->actual = $TokenInfo['nonce'];
        $this->verify('id_token に 発行した nonce が含まれているかどうか');

        $PublicKey = $this->app['eccube.repository.oauth2.openid.public_key']->findOneBy(array('UserInfo' => $this->CustomerUserInfo));
        // verify id_token with JWS
        $jwt = \JOSE_JWT::decode($TokenResponse['id_token']);
        $jws = new \JOSE_JWS($jwt);
        try {
            $jws->verify($PublicKey->getPublicKey(), $this->CustomerClient->getEncryptionAlgorithm());
        } catch (\JOSE_Exception_VerificationFailed $e) {
            $this->fail($e->getMessage());
        }

        $access_token = $TokenResponse['access_token'];

        // API Request
        $crawler = $client->request(
            'GET',
            $this->app->path('oauth2_server_userinfo'),
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
                'CONTENT_TYPE' => 'application/json',
            )
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $UserInfo = json_decode($client->getResponse()->getContent(), true);

        $Result = $this->app['eccube.repository.customer']->find($this->Customer->getId());

        $this->expected = $Result->getName01().' '.$Result->getName02();
        $this->actual = $UserInfo['name'];
        $this->verify();

        $this->expected = $Result->getEmail();
        $this->actual = $UserInfo['preferred_username'];
        $this->verify();

        $this->expected = $Result->getEmail();
        $this->actual = $UserInfo['email'];
        $this->verify();
        $this->assertTrue($UserInfo['email_verified'], 'email_verified = true');

        $this->expected = $Result->getPref()->getName();
        $this->actual = $UserInfo['address']['region'];
        $this->verify();

        $this->expected = $Result->getTel01().'-'.$Result->getTel02().'-'.$Result->getTel03();
        $this->actual = $UserInfo['phone_number'];
        $this->verify();
        $this->assertFalse($UserInfo['phone_number_verified'], 'phone_number_verified = false');
    }

    /**
     * Customer が退会した場合
     */
    public function testDeletedCustomer()
    {
        $this->scope_granted = 'customer_read customer_write customer_address_read';

        $client = $this->logInTo($this->Customer);
        $path = $this->app->path('oauth2_server_mypage_authorize');
        $params = array(
                    'client_id' => $this->CustomerClient->getClientIdentifier(),
                    'redirect_uri' => $this->CustomerClient->getRedirectUri(),
                    'response_type' => 'token',
                    'state' => $this->state,
                    'nonce' => $this->nonce,
                    'scope' => $this->scope_granted,
        );

        // GET でリクエストし, 認可画面を表示
        $crawler = $client->request('GET', $path, $params);

        // 認可要求
        $params['authorized'] = 1;
        $params['_token'] = 'dummy';
        // ここで nonce をクエリストリングに含めなくてはならない
        $crawler = $client->request('POST', $path.'?nonce='.$this->nonce, $params);

        $location = $client->getResponse()->headers->get('location');
        $Url = parse_url($location);

        $Fragments = array();
        foreach (explode('&', $Url['fragment']) as $fragment) {
            $params = explode('=', $fragment);
            $Fragments[$params[0]] = urldecode($params[1]);
        }

        $access_token = $Fragments['access_token'];
        $CustomerAddress = $this->app['eccube.repository.customer_address']->findOneBy(array('Customer' => $this->Customer));

        $this->Customer->setDelFlg(1); // 退会扱い
        $this->app['orm.em']->flush();
        $this->app['orm.em']->detach($this->Customer);

        $AccessToken = $this->app['eccube.repository.oauth2.access_token']->findOneBy(array('token' => $access_token));
        $this->app['orm.em']->detach($AccessToken); // キャッシュしないよう detach

        // API Request
        $crawler = $client->request(
            'GET',
            $this->app->path('api_operation_find', array('table' => 'customer_address', 'id' => $CustomerAddress->getId())),
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
                'CONTENT_TYPE' => 'application/json',
            )
        );

        $this->expected = 401;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();

        $ResponseBody = json_decode($this->client->getResponse()->getContent(), true);

        $this->expected = 'invalid_token';
        $this->actual = $ResponseBody['error'];
        $this->verify();

        $this->expected = 'The access token provided is invalid';
        $this->actual = $ResponseBody['error_description'];
        $this->verify();
    }

    /**
     * Member を削除した場合
     */
    public function testDeletedMember()
    {
        $this->scope_granted = 'customer_read customer_write customer_address_read';

        $client = $this->logInTo($this->Member);
        $path = $this->app->path('oauth2_server_admin_authorize');
        $params = array(
                    'client_id' => $this->MemberClient->getClientIdentifier(),
                    'redirect_uri' => $this->MemberClient->getRedirectUri(),
                    'response_type' => 'token',
                    'state' => $this->state,
                    'nonce' => $this->nonce,
                    'scope' => $this->scope_granted,
        );

        // GET でリクエストし, 認可画面を表示
        $crawler = $client->request('GET', $path, $params);

        // 認可要求
        $params['authorized'] = 1;
        $params['_token'] = 'dummy';
        // ここで nonce をクエリストリングに含めなくてはならない
        $crawler = $client->request('POST', $path.'?nonce='.$this->nonce, $params);

        $location = $client->getResponse()->headers->get('location');
        $Url = parse_url($location);

        $Fragments = array();
        foreach (explode('&', $Url['fragment']) as $fragment) {
            $params = explode('=', $fragment);
            $Fragments[$params[0]] = urldecode($params[1]);
        }

        $access_token = $Fragments['access_token'];
        $CustomerAddress = $this->app['eccube.repository.customer_address']->findOneBy(array('Customer' => $this->Customer));

        $this->Member->setDelFlg(1); // 退会扱い
        $this->app['orm.em']->flush();
        $this->app['orm.em']->detach($this->Member);

        $AccessToken = $this->app['eccube.repository.oauth2.access_token']->findOneBy(array('token' => $access_token));
        $this->app['orm.em']->detach($AccessToken); // キャッシュしないよう detach

        // API Request
        $crawler = $client->request(
            'GET',
            $this->app->path('api_operation_find', array('table' => 'customer_address', 'id' => $CustomerAddress->getId())),
            array(),
            array(),
            array(
                'HTTP_AUTHORIZATION' => 'Bearer '.$access_token,
                'CONTENT_TYPE' => 'application/json',
            )
        );

        $this->expected = 401;
        $this->actual = $this->client->getResponse()->getStatusCode();
        $this->verify();

        $ResponseBody = json_decode($this->client->getResponse()->getContent(), true);

        $this->expected = 'invalid_token';
        $this->actual = $ResponseBody['error'];
        $this->verify();

        $this->expected = 'The access token provided is invalid';
        $this->actual = $ResponseBody['error_description'];
        $this->verify();
    }
}
