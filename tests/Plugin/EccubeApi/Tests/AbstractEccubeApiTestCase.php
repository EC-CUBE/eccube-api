<?php

/*
 * This file is part of the EccubeApi
 *
 * Copyright (C) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\EccubeApi\Tests;

use Eccube\Tests\Web\AbstractWebTestCase;
use Eccube\Application;
use Symfony\Component\Security\Core\User\UserInterface;
use Plugin\EccubeApi\Entity\OAuth2\Client as OAuth2Client;
use Plugin\EccubeApi\Entity\OAuth2\ClientScope;
use Plugin\EccubeApi\Entity\OAuth2\OpenID\UserInfo;
use Plugin\EccubeApi\Entity\OAuth2\OpenID\UserInfoAddress;
use Plugin\EccubeApi\Entity\OAuth2\OpenID\PublicKey;

/**
 * AbstractEccubeApiTestCase
 *
 * @author Kentaro Ohkouchi
 */
class AbstractEccubeApiTestCase extends AbstractWebTestCase
{
    /**
     * Client を生成する.
     */
    public function createApiClient(UserInterface $User, $app_name = null, $client_identifier = null, $client_secret = null, $redirect_uri = null)
    {
        $faker = $this->getFaker();
        if (is_null($app_name)) {
            $app_name = $faker->word;
        }
        if (is_null($client_identifier)) {
            $client_identifier = sha1(openssl_random_pseudo_bytes(100));
        }
        if (is_null($client_secret)) {
            $client_secret = sha1(openssl_random_pseudo_bytes(100));
        }
        if (is_null($redirect_uri)) {
            $redirect_uri = $faker->url;
        }
        $Client = new OAuth2Client();
        if ($User instanceof \Eccube\Entity\Customer) {
            $Client->setCustomer($User);
        } else {
            $Client->setMember($User);
        }

        $Client->setAppName($app_name);
        $Client->setRedirectUri($redirect_uri);
        $Client->setClientIdentifier($client_identifier);
        $Client->setClientSecret($client_secret);
        $this->app['orm.em']->persist($Client);
        $this->app['orm.em']->flush($Client);
        return $Client;
    }

    public function addClientScope(OAuth2Client $Client, $scope)
    {
        $Scope = $this->app['eccube.repository.oauth2.scope']->findOneBy(array('scope' => $scope));
        if (!is_object($Scope)) {
            // Scope is not found.
            return;
        }
        $ClientScope = $this->app['eccube.repository.oauth2.clientscope']->findOneBy(
            array(
                'Scope' => $Scope,
                'Client' => $Client
            )
        );
        if ($ClientScope) {
            // ClientScope is exists.
            return;
        }
        $ClientScope = new ClientScope();
        $ClientScope->setClientId($Client->getId());
        $ClientScope->setClient($Client);
        $ClientScope->setScopeId($Scope->getId());
        $ClientScope->setScope($Scope);
        $this->app['orm.em']->persist($ClientScope);
        $Client->addClientScope($ClientScope);
        $this->app['orm.em']->flush($ClientScope);
    }

    public function createPublicKey(UserInfo $UserInfo = null)
    {
        $RSAKey = new \phpseclib\Crypt\RSA();
        $keys = $RSAKey->createKey(2048);
        $PublicKey = new PublicKey();
        $PublicKey->setPublicKey($keys['publickey']);
        $PublicKey->setPrivateKey($keys['privatekey']);
        $PublicKey->setEncryptionAlgorithm('RS256');

        $RSAKey->loadKey($keys['publickey']);
        $JWK = \JOSE_JWK::encode($RSAKey);

        if (is_object($UserInfo)) {
            $PublicKey->setUserInfo($UserInfo);
            $UserInfo->setSub($JWK->thumbprint());
        }
        $this->app['orm.em']->persist($PublicKey);
        return $PublicKey;
    }

    public function createUserInfo(UserInterface $User) {
        $UserInfo = new UserInfo();
        $UserInfo->setAddress(new UserInfoAddress());
        if ($User instanceof \Eccube\Entity\Customer) {
            $Exists = $this->app['eccube.repository.oauth2.openid.userinfo']->findOneBy(array('Customer' => $User));
            if (is_object($Exists)) {
                return $Exists;
            }
            $UserInfo->setCustomer($User);
            $UserInfo->mergeCustomer();
        } else {
            $Exists = $this->app['eccube.repository.oauth2.openid.userinfo']->findOneBy(array('Member' => $User));
            if (is_object($Exists)) {
                return $Exists;
            }
            $UserInfo->setMember($User);
            $UserInfo->mergeMember();
        }

        $UserInfoAddress = $UserInfo->getAddress();
        $this->app['orm.em']->persist($UserInfoAddress);
        $this->app['orm.em']->flush($UserInfoAddress);
        $UserInfo->setAddress($UserInfoAddress);
        $this->createPublicKey($UserInfo);
        $this->app['orm.em']->persist($UserInfo);
        $this->app['orm.em']->flush($UserInfo);
        return $UserInfo;
    }
}
