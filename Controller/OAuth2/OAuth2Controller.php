<?php

namespace Plugin\EccubeApi\Controller\OAuth2;

use Eccube\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use OAuth2\HttpFoundationBridge\Response as BridgeResponse;
use OAuth2\Encryption\FirebaseJwt as Jwt;
use Plugin\EccubeApi\Controller\AbstractApiController;

/**
 * OAuth2.0 Authorization をするためのコントローラ.
 *
 * @author Kentaro Ohkouchi
 */
class OAuth2Controller extends AbstractApiController
{

    /**
     * Authorization Endpoint.
     *
     * @param Application $app
     * @param Request $request
     * @return \OAuth2\HttpFoundationBridge\Response
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

        $is_admin = false;
        // 認可要求
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            // 認可要求の妥当性をチェックする(主にURLパラメータ)
            if (!$server->validateAuthorizeRequest($BridgeRequest, $Response)) {
                return $Response;
            }

            // ログイン中のユーザーと、認可要求された client_id の妥当性をチェックする.
            // CSRFチェック, Client が使用可能な scope のチェック, ログイン中ユーザーの妥当性チェック
            $Client = $app['eccube.repository.oauth2.client']->findOneBy(array('client_identifier' => $client_id));
            if ($form->isValid() && $app->user() instanceof \Eccube\Entity\Member && $Client->hasMember()) {
                $Member = $Client->getMember();
                if ($Member->getId() !== $app->user()->getId()) {
                    $is_authorized = false;
                }
                $UserInfo = $app['eccube.repository.oauth2.openid.userinfo']->findOneBy(array('Member' => $Member));
                $is_admin = true;
            } elseif ($form->isValid() && $app->user() instanceof \Eccube\Entity\Customer && $Client->hasCustomer()) {
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
            // TODO $is_authorized == false の場合のエラーメッセージを分けたい
            $Response = $server->handleAuthorizeRequest($BridgeRequest, $Response, $is_authorized, $user_id);
            $content = json_decode($Response->getContent(), true);
            // redirect_uri に urn:ietf:wg:oauth:2.0:oob が指定されていた場合(ネイティブアプリ等)の処理
            if ($BridgeRequest->get('redirect_uri') == 'urn:ietf:wg:oauth:2.0:oob' && empty($content)) {
                $ResponseType = $server->getResponseType('code');
                $res = $ResponseType->getAuthorizeResponse(
                    array(
                        'redirect_uri' => 'urn:ietf:wg:oauth:2.0:oob',
                        'client_id' => $client_id,
                        'state' => $state,
                        'scope' => $scope
                    ),
                    $user_id);
                $route = 'oauth2_server_mypage_authorize_oob';
                if ($is_admin) {
                    $route = 'oauth2_server_admin_authorize_oob';
                }
                return $app->redirect($app->url($route, array('code' => $res[1]['query']['code'])));
            }
            return $Response;
        }

        $scopes = array();
        if (!is_null($scope)) {
            $scopes = explode(' ', $scope);
        }

        // 認可リクエスト用の画面を表示
        $view = 'EccubeApi/Resource/template/mypage/OAuth2/authorization.twig';
        if ($app->user() instanceof \Eccube\Entity\Member) {
            $view = 'EccubeApi/Resource/template/admin/OAuth2/authorization.twig';
        }
        return $app->render(
            $view,
            array(
                'client_id' => $client_id,
                'redirect_uri' => $redirect_uri,
                'response_type' => $response_type,
                'state' => $state,
                'scope' => $scope,
                'scopeAsJson' => json_encode($scopes),
                'nonce' => $nonce,
                'form' => $form->createView()
            )
        );
    }

    /**
     * Authorization code を画面に表示する.
     *
     * request_uri に urn:ietf:wg:oauth:2.0:oob が指定された場合はこの画面を表示する.
     *
     * @param Application $app
     * @param Request $request
     * @param string $code Authorization code の文字列
     * @return \OAuth2\HttpFoundationBridge\Response
     */
    public function authorizeOob(Application $app, Request $request, $code = null)
    {
        if ($code === null) {
            throw new NotFoundHttpException();
        }
        $AuthorizationCode = $app['eccube.repository.oauth2.authorization_code']->findOneBy(array('code' => $code));
        if (!is_object($AuthorizationCode)) {
            throw new NotFoundHttpException();
        }

        $view = 'EccubeApi/Resource/template/mypage/OAuth2/authorization_code.twig';
        if ($app->user() instanceof \Eccube\Entity\Member) {
            $view = 'EccubeApi/Resource/template/admin/OAuth2/authorization_code.twig';
        }
        return $app->render(
            $view,
            array('code' => $code)
        );
    }

    /**
     * Token Endpoint.
     *
     * @param Application $app
     * @param Request $request
     * @return \OAuth2\HttpFoundationBridge\Response
     */
    public function token(Application $app, Request $request)
    {
        return $app['oauth2.server.token']->handleTokenRequest(\OAuth2\HttpFoundationBridge\Request::createFromGlobals(), new BridgeResponse());
    }

    /**
     * Tokeninfo Endpoint.
     *
     * id_token の妥当性検証のために使用する.
     *
     * @param Application $app
     * @param Request $request
     * @return \OAuth2\HttpFoundationBridge\Response
     * @link https://developers.google.com/identity/protocols/OpenIDConnect#validatinganidtoken
     */
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

    /**
     * UserInfo Endpoint.
     *
     * このエンドポイントは scope=openid による認可リクエストが必要です.
     *
     * @param Application $app
     * @param Request $request
     * @return \OAuth2\HttpFoundationBridge\Response
     */
    public function userInfo(Application $app, Request $request)
    {
        return $app['oauth2.server.resource']->handleUserInfoRequest(\OAuth2\HttpFoundationBridge\Request::createFromGlobals(), new BridgeResponse());
    }
}
