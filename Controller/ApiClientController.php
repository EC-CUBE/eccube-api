<?php

namespace Plugin\EccubeApi\Controller;

use Eccube\Application;
use Symfony\Component\HttpFoundation\Request;
use OAuth2\HttpFoundationBridge\Response as BridgeResponse;
use OAuth2\Encryption\FirebaseJwt as Jwt;

class ApiClientController
{
    const DEFAULT_SCOPE = 'read write openid offline_access';
    const DEFAULT_ENCRYPTION_ALGORITHM = 'RS256';

    public function lists(Application $app, Request $request, $member_id = null)
    {
        $searchConditions = array();
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

    public function edit(Application $app, Request $request, $member_id = null, $client_id = null)
    {
        $is_admin = false;
        if ($app->user() instanceof \Eccube\Entity\Member) {
            $User = $app['eccube.repository.member']->find($member_id);
            $searchConditions = array('Member' => $User);
            $view = 'EccubeApi/Resource/template/admin/Api/edit.twig';
            $is_admin = true;
        } else {
            $User = $app['eccube.repository.customer']->find($app->user()->getId());
            $searchConditions = array('Customer' => $User);
            $view = 'EccubeApi/Resource/template/mypage/Api/edit.twig';
        }

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

        $builder = $app['form.factory']->createBuilder('admin_api_client', $Client);
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

    public function newClient(Application $app, Request $request, $member_id = null)
    {
        $is_admin = false;
        if ($app->user() instanceof \Eccube\Entity\Member) {
            $User = $app['eccube.repository.member']->find($member_id);
            $searchConditions = array('Member' => $User);
            $view = 'EccubeApi/Resource/template/admin/Api/lists.twig';
            $is_admin = true;
        } else {
            $User = $app['eccube.repository.customer']->find($app->user()->getId());
            $searchConditions = array('Customer' => $User);
            $view = 'EccubeApi/Resource/template/mypage/Api/lists.twig';
        }
        $Client = new \Plugin\EccubeApi\Entity\OAuth2\Client();

        $builder = $app['form.factory']->createBuilder('admin_api_client', $Client);
        $form = $builder->getForm();
        $Scopes = $app['eccube.repository.oauth2.scope']->findBy(array('is_default' => true));
        $form['Scopes']->setData($Scopes);
        $form['encryption_algorithm']->setData(self::DEFAULT_ENCRYPTION_ALGORITHM);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $PublicKey = null;
            $UserInfo = $app['eccube.repository.oauth2.openid.userinfo']->findOneBy($searchConditions);
            if (!is_object($UserInfo)) {
                $UserInfo = new \Plugin\EccubeApi\Entity\OAuth2\OpenID\UserInfo();
                $UserInfoAdderss = new \Plugin\EccubeApi\Entity\OAuth2\OpenID\UserInfoAddress();
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
                $UserInfo->setSub($JWK->thumbprint());
            }

            if ($is_admin) {
                $UserInfo->setPreferredUsername($User->getUsername());
                $UserInfo->setMember($User);
            } else {
                $UserInfo->setPreferredUsername($User->getEmail());
                $UserInfo->setCustomer($User);
            }
            if (!is_object($UserInfo->getAddress())) {
                $app['orm.em']->persist($UserInfoAdderss);
                $app['orm.em']->flush($UserInfoAdderss);
                $UserInfo->setAddress($UserInfoAdderss);
            }
            $UserInfo->setUpdatedAt(new \DateTime());
            $app['orm.em']->persist($UserInfo);
            if ($is_new_public_key) {
                $app['orm.em']->persist($PublicKey);
            }

            $app['orm.em']->flush();
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
        // TODO
        return null;
    }
}
