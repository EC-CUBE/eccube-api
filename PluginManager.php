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
     * @var string コピー元リソースディレクトリ
     */
    private $origin;
    /**
     * @var string コピー先リソースディレクトリ
     */
    private $target;

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
        // コピー元のディレクトリ
        $this->origin = __DIR__.'/Resource/swagger-ui';
        // XXX html のパスを動的に取得したい
        $this->target = __DIR__.'/../../../html/plugin/api';
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
        // リソースファイルのコピー
        $this->copyAssets();
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
        // リソースファイルの削除
        $this->removeAssets();
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
        $this->migrationSchema($app, __DIR__.'/Resource/doctrine/migrations', $config['code']);
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

    /**
     * リソースファイル等をコピー
     */
    private function copyAssets()
    {
        $file = new Filesystem();
        $file->mirror($this->origin, $this->target.'/assets', null, array('override' => true));
        $file->remove($this->target.'/assets/index.html');
        $file->remove($this->target.'/assets/o2c.html');
    }

    /**
     * コピーしたリソースファイルなどを削除
     */
    private function removeAssets()
    {
        $file = new Filesystem();
        $file->remove($this->target);
    }
}
