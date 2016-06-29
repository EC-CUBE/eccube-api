<?php

/*
 * This file is part of the EccubeApi
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\EccubeApi\Entity\OAuth2;

use Doctrine\ORM\Mapping as ORM;

/**
 * AuthorizationCode
 */
class AuthorizationCode extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $code;

    /**
     * @var integer
     */
    private $client_id;

    /**
     * @var integer
     */
    private $user_id;

    /**
     * @var \DateTime
     */
    private $expires;

    /**
     * @var string
     */
    private $redirect_uri;

    /**
     * @var string
     */
    private $scope;

    /**
     * @var \Plugin\EccubeApi\Entity\OAuth2\Client
     */
    private $client;

    /**
     * @var \Plugin\EccubeApi\Entity\OAuth2\OpenID\UserInfo
     */
    private $user;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return AuthorizationCode
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set client_id
     *
     * @param integer $clientId
     * @return AuthorizationCode
     */
    public function setClientId($clientId)
    {
        $this->client_id = $clientId;

        return $this;
    }

    /**
     * Get client_id
     *
     * @return integer
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * Set user_id
     *
     * @param integer $userId
     * @return AuthorizationCode
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get user_id
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set expires
     *
     * @param \DateTime $expires
     * @return AuthorizationCode
     */
    public function setExpires($expires)
    {
        $this->expires = $expires;

        return $this;
    }

    /**
     * Get expires
     *
     * @return \DateTime
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * Set redirect_uri
     *
     * @param string $redirectUri
     * @return AuthorizationCode
     */
    public function setRedirectUri($redirectUri)
    {
        $this->redirect_uri = $redirectUri;

        return $this;
    }

    /**
     * Get redirect_uri
     *
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->redirect_uri;
    }

    /**
     * Set scope
     *
     * @param string $scope
     * @return AuthorizationCode
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Get scope
     *
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Set client
     *
     * @param \Plugin\EccubeApi\Entity\OAuth2\Client $client
     * @return AuthorizationCode
     */
    public function setClient(\Plugin\EccubeApi\Entity\OAuth2\Client $client = null)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client
     *
     * @return \Plugin\EccubeApi\Entity\OAuth2\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set user
     *
     * @param \Plugin\EccubeApi\Entity\OAuth2\OpenID\UserInfo $user
     * @return AuthorizationCode
     */
    public function setUser(\Plugin\EccubeApi\Entity\OAuth2\OpenID\UserInfo $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Plugin\EccubeApi\Entity\OAuth2\OpenID\UserInfo
     */
    public function getUser()
    {
        return $this->user;
    }
    /**
     * @var string
     */
    private $id_token;


    /**
     * Set id_token
     *
     * @param string $idToken
     * @return AuthorizationCode
     */
    public function setIdToken($idToken)
    {
        $this->id_token = $idToken;

        return $this;
    }

    /**
     * Get id_token
     *
     * @return string
     */
    public function getIdToken()
    {
        return $this->id_token;
    }
}
