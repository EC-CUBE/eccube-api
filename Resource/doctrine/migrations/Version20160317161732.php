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
        $schemaTool->dropSchema($metadatas);
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
        $app = \Eccube\Application::getInstance();
        $em = $app['orm.em'];
        foreach ($classes as $class) {
            $metadatas[] = $em->getMetadataFactory()->getMetadataFor('\\Plugin\\EccubeApi\\Entity\\OAuth2\\'.$class);
        }
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $schemaTool->dropSchema($metadatas);
    }
}
