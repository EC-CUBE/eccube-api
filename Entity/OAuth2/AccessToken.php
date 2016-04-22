<?php

namespace Plugin\EccubeApi\Entity\OAuth2;

use Doctrine\ORM\Mapping as ORM;

/**
 * AccessToken
 *
 * @link http://bshaffer.github.io/oauth2-server-php-docs/cookbook/doctrine2/
 */
class AccessToken extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $token;

    /**
     * @var integer
     */
    private $client_id;

    /**
     * @var string
     */
    private $user_id;

    /**
     * @var \DateTime
     */
    private $expires;

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
     * Set token
     *
     * @param string $token
     * @return AccessToken
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set client_id
     *
     * @param integer $clientId
     * @return AccessToken
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
     * @return AccessToken
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
     * @return AccessToken
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
     * Set scope
     *
     * @param string $scope
     * @return AccessToken
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
     * @return AccessToken
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
     * @return AccessToken
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
}
