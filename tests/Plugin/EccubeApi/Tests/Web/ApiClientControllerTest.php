<?php

/*
 * This file is part of the EccubeApi
 *
 * Copyright (C) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\EccubeApi\Tests\Web;

use Plugin\EccubeApi\Util\EntityUtil;

class ApiClientControllerTest extends AbstractEccubeApiWebTestCase
{
    protected $Customer;
    protected $Member;
    protected $CustomerClient;
    protected $CustomerUserInfo;
    protected $MemberClient;
    protected $MemberUserInfo;

    public function setUp()
    {
        parent::setUp();
        $this->Customer = $this->createCustomer();
        $this->Member = $this->app['eccube.repository.member']->find(2);
    }

    public function testCustomerApiLists()
    {
        $this->CustomerUserInfo = $this->createUserInfo($this->Customer);
        $this->CustomerClient = $this->createApiClient($this->Customer);
        $client = $this->logInTo($this->Customer);
        $crawler = $client->request('GET', $this->app->path('mypage_api_lists'));

        $this->expected = $this->CustomerClient->getAppName();
        $this->actual = $crawler->filter('#api_client_list__address_detail--'.$this->CustomerClient->getId())->text();
        $this->verify();
    }

    public function testCustomerApiClientEdit()
    {
        $this->CustomerUserInfo = $this->createUserInfo($this->Customer);
        $this->CustomerClient = $this->createApiClient($this->Customer);
        $client = $this->logInTo($this->Customer);
        $crawler = $client->request('GET', $this->app->path('mypage_api_client_edit', array('client_id' => $this->CustomerClient->getId())));

        $form = $this->createForm($this->CustomerClient, $this->CustomerUserInfo);
        $crawler = $client->request(
            'POST',
            $this->app->path('mypage_api_client_edit', array('client_id' => $this->CustomerClient->getId())),
            array('api_client' => $form)
        );

        $this->assertTrue($this->client->getResponse()->isRedirect(
            $this->app->url('mypage_api_client_edit', array('client_id' => $this->CustomerClient->getId()))
        ));

        $Client = $this->app['eccube.repository.oauth2.client']->find($this->CustomerClient->getId());
        $this->expected = $form['app_name'];
        $this->actual = $Client->getAppName();
        $this->verify();
    }

    public function testCustomerApiNewClient()
    {
        $this->CustomerUserInfo = $this->createUserInfo($this->Customer);
        $this->CustomerClient = $this->createApiClient($this->Customer);
        $client = $this->logInTo($this->Customer);
        $crawler = $client->request('GET', $this->app->path('mypage_api_client_new'));

        $form = $this->createForm($this->CustomerClient, $this->CustomerUserInfo);
        $client_id = $this->CustomerClient->getId();
        $crawler = $client->request(
            'POST',
            $this->app->path('mypage_api_client_new'),
            array('api_client' => $form)
        );

        $this->assertTrue($this->client->getResponse()->isRedirect($this->client->getResponse()->headers->get('location')));
        preg_match('/([0-9]+)\/edit$/', $this->client->getResponse()->headers->get('location'), $matched);

        $Client = $this->app['eccube.repository.oauth2.client']->find($matched[1]);
        $this->expected = $form['app_name'];
        $this->actual = $Client->getAppName();
        $this->verify();
    }

    public function testCustomerApiClientDelete()
    {
        $this->CustomerUserInfo = $this->createUserInfo($this->Customer);
        $this->CustomerClient = $this->createApiClient($this->Customer);
        $client_id = $this->CustomerClient->getId();
        $client = $this->logInTo($this->Customer);
        $crawler = $client->request(
            'DELETE',
            $this->app->path('mypage_api_client_delete',
                             array('client_id' => $this->CustomerClient->getId()))
        );

        $this->assertTrue($this->client->getResponse()->isRedirect(
            $this->app->url('mypage_api_lists')
        ));

        $Client = $this->app['eccube.repository.oauth2.client']->find($client_id);
        $this->assertNull($Client);
    }

    public function testMemberApiLists()
    {
        $this->MemberUserInfo = $this->createUserInfo($this->Member);
        $this->MemberClient = $this->createApiClient($this->Member);
        $client = $this->logInTo($this->Member);
        $crawler = $client->request('GET', $this->app->path('admin_api_lists', array('member_id' => $this->Member->getId())));

        $this->expected = $this->MemberClient->getAppName();
        $this->actual = $crawler->filter('#client_list__name--'.$this->MemberClient->getId())->text();
        $this->verify();
    }

    public function testMemberApiNewClient()
    {
        $this->MemberUserInfo = $this->createUserInfo($this->Member);
        $this->MemberClient = $this->createApiClient($this->Member);
        $client = $this->logInTo($this->Member);
        $crawler = $client->request(
            'GET',
            $this->app->path('admin_setting_system_client_new', array('member_id' => $this->Member->getId()))
        );

        $form = $this->createForm($this->MemberClient, $this->MemberUserInfo);
        $client_id = $this->MemberClient->getId();
        $crawler = $client->request(
            'POST',
            $this->app->path('admin_setting_system_client_new', array('member_id' => $this->Member->getId())),
            array('api_client' => $form)
        );

        $this->assertTrue($this->client->getResponse()->isRedirect($this->client->getResponse()->headers->get('location')));
        preg_match('/([0-9]+)\/edit$/', $this->client->getResponse()->headers->get('location'), $matched);

        $Client = $this->app['eccube.repository.oauth2.client']->find($matched[1]);
        $this->expected = $form['app_name'];
        $this->actual = $Client->getAppName();
        $this->verify();
    }

    public function testMemberApiClientEdit()
    {
        $this->MemberUserInfo = $this->createUserInfo($this->Member);
        $this->MemberClient = $this->createApiClient($this->Member);
        $client = $this->logInTo($this->Member);
        $crawler = $client->request(
            'GET',
            $this->app->path(
                'admin_setting_system_client_edit',
                array('member_id' => $this->Member->getId(), 'client_id' => $this->MemberClient->getId())
            )
        );

        $form = $this->createForm($this->MemberClient, $this->MemberUserInfo);
        $crawler = $client->request(
            'POST',
            $this->app->path('admin_setting_system_client_edit',
                             array('member_id' => $this->Member->getId(), 'client_id' => $this->MemberClient->getId())),
            array('api_client' => $form)
        );

        $this->assertTrue($this->client->getResponse()->isRedirect(
            $this->app->url('admin_setting_system_client_edit',
                            array(
                                'member_id' => $this->Member->getId(),
                                'client_id' => $this->MemberClient->getId()
                            ))
        ));

        $Client = $this->app['eccube.repository.oauth2.client']->find($this->MemberClient->getId());
        $this->expected = $form['app_name'];
        $this->actual = $Client->getAppName();
        $this->verify();
    }

    public function testMemberApiClientDelete()
    {
        $this->MemberUserInfo = $this->createUserInfo($this->Member);
        $this->MemberClient = $this->createApiClient($this->Member);
        $client_id = $this->MemberClient->getId();
        $client = $this->logInTo($this->Member);
        $crawler = $client->request(
            'DELETE',
            $this->app->path('admin_setting_system_client_delete',
                             array('member_id' => $this->Member->getId(), 'client_id' => $this->MemberClient->getId()))
        );

        $this->assertTrue($this->client->getResponse()->isRedirect(
            $this->app->url('admin_api_lists', array('member_id' => $this->Member->getId()))
        ));

        $Client = $this->app['eccube.repository.oauth2.client']->find($client_id);
        $this->assertNull($Client);
    }

    protected function createForm($Client, $UserInfo)
    {
        $faker = $this->getFaker();
        $sub = $UserInfo->getSub();
        $PublicKey = $this->app['eccube.repository.oauth2.openid.public_key']->getPublicKey($Client->getClientIdentifier());
        $form = array(
            'app_name' => $faker->word,
            'redirect_uri' => $faker->url,
            'client_identifier' => $Client->getClientIdentifier(),
            'client_secret' => $Client->getClientSecret(),
            'Scopes' => array('openid'),
            'public_key' => $PublicKey,
            'encryption_algorithm' => 'RS256',
            '_token' => 'dummy'
        );
        return $form;
    }
}
