<?php

namespace Plugin\EccubeApi\Controller\OAuth2;

use Eccube\Application;
use Symfony\Component\HttpFoundation\Request;
use OAuth2\HttpFoundationBridge\Response as BridgeResponse;
use OAuth2\Encryption\FirebaseJwt as Jwt;

class OAuth2Controller
{

    /**
     * @link http://bshaffer.github.io/oauth2-server-php-docs/grant-types/authorization-code/
     */
    public function authorize(Application $app, Request $request)
    {
        // Pass the doctrine storage objects to the OAuth2 server class
        $server = $app['oauth2.server.authorization'];

        // TODO validation
        $client_id = $request->get('client_id');
        $redirect_uri = $request->get('redirect_uri');
        $response_type = $request->get('response_type');
        $state = $request->get('state');
        $scope = $request->get('scope');
        $nonce = $request->get('nonce');
        $is_authorized = (boolean)$request->get('authorized');

        $BridgeRequest = \OAuth2\HttpFoundationBridge\Request::createFromGlobals();
        $Response = new BridgeResponse();
        $form = $app['form.factory']->createNamed(
            '',                 // 無名のフォームを生成
            new \Plugin\EccubeApi\Form\Type\OAuth2AuthorizationType($app),
            $BridgeRequest->query->all(),
            array(
                // 'csrf_protection' => false,
            )
        );

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if (!$server->validateAuthorizeRequest($BridgeRequest, $Response)) {
                return $Response;
            }

            $Client = $app['eccube.repository.oauth2.client']->findOneBy(array('client_identifier' => $client_id));
            if ($form->isValid() && $app->user() instanceof \Eccube\Entity\Member && $Client->hasMember() && $Client->checkScope($scope)) {
                $Member = $Client->getMember();
                if ($Member->getId() !== $app->user()->getId()) {
                    $is_authorized = false;
                }
                $UserInfo = $app['eccube.repository.oauth2.openid.userinfo']->findOneBy(array('Member' => $Member));
            } elseif ($form->isValid() && $app->user() instanceof \Eccube\Entity\Customer && $Client->hasCustomer() && $Client->checkScope($scope)) {
                $Customer = $Client->getCustomer();
                if ($Customer->getId() !== $app->user()->getId()) {
                    $is_authorized = false;
                }
                $UserInfo = $app['eccube.repository.oauth2.openid.userinfo']->findOneBy(array('Customer' => $Customer));
            } else {
                // user unknown
                return $server->handleAuthorizeRequest($BridgeRequest, $Response, false);
            }

            $user_id = null;
            if ($UserInfo) {
                $user_id = $UserInfo->getSub();
            }

            // handle the request
            return $server->handleAuthorizeRequest($BridgeRequest, $Response, $is_authorized, $user_id);
        }

        return $app->render(
            'EccubeApi/Resource/template/admin/OAuth2/authorization.twig',
            array(
                'client_id' => $client_id,
                'redirect_uri' => $redirect_uri,
                'response_type' => $response_type,
                'state' => $state,
                'scope' => $scope,
                'nonce' => $nonce,
                'form' => $form->createView()
            )
        );
    }

    public function token(Application $app, Request $request)
    {
        return $app['oauth2.server.token']->handleTokenRequest(\OAuth2\HttpFoundationBridge\Request::createFromGlobals(), new BridgeResponse());
    }

    public function tokenInfo(Application $app, Request $request)
    {
        // TODO validation
        $id_token = $request->get('id_token');
        $AuthorizationCode = $app['eccube.repository.oauth2.authorization_code']->findOneBy(array('id_token' => $id_token));
        $ErrorResponse = $app->json(
            array(
                'error' => 'invalid_token',
                'error_description' => 'Invalid Value'
            ), 400);

        if (!$AuthorizationCode) {
            return $ErrorResponse;
        }

        $Client = $AuthorizationCode->getClient();
        $public_key = $app['eccube.repository.oauth2.openid.public_key']->getPublicKeyByClientId($Client->getId());
        $jwt = new Jwt();
        $payload = $jwt->decode($id_token, $public_key);
        if (!$payload) {
            return $ErrorResponse;
        }
        return $app->json($payload, 200);
    }
}
