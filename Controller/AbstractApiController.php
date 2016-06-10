<?php

namespace Plugin\EccubeApi\Controller;

use Eccube\Application;
use Eccube\Entity\AbstractEntity;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Proxy\Proxy;
use Symfony\Component\HttpFoundation\Request;
use OAuth2\HttpFoundationBridge\Response as BridgeResponse;
use OAuth2\HttpFoundationBridge\Request as BridgeRequest;

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
    protected function verifyRequest(Application $app, Request $request, $scope_required = null)
    {
        return $app['oauth2.server.resource']->verifyResourceRequest(
            BridgeRequest::createFromRequest($request),
            new BridgeResponse(),
            $scope_required
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
     * エラーレスポンスを返します.
     *
     * @param Application $app
     * @param string $message エラーメッセージ
     * @param integer $statusCode 返却する HTTP Status コード
     * @return \OAuth2\HttpFoundationBridge\Response でラップしたレスポンス.

     */
    protected function getWrapperedErrorResponseBy(Application $app, $message = 'Not found', $statusCode = 404)
    {
        $this->addErrors($app, $statusCode, $message);
        return $this->getWrapperedResponseBy($app, $this->getErrors(), $statusCode);
    }
}
