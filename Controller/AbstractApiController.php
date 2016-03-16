<?php

namespace Plugin\EccubeApi\Controller;

use Eccube\Application;
use Symfony\Component\HttpFoundation\Request;
use OAuth2\HttpFoundationBridge\Response as BridgeResponse;

abstract class AbstractApiController
{
    protected function verifyRequest(Application $app, $scope_reuqired = null)
    {
        return $app['oauth2.server.resource']->verifyResourceRequest(
            \OAuth2\Request::createFromGlobals(),
            new BridgeResponse(),
            $scope_reuqired
        );
    }

    protected function getWrapperedResponseBy(Application $app, $data)
    {
        $Response = $app['oauth2.server.resource']->getResponse();
        $Response->setData($data);
        return $Response;
    }
}
