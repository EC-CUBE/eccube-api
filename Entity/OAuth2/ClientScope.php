<?php

namespace Plugin\EccubeApi\Entity\OAuth2;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientScope
 */
class ClientScope extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     */
    private $client_id;

    /**
     * @var integer
     */
    private $scope_id;

    /**
     * @var \Plugin\EccubeApi\Entity\OAuth2\Client
     */
    private $Client;

    /**
     * @var \Plugin\EccubeApi\Entity\OAuth2\Scope
     */
    private $Scope;


    /**
     * Set client_id
     *
     * @param integer $clientId
     * @return ClientScope
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
     * Set scope_id
     *
     * @param integer $scopeId
     * @return ClientScope
     */
    public function setScopeId($scopeId)
    {
        $this->scope_id = $scopeId;

        return $this;
    }

    /**
     * Get scope_id
     *
     * @return integer
     */
    public function getScopeId()
    {
        return $this->scope_id;
    }

    /**
     * Set Client
     *
     * @param \Plugin\EccubeApi\Entity\OAuth2\Client $client
     * @return ClientScope
     */
    public function setClient(\Plugin\EccubeApi\Entity\OAuth2\Client $client = null)
    {
        $this->Client = $client;

        return $this;
    }

    /**
     * Get Client
     *
     * @return \Plugin\EccubeApi\Entity\OAuth2\Client
     */
    public function getClient()
    {
        return $this->Client;
    }

    /**
     * Set Scope
     *
     * @param \Plugin\EccubeApi\Entity\OAuth2\Scope $scope
     * @return ClientScope
     */
    public function setScope(\Plugin\EccubeApi\Entity\OAuth2\Scope $scope = null)
    {
        $this->Scope = $scope;

        return $this;
    }

    /**
     * Get Scope
     *
     * @return \Plugin\EccubeApi\Entity\OAuth2\Scope
     */
    public function getScope()
    {
        return $this->Scope;
    }
}
