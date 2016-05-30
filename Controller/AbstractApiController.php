<?php

namespace Plugin\EccubeApi\Controller;

use Eccube\Application;
use Eccube\Entity\AbstractEntity;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Proxy\Proxy;
use Symfony\Component\HttpFoundation\Request;
use OAuth2\HttpFoundationBridge\Response as BridgeResponse;

/**
 * ApiController の抽象クラス.
 *
 * API の Controller クラスを作成する場合は、このクラスを継承します.
 *
 * @author Kentaro Ohkouchi
 * @author Kiyoshi Yamamura
 */
abstract class AbstractApiController
{
    private $errors = array();

    /**
     * API リクエストの妥当性を検証します.
     *
     * 認可リクエスト(AuthN)において、 $scope_required で指定した scope が認可されていない場合は false を返します.
     *
     * @param Application $app
     * @param string $scope_required API リクエストで必要とする scope
     * @return boolean 妥当と判定された場合 true
     */
    protected function verifyRequest(Application $app, $scope_reuqired = null)
    {
        return $app['oauth2.server.resource']->verifyResourceRequest(
            \OAuth2\Request::createFromGlobals(),
            new BridgeResponse(),
            $scope_reuqired
        );
    }

    /**
     * \OAuth2\HttpFoundationBridge\Response でラップしたレスポンスを返します.
     *
     * @param Application $app
     * @param mixed $data レスポンス結果のデータ
     * @param integer $statusCode 返却する HTTP Status コード
     * @return \OAuth2\HttpFoundationBridge\Response でラップしたレスポンス.
     */
    protected function getWrapperedResponseBy(Application $app, $data, $statusCode = 200)
    {
        $Response = $app['oauth2.server.resource']->getResponse();
        if (!is_object($Response)) {
            return $app->json($data, $statusCode);
        }
        $Response->setData($data);
        $Response->setStatusCode($statusCode);
        return $Response;
    }

    /**
     * エラー内容を追加します.
     *
     * $message が null の場合は、エラーコードに該当するエラーメッセージを返します.
     *
     * @param Application $app
     * @param string $code エラーコード
     * @param string $message エラーメッセージ
     * @returnl void
     */
    protected function addErrors(Application $app, $code, $message = null)
    {

        if (!$message) {
            $message = $app->trans($code);
            if ($message == $code) {
                // コードに該当するメッセージが取得できなかった場合、共通メッセージを表示
                $message =  $app->trans(100);
            }
        }

        $this->errors[] = array('code' => $code, 'message' => $message);
    }

    /**
     * エラーメッセージの配列を返します.
     *
     * @return array エラーメッセージの配列
     */
    protected function getErrors()
    {

        $errors = array();
        foreach ($this->errors as $error) {
            $errors[] = $error;
        }

        return array('errors' => $errors);
    }

    /**
     * テーブル名から Metadata を検索する.
     *
     * テーブル名の, `dtb_`, `mtb_` といった prefix は省略可能.
     *
     * @param Application $app
     * @param string $table テーブル名.
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadata
     */
    protected function findMetadata(Application $app, $table)
    {
        $metadatas = $app['orm.em']->getMetadataFactory()->getAllMetadata();
        foreach ($metadatas as $metadata) {
            $table_name = $metadata->table['name'];
            if ($table == $table_name
                || $table == $this->shortTableName($table_name)) {
                return $metadata;
            }
        }
        return  null;
    }

    /**
     * `dtb_`, `mtb_` といった prefix を除いたテーブル名を返す.
     *
     * @param string $table テーブル名
     * @return string prefix を除いたテーブル名
     */
    protected function shortTableName($table)
    {
        return str_replace(array('dtb_', 'mtb_'), '', $table);
    }

    /**
     * JSON Respoinse 用の配列を返します.
     *
     * @see AbstractEntity::toArray()
     */
    protected function entityToArray(Application $app, AbstractEntity $Entity, array $excludeAttribute = array('__initializer__', '__cloner__', '__isInitialized__'))
    {
        $Reflect = new \ReflectionClass($Entity);
        $Properties = $Reflect->getProperties();
        $Results = array();
        foreach ($Properties as $Property) {
            $Property->setAccessible(true);
            $name = $Property->getName();
            if (in_array($name, $excludeAttribute)) {
                continue;
            }
            $PropertyValue = $Property->getValue($Entity);
            if ($PropertyValue instanceof \DateTime) {
                $Results[$name] = $PropertyValue->format(\Datetime::ATOM);
            } elseif ($PropertyValue instanceof AbstractEntity) {
                // Entity の場合は [id => value] の配列を返す
                $Results[$name] = $this->getEntityIdentifierAsArray($app, $PropertyValue);
            } elseif ($PropertyValue instanceof PersistentCollection) {
                // Collection の場合は ID を持つオブジェクトの配列を返す
                $Collections = $PropertyValue->getValues();
                foreach ($Collections as $Child) {
                    $Results[$name][] = $this->getEntityIdentifierAsArray($app, $Child);
                }
            } else {
                if ($Entity instanceof Proxy) {
                    // XXX Proxy の場合はリフレクションが使えないため id を決め打ちで取得する
                    $Results['id'] = $Entity->getId();
                } else {
                    $Results[$name] = $PropertyValue;
                }
            }
        }
        return $Results;
    }

    /**
     * Entity のID情報を配列で返します.
     */
    protected function getEntityIdentifierAsArray(Application $app, AbstractEntity $Entity)
    {
        $metadata = $app['orm.em']->getMetadataFactory()->getMetadataFor(get_class($Entity));
        $idField = '';
        $Result = array();
        foreach ($metadata->fieldMappings as $field => $mapping) {
            if (array_key_exists('id', $mapping) === true && $mapping['id'] === true) {
                $idField = $mapping['fieldName'];
                if ($Entity instanceof Proxy) {
                    // Doctrine Proxy の場合は getId() で値を取得
                    $value = $Entity->getId(); // XXX 複合キーや getId() の無い場合の対応
                } else {
                    // Entity の場合はリフレクションで値を取得
                    $PropReflect = new \ReflectionClass($Entity);

                    $IdProperty = $PropReflect->getProperty($idField);
                    $IdProperty->setAccessible(true);
                    $value = $IdProperty->getValue($Entity);
                }
                $Result[$idField] = $value;
            }
        }

        return $Result;
    }

}
