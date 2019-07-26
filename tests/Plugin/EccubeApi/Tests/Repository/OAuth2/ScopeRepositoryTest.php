<?php

/*
 * This file is part of the EccubeApi
 *
 * Copyright (C) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\EccubeApi\Tests\Repository\OAuth2;

use Eccube\Application;
use Plugin\EccubeApi\Tests\AbstractEccubeApiTestCase;


/**
 * ScopeRepositoryTest test cases.
 *
 * @author Kentaro Ohkouchi
 */
class ScopeRepositoryTest extends AbstractEccubeApiTestCase
{

    public function setUp()
    {
        parent::setUp();
        $ClientScopes = $this->app['eccube.repository.oauth2.clientscope']->findAll();
        foreach ($ClientScopes as $ClientScope) {
            $this->app['orm.em']->remove($ClientScope);
            $this->app['orm.em']->flush($ClientScope);
        }

        $Scopes = $this->app['eccube.repository.oauth2.scope']->findAll();
        foreach ($Scopes as $Scope) {
            $this->app['orm.em']->remove($Scope);
            $this->app['orm.em']->flush($Scope);
        }

        $scopeNames = array(
            'scope_a' => 'スコープA',
            'scope_b' => 'スコープB',
            'scope_c' => 'スコープC'
        );

        foreach ($scopeNames as $name => $label) {
            $Scope = $this->createScope($name, $label);
            if ($name != 'scope_a') {
                $Scope->setDefault(true);
            } else {
                $Scope->setDefault(false);
            }
            $this->app['orm.em']->flush($Scope);
        }
    }

    public function testScopeExists()
    {
        $scope = 'scope_a scope_b';
        $this->expected = true;
        $this->actual = $this->app['eccube.repository.oauth2.scope']->scopeExists($scope);
        $this->verify();
    }

    public function testScopeExistsWithFalse()
    {
        $scope = 'scope_n';
        $this->expected = false;
        $this->actual = $this->app['eccube.repository.oauth2.scope']->scopeExists($scope);
        $this->verify();
    }

    public function testScopeExistsWithNull()
    {
        $scope = null;
        $this->expected = false;
        $this->actual = $this->app['eccube.repository.oauth2.scope']->scopeExists($scope);
        $this->verify();
    }

    public function testScopeExistsWithSingle()
    {
        $scope = 'scope_c';
        $this->expected = true;
        $this->actual = $this->app['eccube.repository.oauth2.scope']->scopeExists($scope);
        $this->verify();
    }

    public function testDefaultScope()
    {
        $this->expected = 'scope_b scope_c';
        $this->actual = $this->app['eccube.repository.oauth2.scope']->getDefaultScope();
        $this->verify();

        // with parameter
        $this->actual = $this->app['eccube.repository.oauth2.scope']->getDefaultScope(1111);
        $this->verify();
    }

    public function testDefaultScopeWithNotfound()
    {
        $Scopes = $this->app['eccube.repository.oauth2.scope']->findAll();
        foreach ($Scopes as $Scope) {
            $Scope->setDefault(false);
            $this->app['orm.em']->flush($Scope);
        }

        $this->expected = null;
        $this->actual = $this->app['eccube.repository.oauth2.scope']->getDefaultScope();
        $this->verify();
    }

    public function testFindByStringWithCustomer()
    {
        $scopeNames = array(
            'customer_1' => '会員1',
            'customer_2' => '会員2',
            'customer_3' => '会員3'
        );
        $Customer = $this->createCustomer();

        foreach ($scopeNames as $name => $label) {
            $Scope = $this->createScope($name, $label);
            $Scope->setCustomerFlg(1);
            $this->app['orm.em']->flush($Scope);
        }

        $scope_required = 'customer_3 customer_1';

        $Results = $this->app['eccube.repository.oauth2.scope']->findByString($scope_required, $Customer);
        $this->expected = 2;
        $this->actual = count($Results);
        $this->verify();

        foreach ($Results as $Scope) {
            $this->assertTrue(in_array($Scope->getScope(), explode(' ', $scope_required)),
                              $scope_required.' に '.$Scope->getScope().' が見つかりません');
        }
    }

    public function testFindByStringWithMember()
    {
        $scopeNames = array(
            'member_1' => '会員1',
            'member_2' => '会員2',
            'member_3' => '会員3'
        );
        $Member = $this->app['eccube.repository.member']->find(2);

        foreach ($scopeNames as $name => $label) {
            $Scope = $this->createScope($name, $label);
            $Scope->setMemberFlg(1);
            $this->app['orm.em']->flush($Scope);
        }

        $scope_required = 'member_3 member_1';

        $Results = $this->app['eccube.repository.oauth2.scope']->findByString($scope_required, $Member);
        $this->expected = 2;
        $this->actual = count($Results);
        $this->verify();

        foreach ($Results as $Scope) {
            $this->assertTrue(in_array($Scope->getScope(), explode(' ', $scope_required)),
                              $scope_required.' に '.$Scope->getScope().' が見つかりません');
        }
    }

    /**
     * @param string $name
     * @param string $label
     * @return \Plugin\EccubeApi\Entity\OAuth2\Scope
     */
    protected function createScope($name, $label)
    {
        $Scope = new \Plugin\EccubeApi\Entity\OAuth2\Scope();
        $Scope->setLabel($label);
        $Scope->setScope($name);
        $Scope->setDefault(true);
        $this->app['orm.em']->persist($Scope);
        $this->app['orm.em']->flush($Scope);
        return $Scope;
    }
}
