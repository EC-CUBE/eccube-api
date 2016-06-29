<?php

/*
 * This file is part of the EccubeApi
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\EccubeApi\Controller;

use Eccube\Application;
use Eccube\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use OAuth2\HttpFoundationBridge\Response as BridgeResponse;
use OAuth2\Encryption\FirebaseJwt as Jwt;

/**
 * ApiClientController
 *
 * APIクライアントを管理するためのコントローラ
 *
 * @author Kentaro Ohkouchi
 */
class ApiClientController extends AbstractController
{

    /** デフォルトの暗号化方式. */
    const DEFAULT_ENCRYPTION_ALGORITHM = 'RS256';

    /**
     * API クライアント一覧を表示します.
     *
     * @param Application $app
     * @param Request $request
     * @param integer $member_id \Eccube\Entity\Member の ID
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function lists(Application $app, Request $request, $member_id = null)
    {
        $searchConditions = array();
        // ログイン中のユーザーのインスタンスによって処理を切り替える
        if ($app->user() instanceof \Eccube\Entity\Member) {
            $User = $app['eccube.repository.member']->find($member_id);
            $searchConditions = array('Member' => $User);
            $view = 'EccubeApi/Resource/template/admin/Api/lists.twig';
        } else {
            $User = $app['eccube.repository.customer']->find($app->user()->getId());
            $searchConditions = array('Customer' => $User);
            $view = 'EccubeApi/Resource/template/mypage/Api/lists.twig';
        }
        $Clients = $app['eccube.repository.oauth2.client']->findBy($searchConditions);

        $builder = $app['form.factory']->createBuilder();
        $form = $builder->getForm();

        return $app->render($view, array(
            'form' => $form->createView(),
            'User' => $User,
            'Clients' => $Clients,
        ));
    }

    /**
     * APIクライアントを編集します.
     *
     * @param Application $app
     * @param Request $request
     * @param integer $member_id \Eccube\Entity\Member の ID
     * @param integer $client_id APIクライアントID
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function edit(Application $app, Request $request, $member_id = null, $client_id = null)
    {
        $is_admin = false;
        // ログイン中のユーザーのインスタンスによって処理を切り替える
        if ($app->user() instanceof \Eccube\Entity\Member) {
            $User = $app['eccube.repository.member']->find($member_id);
            $searchConditions = array('Member' => $User);
            $view = 'EccubeApi/Resource/template/admin/Api/edit.twig';
            $is_admin = true;
            $scope_key = 'member_flg';
        } else {
            $User = $app['eccube.repository.customer']->find($app->user()->getId());
            $searchConditions = array('Customer' => $User);
            $view = 'EccubeApi/Resource/template/mypage/Api/edit.twig';
            $scope_key = 'customer_flg';
        }

        // Client が保持する Scope の配列を取得する
        $Client = $app['eccube.repository.oauth2.client']->find($client_id);
        $Scopes = array_map(function ($ClientScope) {
            return $ClientScope->getScope();
        }, $app['eccube.repository.oauth2.clientscope']->findBy(array('Client' => $Client)));

        $userInfoConditions = array();
        if ($Client->hasMember()) {
            $userInfoConditions = array('Member' => $Client->getMember());
        } elseif ($Client->hasCustomer()) {
            $userInfoConditions = array('Customer' => $Client->getCustomer());
        }
        $UserInfo = $app['eccube.repository.oauth2.openid.userinfo']->findOneBy($userInfoConditions);
        $PublicKey = $app['eccube.repository.oauth2.openid.public_key']->findOneBy(array('UserInfo' => $UserInfo));

        $builder = $app['form.factory']->createBuilder('api_client', $Client);
        $builder->remove('Scopes');
        $ListScopes = $app['eccube.repository.oauth2.scope']->findBy(array('is_default' => true, $scope_key => 1));
        $builder->add('Scopes', 'entity', array(
                'label' => 'scope',
                'choice_label' => 'label',
                'choice_value' => 'scope',
                'choice_name' => 'scope',
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
                'required' => false,
                'choices' => $ListScopes,
                'class' => 'Plugin\EccubeApi\Entity\OAuth2\Scope'
        ));

        $form = $builder->getForm();

        $form['Scopes']->setData($Scopes);

        if ($PublicKey) {
            $form['public_key']->setData($PublicKey->getPublicKey());
            $form['encryption_algorithm']->setData($PublicKey->getEncryptionAlgorithm());
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $ClientScopes = $app['eccube.repository.oauth2.clientscope']->findBy(array('Client' => $Client));
            foreach ($ClientScopes as $ClientScope) {
                $app['orm.em']->remove($ClientScope);
                $app['orm.em']->flush($ClientScope);
            }

            $Scopes = $form['Scopes']->getData();
            foreach ($Scopes as $Scope) {
                $ClientScope = new \Plugin\EccubeApi\Entity\OAuth2\ClientScope();
                $ClientScope->setClient($Client);
                $ClientScope->setClientId($Client->getId());
                $ClientScope->setScope($Scope);
                $ClientScope->setScopeId($Scope->getId());
                $app['orm.em']->persist($ClientScope);
                $Client->addClientScope($ClientScope);
            }

            $app['orm.em']->flush($Client);
            $app->addSuccess('admin.register.complete', 'admin');
            if ($is_admin) {
                $route = 'admin_setting_system_client_edit';
            } else {
                $route = 'mypage_api_client_edit';
            }
            return $app->redirect(
                $app->url($route,
                          array(
                              'member_id' => $member_id,
                              'client_id' => $client_id
                          )
                )
            );
        }

        return $app->render($view, array(
            'form' => $form->createView(),
            'User' => $User,
            'Client' => $Client,
        ));
    }

    /**
     * APIクライアントを新規作成する.
     *
     * @param Application $app
     * @param Request $request
     * @param integer $member_id \Eccube\Entity\Member の ID
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newClient(Application $app, Request $request, $member_id = null)
    {
        $is_admin = false;
        // ログイン中のユーザーのインスタンスによって処理を切り替える
        if ($app->user() instanceof \Eccube\Entity\Member) {
            $User = $app['eccube.repository.member']->find($member_id);
            $searchConditions = array('Member' => $User);
            $view = 'EccubeApi/Resource/template/admin/Api/lists.twig';
            $is_admin = true;
            $scope_key = 'member_flg';
        } else {
            $User = $app['eccube.repository.customer']->find($app->user()->getId());
            $searchConditions = array('Customer' => $User);
            $view = 'EccubeApi/Resource/template/mypage/Api/lists.twig';
            $scope_key = 'customer_flg';
        }
        $Client = new \Plugin\EccubeApi\Entity\OAuth2\Client();

        $builder = $app['form.factory']->createBuilder('api_client', $Client);
        $builder->remove('Scopes');
        $Scopes = $app['eccube.repository.oauth2.scope']->findBy(array('is_default' => true, $scope_key => 1));
        $builder->add('Scopes', 'entity', array(
                'label' => 'scope',
                'choice_label' => 'label',
                'choice_value' => 'scope',
                'choice_name' => 'scope',
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
                'required' => false,
                'choices' => $Scopes,
                'class' => 'Plugin\EccubeApi\Entity\OAuth2\Scope'
        ));

        $form = $builder->getForm();

        $form['Scopes']->setData($Scopes);
        $form['encryption_algorithm']->setData(self::DEFAULT_ENCRYPTION_ALGORITHM);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $PublicKey = null;
            $UserInfo = $app['eccube.repository.oauth2.openid.userinfo']->findOneBy($searchConditions);
            if (!is_object($UserInfo)) {
                // 該当ユーザーの UserInfo が存在しない場合は生成する
                $UserInfo = new \Plugin\EccubeApi\Entity\OAuth2\OpenID\UserInfo();
                $UserInfo->setAddress(new \Plugin\EccubeApi\Entity\OAuth2\OpenID\UserInfoAddress());
            } else {
                $PublicKey = $app['eccube.repository.oauth2.openid.public_key']->findOneBy(array('UserInfo' => $UserInfo));
            }

            $client_id = sha1(openssl_random_pseudo_bytes(100));
            $client_secret = sha1(openssl_random_pseudo_bytes(100));

            $Client->setClientIdentifier($client_id);
            $Client->setClientSecret($client_secret);

            if ($is_admin) {
                $Client->setMember($User);
            } else {
                $Client->setCustomer($User);
            }
            $app['orm.em']->persist($Client);
            $app['orm.em']->flush($Client);

            $Scopes = $form['Scopes']->getData();
            foreach ($Scopes as $Scope) {
                $ClientScope = new \Plugin\EccubeApi\Entity\OAuth2\ClientScope();
                $ClientScope->setClient($Client);
                $ClientScope->setClientId($Client->getId());
                $ClientScope->setScope($Scope);
                $ClientScope->setScopeId($Scope->getId());
                $app['orm.em']->persist($ClientScope);
                $Client->addClientScope($ClientScope);
            }

            $is_new_public_key = false;
            if (!is_object($PublicKey)) {
                // 該当ユーザーの公開鍵が存在しない場合は生成する. UserInfo と 公開鍵は 1:1 となる.
                $RSAKey = new \phpseclib\Crypt\RSA();
                $is_new_public_key = true;
                $keys = $RSAKey->createKey(2048);
                $PublicKey = new \Plugin\EccubeApi\Entity\OAuth2\OpenID\PublicKey();
                $PublicKey->setPublicKey($keys['publickey']);
                $PublicKey->setPrivateKey($keys['privatekey']);
                $PublicKey->setEncryptionAlgorithm($form['encryption_algorithm']->getData());
                $PublicKey->setUserInfo($UserInfo);

                $RSAKey->loadKey($keys['publickey']);
                $JWK = \JOSE_JWK::encode($RSAKey);
                // 公開鍵の指紋を UserInfo::sub に設定する. Self-Issued ID Token Validation に準拠した形式
                // http://openid-foundation-japan.github.io/openid-connect-core-1_0.ja.html#SelfIssuedValidation
                $UserInfo->setSub($JWK->thumbprint());
            }

            if ($is_admin) {
                $UserInfo->setMember($User);
                $UserInfo->mergeMember();
            } else {
                $UserInfo->setCustomer($User);
                $UserInfo->mergeCustomer();
            }

            if (is_object($UserInfo->getAddress())
                && is_null($UserInfo->getAddress()->getId())) {
                $UserInfoAddress = $UserInfo->getAddress();
                $app['orm.em']->persist($UserInfoAddress);
                $app['orm.em']->flush($UserInfoAddress);
                $UserInfo->setAddress($UserInfoAddress);
            }
            $app['orm.em']->persist($UserInfo);
            if ($is_new_public_key) {
                $app['orm.em']->persist($PublicKey);
            }

            $app['orm.em']->flush();

            if ($is_admin) {
                $app->addSuccess('admin.register.complete', 'admin');
                $route = 'admin_setting_system_client_edit';
            } else {
                $app->addSuccess('admin.register.complete', 'front');
                $route = 'mypage_api_client_edit';
            }
            return $app->redirect(
                $app->url($route,
                          array(
                              'member_id' => $member_id,
                              'client_id' => $Client->getId()
                          )
                )
            );
        }

        if ($is_admin) {
            $view = 'EccubeApi/Resource/template/admin/Api/edit.twig';
        } else {
            $view = 'EccubeApi/Resource/template/mypage/Api/edit.twig';
        }
        return $app->render($view, array(
            'form' => $form->createView(),
            'User' => $User,
            'Client' => $Client,
        ));
    }

    public function delete(Application $app, Request $request, $member_id = null, $client_id = null)
    {
        $this->isTokenValid($app);

        $Client = $app['eccube.repository.oauth2.client']->find($client_id);

        $ClientScopes = $app['eccube.repository.oauth2.clientscope']->findBy(array('client_id' => $Client->getId()));
        foreach ($ClientScopes as $ClientScope) {
            $app['orm.em']->remove($ClientScope);
            $app['orm.em']->flush($ClientScope);
        }
        $RefreshTokens = $app['eccube.repository.oauth2.refresh_token']->findBy(array('client_id' => $Client->getId()));
        foreach ($RefreshTokens as $RefreshToken) {
            $app['orm.em']->remove($RefreshToken);
            $app['orm.em']->flush($RefreshToken);
        }
        $AuthorizationCodes = $app['eccube.repository.oauth2.authorization_code']->findBy(array('client_id' => $Client->getId()));
        foreach ($AuthorizationCodes as $AuthorizationCode) {
            $app['orm.em']->remove($AuthorizationCode);
            $app['orm.em']->flush($AuthorizationCode);
        }
        $AccessTokens = $app['eccube.repository.oauth2.access_token']->findBy(array('client_id' => $Client->getId()));
        foreach ($AccessTokens as $AccessToken) {
            $app['orm.em']->remove($AccessToken);
            $app['orm.em']->flush($AccessToken);
        }
        $app['orm.em']->remove($Client);
        $app['orm.em']->flush($Client);

        if ($app->user() instanceof \Eccube\Entity\Member) {
            $route = 'admin_api_lists';
        } else {
            $route = 'mypage_api_lists';
        }

        return $app->redirect(
            $app->url($route, array('member_id' => $member_id))
        );
    }
}
