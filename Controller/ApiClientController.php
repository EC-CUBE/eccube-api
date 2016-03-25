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

    PUBLIC function lists(Application $app, Request $request, $member_id = null)
    {
        $entityManager = $app['orm.em'];
        $clientStorage  = $entityManager->getRepository('Plugin\EccubeApi\Entity\OAuth2\Client');
        $Member = $app['eccube.repository.member']->find($member_id);
        $Clients = $clientStorage->findBy(array('Member' => $Member));

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
        $entityManager = $app['orm.em'];
        $clientStorage  = $entityManager->getRepository('Plugin\EccubeApi\Entity\OAuth2\Client');
        $keyStorage = $entityManager->getRepository('Plugin\EccubeApi\Entity\OAuth2\OpenID\PublicKey');
        $userStorage = $entityManager->getRepository('Plugin\EccubeApi\Entity\OAuth2\OpenID\UserInfo');

        $Member = $app['eccube.repository.member']->find($member_id);
        $Client = $clientStorage->find($client_id);
        $userInfoConditions = array();
        if ($Client->hasMember()) {
            $userInfoConditions = array('Member' => $Client->getMember());
        } elseif ($Client->hasCustomer()) {
            $userInfoConditions = array('Customer' => $Client->getCustomer());
        }
        $UserInfo = $userStorage->findOneBy($userInfoConditions);
        $PublicKey = $keyStorage->findOneBy(array('UserInfo' => $UserInfo));

        $builder = $app['form.factory']->createBuilder('admin_api_client', $Client);
        $form = $builder->getForm();
        $form['scope']->setData('read write openid offline_access'); // TODO
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
        $entityManager = $app['orm.em'];
        $userStorage = $entityManager->getRepository('Plugin\EccubeApi\Entity\OAuth2\OpenID\UserInfo');
        $keyStorage = $entityManager->getRepository('Plugin\EccubeApi\Entity\OAuth2\OpenID\PublicKey');

        $Member = $app['eccube.repository.member']->find($member_id);
        $Client = new \Plugin\EccubeApi\Entity\OAuth2\Client();

        $builder = $app['form.factory']->createBuilder('admin_api_client', $Client);
        $form = $builder->getForm();
        $form['scope']->setData(self::DEFAULT_SCOPE);
        $form['encryption_algorithm']->setData(self::DEFAULT_ENCRYPTION_ALGORITHM);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $PublicKey = null;
            $UserInfo = $userStorage->findOneBy(array('Member' => $Member));
            if (!is_object($UserInfo)) {
                $UserInfo = new \Plugin\EccubeApi\Entity\OAuth2\OpenID\UserInfo();
            } else {
                $PublicKey = $keyStorage->findOneBy(array('UserInfo' => $UserInfo));
            }

            $client_id = sha1(openssl_random_pseudo_bytes(100));
            $client_secret = sha1(openssl_random_pseudo_bytes(100));

            $Client->setClientIdentifier($client_id);
            $Client->setClientSecret($client_secret);
            $Client->setMember($Member);
            $app['orm.em']->persist($Client);

            $is_new_public_key = false;
            if (!is_object($PublicKey)) {
                $is_new_public_key = true;
                $configures = array(
                    'digest_alg' => 'sha256',
                    'private_key_bits' => 2048,
                    'private_key_type' => OPENSSL_KEYTYPE_RSA
                );
                $openssl = openssl_pkey_new($configures);
                openssl_pkey_export($openssl, $privKey, null, $configures);

                $pubKey = openssl_pkey_get_details($openssl);

                $PublicKey = new \Plugin\EccubeApi\Entity\OAuth2\OpenID\PublicKey();
                $PublicKey->setPublicKey($pubKey['key']);
                $PublicKey->setPrivateKey($privKey);
                $PublicKey->setEncryptionAlgorithm($form['encryption_algorithm']->getData());
                $PublicKey->setUserInfo($UserInfo);

                $RSAKey = new \phpseclib\Crypt\RSA();
                $RSAKey->loadKey($pubKey['key']);
                $JWK = \JOSE_JWK::encode($RSAKey);
                $UserInfo->setSub($JWK->thumbprint());
            }

            $UserInfo->setPreferredUsername($Member->getUsername());
            $UserInfo->setMember($Member);
            $UserInfoAdderss = new \Plugin\EccubeApi\Entity\OAuth2\OpenID\UserInfoAddress();
            $app['orm.em']->persist($UserInfoAdderss);
            $app['orm.em']->flush($UserInfoAdderss);
            $UserInfo->setAddress($UserInfoAdderss);
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
