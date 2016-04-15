<?php

namespace Plugin\EccubeApi\Entity\OAuth2;

use Doctrine\ORM\Mapping as ORM;

/**
 * Scope
 */
class Scope extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $scope;

    /**
     * @var string
     */
    private $label;

    /**
     * @var boolean
     */
    private $is_default;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $ClientScope;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ClientScope = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Set scope
     *
     * @param string $scope
     * @return Scope
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
     * Set label
     *
     * @param string $label
     * @return Scope
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set is_default
     *
     * @param boolean $isDefault
     * @return Scope
     */
    public function setDefault($isDefault)
    {
        $this->is_default = $isDefault;

        return $this;
    }

    /**
     * Get is_default
     *
     * @return boolean
     */
    public function getDefault()
    {
        return $this->is_default;
    }

    public function isDefault()
    {
        return $this->is_default;
    }

    /**
     * Add ClientScope
     *
     * @param \Plugin\EccubeApi\Entity\OAuth2\ClientScope $clientScope
     * @return Scope
     */
    public function addClientScope(\Plugin\EccubeApi\Entity\OAuth2\ClientScope $clientScope)
    {
        $this->ClientScope[] = $clientScope;

        return $this;
    }

    /**
     * Remove ClientScope
     *
     * @param \Plugin\EccubeApi\Entity\OAuth2\ClientScope $clientScope
     */
    public function removeClientScope(\Plugin\EccubeApi\Entity\OAuth2\ClientScope $clientScope)
    {
        $this->ClientScope->removeElement($clientScope);
    }

    /**
     * Get ClientScope
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getClientScope()
    {
        return $this->ClientScope;
    }
}
