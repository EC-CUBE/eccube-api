<?php

/*
 * This file is part of the EccubeApi
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\EccubeApi\Entity\OAuth2\OpenID;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserInfoAddress
 */
class UserInfoAddress extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $formatted;

    /**
     * @var string
     */
    private $street_address;

    /**
     * @var string
     */
    private $locality;

    /**
     * @var string
     */
    private $region;

    /**
     * @var string
     */
    private $postal_code;

    /**
     * @var string
     */
    private $country;


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
     * Set formatted
     *
     * @param string $formatted
     * @return UserInfoAddress
     */
    public function setFormatted($formatted)
    {
        $this->formatted = $formatted;

        return $this;
    }

    /**
     * Get formatted
     *
     * @return string
     */
    public function getFormatted()
    {
        return $this->formatted;
    }

    /**
     * Set street_address
     *
     * @param string $streetAddress
     * @return UserInfoAddress
     */
    public function setStreetAddress($streetAddress)
    {
        $this->street_address = $streetAddress;

        return $this;
    }

    /**
     * Get street_address
     *
     * @return string
     */
    public function getStreetAddress()
    {
        return $this->street_address;
    }

    /**
     * Set locality
     *
     * @param string $locality
     * @return UserInfoAddress
     */
    public function setLocality($locality)
    {
        $this->locality = $locality;

        return $this;
    }

    /**
     * Get locality
     *
     * @return string
     */
    public function getLocality()
    {
        return $this->locality;
    }

    /**
     * Set region
     *
     * @param string $region
     * @return UserInfoAddress
     */
    public function setRegion($region)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * Get region
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Set postal_code
     *
     * @param string $postalCode
     * @return UserInfoAddress
     */
    public function setPostalCode($postalCode)
    {
        $this->postal_code = $postalCode;

        return $this;
    }

    /**
     * Get postal_code
     *
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postal_code;
    }

    /**
     * Set country
     *
     * @param string $country
     * @return UserInfoAddress
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }
}
