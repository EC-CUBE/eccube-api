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

        $this->createScopes($em);
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
            );
            foreach ($sequences as $sequence) {
                $schema->dropSequence($sequence);
            }
        }
    }

    protected function createScopes($em)
    {
        $scopes = array(
            // OpenID Connect で必須の scope
            'openid' => 'OpenID Connect 認証',
            'offline_access' => 'オフラインアクセス',
            'profile' => 'プロフィール情報',
            'email' => 'メールアドレス',
            'address' => '住所',
            'phone' => '電話番号',
            // 参照権限
            'csv_type_read' => 'CSV種別(参照)',
            'customer_order_status_read' => '受注ステータス(マイページでの表示)(参照)',
            'customer_status_read' => '会員ステータス(仮会員・本会員)(参照)',
            'db_read' => 'DB種別(参照)',
            'device_type_read' => '端末種別(参照)',
            'disp_read' => '表示・非表示(参照)',
            'job_read' => '職業(参照)',
            'order_status_read' => '受注ステータス(管理画面での表示)(参照)',
            'order_status_color_read' => '受注ステータス(受注ステータスの表示色)(参照)',
            'page_max_read' => 'ページ表示件数(参照)',
            'pref_read' => '都道府県(参照)',
            'product_list_max_read' => '商品表示件数(参照)',
            'product_list_order_by_read' => '商品ソート順(参照)',
            'product_type_read' => '商品種別(参照)',
            'sex_read' => '性別(参照)',
            'tag_read' => '商品タグ(参照)',
            'taxrule_read' => '計算ルール(四捨五入・切り捨て等)(参照)',
            'work_read' => '稼働状況(管理画面メンバーの稼働状況)(参照)',
            'shipment_item_read' => '配送商品(参照)',
            'shipping_read' => '配送先(参照)',
            'tax_rule_read' => '税率(参照)',
            'template_read' => 'デザインテンプレート一覧(参照)',
            'authority_role_read' => '権限(参照)',
            'base_info_read' => '基本情報(参照)',
            'block_read' => 'ブロック(参照)',
            'block_position_read' => 'ブロック配置情報(参照)',
            'category_read' => '商品カテゴリ(参照)',
            'class_category_read' => '規格分類(参照)',
            'class_name_read' => '規格名(参照)',
            'csv_read' => 'CSV出力項目(参照)',
            'customer_read' => '会員(参照)',
            'customer_address_read' => '会員お届け先(参照)',
            'customer_favorite_product_read' => '会員お気に入り商品(参照)',
            'delivery_read' => '配送業者(参照)',
            'delivery_date_read' => '配送予定日(参照)',
            'delivery_fee_read' => '配送料(参照)',
            'delivery_time_read' => '配送時間(参照)',
            'help_read' => 'サイトコンテンツ(特定商取引法等)(参照)',
            'mail_history_read' => 'メール送信履歴(参照)',
            'mail_template_read' => 'メールテンプレート(参照)',
            'member_read' => '管理画面メンバー(参照)',
            'news_read' => '新着情報(参照)',
            'order_read' => '受注(参照)',
            'order_detail_read' => '受注詳細(参照)',
            'page_layout_read' => 'ページ情報(参照)',
            'payment_read' => '支払い方法(参照)',
            'payment_option_read' => '配送業者-支払い方法(参照)',
            'plugin_read' => 'プラグイン情報(参照)',
            'plugin_event_handler_read' => 'プラグインイベントハンドラ(参照)',
            'product_read' => '商品(参照)',
            'product_category_read' => '商品-カテゴリ(多対多)(参照)',
            'product_class_read' => '商品規格(参照)',
            'product_image_read' => '商品画像(参照)',
            'product_stock_read' => '在庫(参照)',
            'product_tag_read' => '商品タグ(参照)',
            // 更新権限
            'csv_type_write' => 'CSV種別(更新)',
            'customer_order_status_write' => '受注ステータス(マイページでの表示)(更新)',
            'customer_status_write' => '会員ステータス(仮会員・本会員)(更新)',
            'db_write' => 'DB種別(更新)',
            'device_type_write' => '端末種別(更新)',
            'disp_write' => '表示・非表示(更新)',
            'job_write' => '職業(更新)',
            'order_status_write' => '受注ステータス(管理画面での表示)(更新)',
            'order_status_color_write' => '受注ステータス(受注ステータスの表示色)(更新)',
            'page_max_write' => 'ページ表示件数(更新)',
            'pref_write' => '都道府県(更新)',
            'product_list_max_write' => '商品表示件数(更新)',
            'product_list_order_by_write' => '商品ソート順(更新)',
            'product_type_write' => '商品種別(更新)',
            'sex_write' => '性別(更新)',
            'tag_write' => '商品タグ(更新)',
            'taxrule_write' => '計算ルール(四捨五入・切り捨て等)(更新)',
            'work_write' => '稼働状況(管理画面メンバーの稼働状況)(更新)',
            'shipment_item_write' => '配送商品(更新)',
            'shipping_write' => '配送先(更新)',
            'tax_rule_write' => '税率(更新)',
            'template_write' => 'デザインテンプレート一覧(更新)',
            'authority_role_write' => '権限(更新)',
            'base_info_write' => '基本情報(更新)',
            'block_write' => 'ブロック(更新)',
            'block_position_write' => 'ブロック配置情報(更新)',
            'category_write' => '商品カテゴリ(更新)',
            'class_category_write' => '規格分類(更新)',
            'class_name_write' => '規格名(更新)',
            'csv_write' => 'CSV出力項目(更新)',
            'customer_write' => '会員(更新)',
            'customer_address_write' => '会員お届け先(更新)',
            'customer_favorite_product_write' => '会員お気に入り商品(更新)',
            'delivery_write' => '配送業者(更新)',
            'delivery_date_write' => '配送予定日(更新)',
            'delivery_fee_write' => '配送料(更新)',
            'delivery_time_write' => '配送時間(更新)',
            'help_write' => 'サイトコンテンツ(特定商取引法等)(更新)',
            'mail_history_write' => 'メール送信履歴(更新)',
            'mail_template_write' => 'メールテンプレート(更新)',
            'member_write' => '管理画面メンバー(更新)',
            'news_write' => '新着情報(更新)',
            'order_write' => '受注(更新)',
            'order_detail_write' => '受注詳細(更新)',
            'page_layout_write' => 'ページ情報(更新)',
            'payment_write' => '支払い方法(更新)',
            'payment_option_write' => '配送業者-支払い方法(更新)',
            'plugin_write' => 'プラグイン情報(更新)',
            'plugin_event_handler_write' => 'プラグインイベントハンドラ(更新)',
            'product_write' => '商品(更新)',
            'product_category_write' => '商品-カテゴリ(多対多)(更新)',
            'product_class_write' => '商品規格(更新)',
            'product_image_write' => '商品画像(更新)',
            'product_stock_write' => '在庫(更新)',
            'product_tag_write' => '商品タグ(更新)',
        );

        foreach ($scopes as $scope => $label) {
            $Scope = new \Plugin\EccubeApi\Entity\OAuth2\Scope();
            $Scope->setScope($scope);
            $Scope->setLabel($label);
            switch ($scope) {
                // 会員に許可する scope
                case 'openid':
                case 'offline_access':
                case 'profile':
                case 'email':
                case 'address':
                case 'phone':
                case 'customer_read':
                case 'customer_write':
                case 'customer_address_read':
                case 'customer_address_write':
                case 'order_read':
                case 'order_detail_read':
                    $Scope->setCustomerFlg(1);
                    break;
                default:
                    $Scope->setCustomerFlg(0);
            }

            $Scope->setMemberFlg(1);
            $Scope->setDefault(true);
            $em->persist($Scope);
            $em->flush($Scope);
        }
    }
}
