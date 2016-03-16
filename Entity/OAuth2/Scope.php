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
     * @var boolean
     */
    private $is_default;


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
     * Set is_default
     *
     * @param boolean $isDefault
     * @return Scope
     */
    public function setIsDefault($isDefault)
    {
        $this->is_default = $isDefault;

        return $this;
    }

    /**
     * Get is_default
     *
     * @return boolean
     */
    public function getIsDefault()
    {
        return $this->is_default;
    }

    public function setDefault($default)
    {
        return $this->setIsDefault($default);
    }

    public function isDefault()
    {
        return $this->getIsDefault();
    }
}
