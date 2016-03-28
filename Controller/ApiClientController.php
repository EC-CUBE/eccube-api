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
        $Member = $app['eccube.repository.member']->find($member_id);
        $Clients = $app['eccube.repository.oauth2.client']->findBy(array('Member' => $Member));

        $builder = $app['form.factory']->createBuilder();
        $form = $builder->getForm();

        return $app->render('EccubeApi/Resource/template/admin/Api/lists.twig', array(
            'form' => $form->createView(),
            'Member' => $Member,
            'Clients' => $Clients,
        ));
    }

    public function edit(Application $app, Request $request, $member_id = null, $client_id = null)
    {
        $Member = $app['eccube.repository.member']->find($member_id);
        $Client = $app['eccube.repository.oauth2.client']->find($client_id);
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
        $form['scope']->setData(self::DEFAULT_SCOPE); // TODO
        if ($PublicKey) {
            $form['public_key']->setData($PublicKey->getPublicKey());
            $form['encryption_algorithm']->setData($PublicKey->getEncryptionAlgorithm());
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $app['orm.em']->flush($Client);
            $app->addSuccess('admin.register.complete', 'admin');
            return $app->redirect(
                $app->url('admin_setting_system_client_edit',
                          array(
                              'member_id' => $member_id,
                              'client_id' => $client_id
                          )
                )
            );
        }

        return $app->render('EccubeApi/Resource/template/admin/Api/edit.twig', array(
            'form' => $form->createView(),
            'Member' => $Member,
            'Client' => $Client,
        ));
    }

    public function newClient(Application $app, Request $request, $member_id = null)
    {
        $Member = $app['eccube.repository.member']->find($member_id);
        $Client = new \Plugin\EccubeApi\Entity\OAuth2\Client();

        $builder = $app['form.factory']->createBuilder('admin_api_client', $Client);
        $form = $builder->getForm();
        $form['scope']->setData(self::DEFAULT_SCOPE); // TODO
        $form['encryption_algorithm']->setData(self::DEFAULT_ENCRYPTION_ALGORITHM);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $PublicKey = null;
            $UserInfo = $app['eccube.repository.oauth2.openid.userinfo']->findOneBy(array('Member' => $Member));
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
            $Client->setMember($Member);
            $app['orm.em']->persist($Client);

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

            $UserInfo->setPreferredUsername($Member->getUsername());
            $UserInfo->setMember($Member);
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
            return $app->redirect(
                $app->url('admin_setting_system_client_edit',
                          array(
                              'member_id' => $member_id,
                              'client_id' => $Client->getId()
                          )
                )
            );
        }

        return $app->render('EccubeApi/Resource/template/admin/Api/edit.twig', array(
            'form' => $form->createView(),
            'Member' => $Member,
            'Client' => $Client,
        ));
    }
    public function delete(Application $app, Request $request, $member_id = null, $client_id = null)
    {
        // TODO
        return null;
    }
}
