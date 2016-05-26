<?php

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
            $Scope = new \Plugin\EccubeApi\Entity\OAuth2\Scope();
            $Scope->setLabel($label);
            $Scope->setScope($name);
            if ($name != 'scope_a') {
                $Scope->setDefault(true);
            } else {
                $Scope->setDefault(false);
            }
            $this->app['orm.em']->persist($Scope);
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
}
