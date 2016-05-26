<?php
namespace Plugin\EccubeApi\Tests\Repository\OAuth2\OpenID;

use Plugin\EccubeApi\Tests\AbstractEccubeApiTestCase;

/**
 * UserInfoRepositoryRepository test cases.
 *
 * @author Kentaro Ohkouchi
 */
class UserInfoRepositoryTest extends AbstractEccubeApiTestCase
{
    protected $Customer;
    protected $UserInfo;

    public function setUp()
    {
        parent::setUp();

        $this->Customer = $this->createCustomer();
        $this->UserInfo = $this->createUserInfo($this->Customer);
    }

    public function testGetUserClaims()
    {
        $Claims = $this->app['eccube.repository.oauth2.openid.userinfo']->getUserClaims($this->UserInfo->getId(), null);
        $this->expected = true;
        $this->actual = is_array($Claims);
        $this->verify();

        $this->expected = $this->UserInfo->getSub();
        $this->actual = $Claims['sub'];
        $this->assertNotNull($this->actual);
        $this->verify();
    }

    public function testGetUserClaimsWithProfile()
    {
        $Claims = $this->app['eccube.repository.oauth2.openid.userinfo']->getUserClaims($this->UserInfo->getId(), 'profile');
        $this->expected = true;
        $this->actual = is_array($Claims);
        $this->verify();

        $this->verifyProfile($Claims);
    }

    public function testGetUserClaimsWithEmail()
    {
        $Claims = $this->app['eccube.repository.oauth2.openid.userinfo']->getUserClaims($this->UserInfo->getId(), 'email');
        $this->expected = true;
        $this->actual = is_array($Claims);
        $this->verify();

        $this->verifyEmail($Claims);
    }

    public function testGetUserClaimsWithAddress()
    {
        $Claims = $this->app['eccube.repository.oauth2.openid.userinfo']->getUserClaims($this->UserInfo->getId(), 'address');
        $this->expected = true;
        $this->actual = is_array($Claims);
        $this->verify();

        $this->verifyAddress($Claims);
    }

    public function testGetUserClaimsWithPhone()
    {
        $Claims = $this->app['eccube.repository.oauth2.openid.userinfo']->getUserClaims($this->UserInfo->getId(), 'phone');
        $this->expected = true;
        $this->actual = is_array($Claims);
        $this->verify();

        $this->verifyPhone($Claims);
    }

    public function testGetUserClaimsWithMixed()
    {
        $Claims = $this->app['eccube.repository.oauth2.openid.userinfo']->getUserClaims($this->UserInfo->getId(), 'phone address');
        $this->expected = true;
        $this->actual = is_array($Claims);
        $this->verify();

        $this->verifyPhone($Claims);

        $this->verifyAddress($Claims);
    }

    protected function verifyProfile($Claims)
    {
        $this->expected = $this->UserInfo->getFamilyName();
        $this->actual = $Claims['family_name'];
        $this->verify();

        $this->expected = $this->UserInfo->getName();
        $this->actual = $Claims['name'];
        $this->verify();

        $this->expected = $this->UserInfo->getGivenName();
        $this->actual = $Claims['given_name'];
        $this->verify();

        $this->expected = $this->UserInfo->getMiddleName();
        $this->actual = $Claims['middle_name'];
        $this->verify();

        $this->expected = $this->UserInfo->getNickName();
        $this->actual = $Claims['nickname'];
        $this->verify();

        $this->expected = $this->UserInfo->getPreferredUsername();
        $this->actual = $Claims['preferred_username'];
        $this->verify();

        $this->expected = $this->UserInfo->getProfile();
        $this->actual = $Claims['profile'];
        $this->verify();

        $this->expected = $this->UserInfo->getPicture();
        $this->actual = $Claims['picture'];
        $this->verify();

        $this->expected = $this->UserInfo->getWebsite();
        $this->actual = $Claims['website'];
        $this->verify();

        $this->expected = $this->UserInfo->getGender();
        $this->actual = $Claims['gender'];
        $this->verify();

        $this->expected = $this->UserInfo->getBirthdateAsString();
        $this->actual = $Claims['birthdate'];
        $this->verify();

        $this->expected = $this->UserInfo->getZoneinfo();
        $this->actual = $Claims['zoneinfo'];
        $this->verify();

        $this->expected = $this->UserInfo->getLocale();
        $this->actual = $Claims['locale'];
        $this->verify();

        $this->expected = $this->UserInfo->getUpdatedAtAsString();
        $this->actual = $Claims['updated_at'];
        $this->verify();
    }

    protected function verifyEmail($Claims)
    {
        $this->expected = $this->UserInfo->getEmail();
        $this->actual = $Claims['email'];
        $this->verify();

        $this->expected = $this->UserInfo->getEmailVerified();
        $this->actual = $Claims['email_verified'];
        $this->verify();
    }

    protected function verifyAddress($Claims)
    {
        $Address = $this->UserInfo->getAddress();

        $this->expected = $Address->getCountry();
        $this->actual = $Claims['address']['country'];
        $this->verify();

        $this->expected = $Address->getPostalCode();
        $this->actual = $Claims['address']['postal_code'];
        $this->verify();

        $this->expected = $Address->getRegion();
        $this->actual = $Claims['address']['region'];
        $this->verify();

        $this->expected = $Address->getLocality();
        $this->actual = $Claims['address']['locality'];
        $this->verify();

        $this->expected = $Address->getStreetAddress();
        $this->actual = $Claims['address']['street_address'];
        $this->verify();

        $this->expected = $Address->getFormatted();
        $this->actual = $Claims['address']['formatted'];
        $this->verify();
    }

    protected function verifyPhone($Claims)
    {
        $this->expected = $this->UserInfo->getPhoneNumber();
        $this->actual = $Claims['phone_number'];
        $this->verify();

        $this->expected = $this->UserInfo->getPhoneNumberVerified();
        $this->actual = $Claims['phone_number_verified'];
        $this->verify();
    }

    public function testGetUserClaimsWithMember()
    {
        $Member = $this->app['eccube.repository.member']->find(2);
        $this->UserInfo = $this->createUserInfo($Member);
        $Claims = $this->app['eccube.repository.oauth2.openid.userinfo']->getUserClaims($this->UserInfo->getId(), 'profile');
        $this->expected = true;
        $this->actual = is_array($Claims);
        $this->verify();

        $this->verifyProfile($Claims);

        $this->expected = $Member->getUsername();
        $this->actual = $Claims['preferred_username'];
        $this->verify();
    }
}
