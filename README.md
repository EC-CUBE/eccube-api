# EC-CUBE Web API

# インストール方法

あらかじめ, EC-CUBE3のインストールを行った状態で, 以下を実行してください

```
// ec-cube のプラグインディレクトリに移動
cd [ec-cube root]/app/Plugin

// ec-cube apiのレポジトリをclone
git clone https://github.com/EC-CUBE/eccube-api.git EccubeApi

// composerの実行
curl -sS https://getcomposer.org/installer | php
php ./composer.phar install --dev --no-interaction -o

// ec-cube apiのプラグインをインストール, 有効化
cd [ec-cube root]
app/console plugin:develop install --code=EccubeApi
app/console plugin:develop enable --code=EccubeApi
```

## .htaccess の設定

一部のレンタルサーバーや SAPI CGI/FastCGI の環境では、認証情報(Authorization ヘッダ)が取得できず、 401 Unauthorized エラーとなってしまう場合があります。
この場合は、 `<ec-cube-install-path>/html/.htaccess` に以下を追記してください。

```.htaccess
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]
```


# 動作確認方法

## アプリケーションの作成

設定＞メンバー管理＞メンバー編集＞APIクライアント一覧からアプリケーションを作成します

* アプリケーション名：任意
* redirect_uri：http://{host}/{basepath}/api/o2c ※http://localhost/ec-ccbe/html/api/o2c 等

作成後、`APIドキュメントを開く`を押下

swagger ui の画面へ遷移すれば成功です

# APIクライアントコードの生成

[swagger editor](http://editor.swagger.io/)を利用して、APIクライアントコードを自動生成することが出来ます

* `eccubeapi.yml`に記述されている内容をswagger editorに貼り付けます
* `localhost`, `basePath`を環境に応じて修正します
* `Generate Client`メニューから、任意の言語でAPIクライアントのソースコードを生成することが出来ます

# 開発ドキュメント

https://ec-cube.github.io/api

