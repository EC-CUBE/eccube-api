<?php

namespace Plugin\EccubeApi\Controller;

use Eccube\Application;
use Symfony\Component\HttpFoundation\Request;
use OAuth2\HttpFoundationBridge\Response as BridgeResponse;

abstract class AbstractApiController
{
    private $errors = array();

    protected function verifyRequest(Application $app, $scope_reuqired = null)
    {
        return $app['oauth2.server.resource']->verifyResourceRequest(
            \OAuth2\Request::createFromGlobals(),
            new BridgeResponse(),
            $scope_reuqired
        );
    }

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

    protected function getErrors()
    {

        $errors = array();
        foreach ($this->errors as $error) {
            $errors[] = $error;
        }

        return array('errors' => $errors);

    }
}
