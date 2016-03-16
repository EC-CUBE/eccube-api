<?php

/*
 * This file is part of the EccubeApi
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\EccubeApi;

use Eccube\Plugin\AbstractPluginManager;
use Symfony\Component\Filesystem\Filesystem;

class PluginManager extends AbstractPluginManager
{
    /**
     * @var array エンティティクラスの配列
     */
    private $classes;

    public function __construct()
    {
        $this->classes = array(
            '\Plugin\EccubeApi\Entity\OAuth2\AuthorizationCode',
            '\Plugin\EccubeApi\Entity\OAuth2\User',
            '\Plugin\EccubeApi\Entity\OAuth2\Scope',
            '\Plugin\EccubeApi\Entity\OAuth2\OpenID\UserInfo',
            '\Plugin\EccubeApi\Entity\OAuth2\OpenID\PublicKey',
            '\Plugin\EccubeApi\Entity\OAuth2\OpenID\UserInfoAddress',
            '\Plugin\EccubeApi\Entity\OAuth2\RefreshToken',
            '\Plugin\EccubeApi\Entity\OAuth2\AccessToken',
            '\Plugin\EccubeApi\Entity\OAuth2\Client'
        );
    }

    /**
     * プラグインインストール時の処理
     *
     * @param $config
     * @param $app
     * @throws \Exception
     */
    public function install($config, $app)
    {
    }

    /**
     * プラグイン削除時の処理
     *
     * @param $config
     * @param $app
     */
    public function uninstall($config, $app)
    {
        $this->migrationSchema($app, __DIR__.'/Resource/doctrine/migrations', $config['code'], 0);
    }

    /**
     * プラグイン有効時の処理
     *
     * @param $config
     * @param $app
     * @throws \Exception
     */
    public function enable($config, $app)
    {
        $this->migrationSchema($app, __DIR__.'/Resource/doctrine/migrations', $config['code']); // XXX install 時点では namespace を見つけられないため
    }

    /**
     * プラグイン無効時の処理
     *
     * @param $config
     * @param $app
     */
    public function disable($config, $app)
    {
    }

    public function update($config, $app)
    {
    }
}
