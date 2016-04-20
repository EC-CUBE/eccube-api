<?php

namespace Plugin\EccubeApi\Entity\OAuth2\OpenID;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserInfo
 */
class UserInfo extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $sub;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $given_name;

    /**
     * @var string
     */
    private $family_name;

    /**
     * @var string
     */
    private $middle_name;

    /**
     * @var string
     */
    private $nickname;

    /**
     * @var string
     */
    private $preferred_username;

    /**
     * @var string
     */
    private $profile;

    /**
     * @var string
     */
    private $picture;

    /**
     * @var string
     */
    private $website;

    /**
     * @var string
     */
    private $email;

    /**
     * @var boolean
     */
    private $email_verified = false;

    /**
     * @var string
     */
    private $gender;

    /**
     * @var \DateTime
     */
    private $birthdate;

    /**
     * @var string
     */
    private $zoneinfo;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $phone_number;

    /**
     * @var boolean
     */
    private $phone_number_verified = false;

    /**
     * @var \DateTime
     */
    private $updated_at;

    /**
     * @var \Plugin\EccubeApi\Entity\OAuth2\OpenID\UserInfoAddress
     */
    private $address;

    /**
     * @var \Eccube\Entity\Customer
     */
    private $Customer;

    /**
     * @var \Eccube\Entity\Member
     */
    private $Member;


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
     * Set sub
     *
     * @param string $sub
     * @return UserInfo
     */
    public function setSub($sub)
    {
        $this->sub = $sub;

        return $this;
    }

    /**
     * Get sub
     *
     * @return string
     */
    public function getSub()
    {
        return $this->sub;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return UserInfo
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set given_name
     *
     * @param string $givenName
     * @return UserInfo
     */
    public function setGivenName($givenName)
    {
        $this->given_name = $givenName;

        return $this;
    }

    /**
     * Get given_name
     *
     * @return string
     */
    public function getGivenName()
    {
        return $this->given_name;
    }

    /**
     * Set family_name
     *
     * @param string $familyName
     * @return UserInfo
     */
    public function setFamilyName($familyName)
    {
        $this->family_name = $familyName;

        return $this;
    }

    /**
     * Get family_name
     *
     * @return string
     */
    public function getFamilyName()
    {
        return $this->family_name;
    }

    /**
     * Set middle_name
     *
     * @param string $middleName
     * @return UserInfo
     */
    public function setMiddleName($middleName)
    {
        $this->middle_name = $middleName;

        return $this;
    }

    /**
     * Get middle_name
     *
     * @return string
     */
    public function getMiddleName()
    {
        return $this->middle_name;
    }

    /**
     * Set nickname
     *
     * @param string $nickname
     * @return UserInfo
     */
    public function setNickname($nickname)
    {
        $this->nickname = $nickname;

        return $this;
    }

    /**
     * Get nickname
     *
     * @return string
     */
    public function getNickname()
    {
        return $this->nickname;
    }

    /**
     * Set preferred_username
     *
     * @param string $preferredUsername
     * @return UserInfo
     */
    public function setPreferredUsername($preferredUsername)
    {
        $this->preferred_username = $preferredUsername;

        return $this;
    }

    /**
     * Get preferred_username
     *
     * @return string
     */
    public function getPreferredUsername()
    {
        return $this->preferred_username;
    }

    /**
     * Set profile
     *
     * @param string $profile
     * @return UserInfo
     */
    public function setProfile($profile)
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * Get profile
     *
     * @return string
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * Set picture
     *
     * @param string $picture
     * @return UserInfo
     */
    public function setPicture($picture)
    {
        $this->picture = $picture;

        return $this;
    }

    /**
     * Get picture
     *
     * @return string
     */
    public function getPicture()
    {
        return $this->picture;
    }

    /**
     * Set website
     *
     * @param string $website
     * @return UserInfo
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Get website
     *
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return UserInfo
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email_verified
     *
     * @param boolean $emailVerified
     * @return UserInfo
     */
    public function setEmailVerified($emailVerified)
    {
        $this->email_verified = $emailVerified;

        return $this;
    }

    /**
     * Get email_verified
     *
     * @return boolean
     */
    public function getEmailVerified()
    {
        return $this->email_verified;
    }

    /**
     * Set gender
     *
     * @param string $gender
     * @return UserInfo
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set birthdate
     *
     * @param \DateTime $birthdate
     * @return UserInfo
     */
    public function setBirthdate($birthdate)
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    /**
     * Get birthdate
     *
     * @return \DateTime
     */
    public function getBirthdate()
    {
        return $this->birthdate;
    }

    public function getBirthdateAsString()
    {
        if ($this->getBirthdate() instanceof \Datetime) {
            return $this->getBirthdate()->format('Y-m-d');
        }
        return null;
    }

    /**
     * Set zoneinfo
     *
     * @param string $zoneinfo
     * @return UserInfo
     */
    public function setZoneinfo($zoneinfo)
    {
        $this->zoneinfo = $zoneinfo;

        return $this;
    }

    /**
     * Get zoneinfo
     *
     * @return string
     */
    public function getZoneinfo()
    {
        return $this->zoneinfo;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return UserInfo
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set phone_number
     *
     * @param string $phoneNumber
     * @return UserInfo
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phone_number = $phoneNumber;

        return $this;
    }

    /**
     * Get phone_number
     *
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phone_number;
    }

    /**
     * Set phone_number_verified
     *
     * @param boolean $phoneNumberVerified
     * @return UserInfo
     */
    public function setPhoneNumberVerified($phoneNumberVerified)
    {
        $this->phone_number_verified = $phoneNumberVerified;

        return $this;
    }

    /**
     * Get phone_number_verified
     *
     * @return boolean
     */
    public function getPhoneNumberVerified()
    {
        return $this->phone_number_verified;
    }

    /**
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     * @return UserInfo
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated_at
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    public function getUpdatedAtAsString()
    {
        if ($this->getUpdatedAt() instanceof \DateTime) {
            $updatedAt = $this->getUpdatedAt();
            $updatedAt->setTimeZone(new \DateTimeZone('UTC'));
            return $updatedAt->format(\DateTime::RFC3339);
        }
        return null;
    }

    /**
     * Set address
     *
     * @param \Plugin\EccubeApi\Entity\OAuth2\OpenID\UserInfoAddress $address
     * @return UserInfo
     */
    public function setAddress(\Plugin\EccubeApi\Entity\OAuth2\OpenID\UserInfoAddress $address = null)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return \Plugin\EccubeApi\Entity\OAuth2\OpenID\UserInfoAddress
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set Customer
     *
     * @param \Eccube\Entity\Customer $customer
     * @return UserInfo
     */
    public function setCustomer(\Eccube\Entity\Customer $customer = null)
    {
        $this->Customer = $customer;

        return $this;
    }

    /**
     * Get Customer
     *
     * @return \Eccube\Entity\Customer
     */
    public function getCustomer()
    {
        return $this->Customer;
    }

    /**
     * Set Member
     *
     * @param \Eccube\Entity\Member $member
     * @return UserInfo
     */
    public function setMember(\Eccube\Entity\Member $member = null)
    {
        $this->Member = $member;

        return $this;
    }

    /**
     * Get Member
     *
     * @return \Eccube\Entity\Member
     */
    public function getMember()
    {
        return $this->Member;
    }

    /**
     * Customer の内容を自分自身にマージします.
     */
    public function mergeCustomer()
    {
        $c = null;
        if (is_object($this->getCustomer())) {
            $c = $this->getCustomer();
        } else {
            return;
        }
        // TODO locale によって表示順を変更する
        $this->setName($c->getName01().' '.$c->getName02());
        $this->setGivenName($c->getName02());
        $this->setFamilyName($c->getName01());
        $this->setMiddleName(null);
        $this->setNickname(null);
        $this->setPreferredUsername($c->getEmail());
        $this->setProfile(null);
        $this->setPicture(null);
        $this->setWebsite(null);
        $this->setEmail($c->getEmail());
        if (is_object($c->getStatus()) && $c->getStatus()->getId() === \Eccube\Entity\Master\CustomerStatus::ACTIVE) {
            $this->setEmailVerified(true);
        } else {
            $this->setEmailVerified(false);
        }
        // TODO magic number
        if (is_object($c->getSex()) && $c->getSex()->getId() === 1) {
            $this->setGender('male');
        } elseif (is_object($c->getSex()) && $c->getSex()->getId() === 2)  {
            $this->setGender('female');
        } else {
            $this->setGender(null);
        }
        $this->setBirthdate($c->getBirth());
        $this->setZoneinfo(null);
        $this->setLocale(null);
        $this->setPhoneNumber($c->getTel01().'-'.$c->getTel02().'-'.$c->getTel03());
        $this->setPhoneNumberVerified(false);
        $UserInfoAddress = $this->getAddress();
        if (!is_object($UserInfoAddress)) {
            $UserInfoAddress = new UserInfoAddress();
        }
        if (is_object($c->getCountry())) {
            $UserInfoAddress->setCountry($c->getCountry()->getName());
        }
        $UserInfoAddress->setPostalCode($c->getZip01().'-'.$c->getZip02());
        if (is_object($c->getPref())) {
            $UserInfoAddress->setRegion($c->getPref()->getName());
        }
        $UserInfoAddress->setLocality($c->getAddr01());
        $UserInfoAddress->setStreetAddress($c->getAddr02());
        $formatted = $UserInfoAddress->getPostalCode().PHP_EOL
            .$UserInfoAddress->getRegion()
            .$UserInfoAddress->getLocality()
            .$UserInfoAddress->getStreetAddress();
        $UserInfoAddress->setFormatted($formatted);
        $this->setAddress($UserInfoAddress);
        $this->setUpdatedAt($c->getUpdateDate());
    }

    /**
     * Member の内容を自分自身にマージします.
     */
    public function mergeMember()
    {
        $m = null;
        if (is_object($this->getMember())) {
            $m = $this->getMember();
        } else {
            return;
        }

        $this->setPreferredUsername($m->getUsername());
        $this->setUpdatedAt($m->getUpdateDate());
    }

    public function toArrayByClaims($claims = null)
    {
        $Results = array();
        switch ($claims) {
            case 'profile':
                $Results = array(
                    'name' => $this->getName(),
                    'family_name' => $this->getFamilyName(),
                    'given_name' => $this->getGivenName(),
                    'middle_name' => $this->getMiddleName(),
                    'nickname' => $this->getNickName(),
                    'preferred_username' => $this->getPreferredUsername(),
                    'profile' => $this->getProfile(),
                    'picture' => $this->getPicture(),
                    'website' => $this->getWebsite(),
                    'gender' => $this->getGender(),
                    'birthdate' => $this->getBirthdateAsString(),
                    'zoneinfo' => $this->getZoneinfo(),
                    'locale' => $this->getLocale(),
                    'updated_at' => $this->getUpdatedAtAsString()
                );
                break;

            case 'email':
                $Results = array(
                    'email' => $this->getEmail(),
                    'email_verified' => $this->getEmailVerified()
                );
                break;

            case 'address':
                $Results = array(
                    'address' => $this->getAddress()->toArray(
                        array('id', '__initializer__', '__cloner__', '__isInitialized__', 'lazyPropertiesDefaults')
                    )
                );
                break;

            case 'phone':
                $Results = array(
                    'phone_number' => $this->getPhoneNumber(),
                    'phone_number_verified' => $this->getPhoneNumberVerified()
                );
                break;

            default:
                $Results = array(
                    'sub' => $this->getSub()
                );
        }
        return $Results;
    }
}
