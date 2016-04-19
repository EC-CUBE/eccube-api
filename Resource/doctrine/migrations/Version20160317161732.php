<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160317161732 extends AbstractMigration
{

    const AUTHORIZATION_CODE = 'AuthorizationCode';
    const SCOPE = 'Scope';
    const USERINFO = 'OpenID\UserInfo';
    const USERINFO_ADDRESS = 'OpenID\UserInfoAddress';
    const PUBLIC_KEY = 'OpenID\PublicKey';
    const REFRESH_TOKEN = 'RefreshToken';
    const ACCESS_TOKEN = 'AccessToken';
    const USER = 'User';
    const CLIENT = 'Client';
    const CLIENT_SCOPE = 'ClientScope';

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $classes = array(
            self::AUTHORIZATION_CODE,
            self::SCOPE,
            self::USERINFO,
            self::USERINFO_ADDRESS,
            self::PUBLIC_KEY,
            self::REFRESH_TOKEN,
            self::ACCESS_TOKEN,
            self::USER,
            self::CLIENT,
            self::CLIENT_SCOPE
        );

        // this up() migration is auto-generated, please modify it to your needs
        $app = \Eccube\Application::getInstance();
        $em = $app['orm.em'];
        foreach ($classes as $class) {
            $metadatas[] = $em->getMetadataFactory()->getMetadataFor('\\Plugin\\EccubeApi\\Entity\\OAuth2\\'.$class);
        }
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $schemaTool->createSchema($metadatas);

        $scopes = array(
            'read' => '読み込み',
            'write' => '書き込み',
            'openid' => 'OpenID Connect 認証',
            'offline_access' => 'オフラインアクセス',
            'profile' => 'プロフィール情報',
            'email' => 'メールアドレス',
            'address' => '住所',
            'phone' => '電話番号'
        );
        foreach ($scopes as $scope => $label) {
            $Scope = new \Plugin\EccubeApi\Entity\OAuth2\Scope();
            $Scope->setScope($scope);
            $Scope->setLabel($label);
            $Scope->setCustomerFlg(1);
            $Scope->setMemberFlg(1);
            $Scope->setDefault(true);
            $em->persist($Scope);
        }
        $em->flush();
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        // XXX PostgreSQL で schemaTool::dropSchema() がうまく動作しないため、個別に削除
        $tables = array(
            'plg_oauth2_access_token',
            'plg_oauth2_refresh_token',
            'plg_oauth2_authorization_code',
            'plg_oauth2_user',
            'plg_oauth2_client',
            'plg_oauth2_openid_public_key',
            'plg_oauth2_openid_userinfo',
            'plg_oauth2_openid_userinfo_address',
            'plg_oauth2_scope',
            'plg_oauth2_client_scope',
        );
        foreach ($tables as $table) {
            if ($schema->hasTable($table)) {
                $schema->dropTable($table);
            }
        }

        if ($this->connection->getDatabasePlatform()->getName() == "postgresql") {
            $sequences = array(
                'plg_oauth2_access_token_id_seq',
                'plg_oauth2_authorization_code_id_seq',
                'plg_oauth2_client_id_seq',
                'plg_oauth2_openid_public_key_id_seq',
                'plg_oauth2_openid_userinfo_address_id_seq',
                'plg_oauth2_openid_userinfo_id_seq',
                'plg_oauth2_refresh_token_id_seq',
                'plg_oauth2_scope_id_seq',
                'plg_oauth2_user_id_seq'
            );
            foreach ($sequences as $sequence) {
                $schema->dropSequence($sequence);
            }
        }
    }
}
