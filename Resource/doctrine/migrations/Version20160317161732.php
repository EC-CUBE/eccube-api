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
            self::CLIENT
        );

        // this up() migration is auto-generated, please modify it to your needs
        $app = \Eccube\Application::getInstance();
        $em = $app['orm.em'];
        foreach ($classes as $class) {
            $metadatas[] = $em->getMetadataFactory()->getMetadataFor('\\Plugin\\EccubeApi\\Entity\\OAuth2\\'.$class);
        }
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        // $schemaTool->dropSchema($metadatas);
        $schemaTool->createSchema($metadatas);

        $scopes = array('read', 'write', 'openid', 'offline_access');
        foreach ($scopes as $scope) {
            $Scope = new \Plugin\EccubeApi\Entity\OAuth2\Scope();
            $Scope->setScope($scope);
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
            'plg_oauth2_scope'
        );
        foreach ($tables as $table) {
            $sql = 'DROP TABLE '.$table;
            $stmt = $this->connection->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
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
                $sql = 'DROP SEQUENCE '.$sequence;
                $stmt = $this->connection->prepare($sql);
                $stmt->execute();
                $stmt->closeCursor();
            }
        }
    }
}
