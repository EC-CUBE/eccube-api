<?php

/*
 * This file is part of the EccubeApi
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\EccubeApi;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\DomCrawler\Crawler;

class EccubeApiEvent
{

    /** @var  \Eccube\Application $app */
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 全体に対して適用させるイベント
     *
     * @param GetResponseEvent $event
     */
    public function onAppRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if ($request->getMethod() === "OPTIONS") {
            $response = new Response();
            // https://developer.mozilla.org/ja/docs/HTTP_access_control#Requests_with_credentials
            $response->headers->set("Access-Control-Allow-Origin","*");
            $response->headers->set("Access-Control-Allow-Methods","GET,POST,PUT,DELETE,OPTIONS");
            $response->headers->set("Access-Control-Allow-Headers","Content-Type");
            $response->setStatusCode(204);
        }

        //accepting JSON
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), true);
            $request->request->replace(is_array($data) ? $data : array());
        }
    }

    /**
     * 全体のレスポンスに対して適用させるイベント
     *
     * @param FilterResponseEvent $event
     */
    public function onAppResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $response->headers->set("Access-Control-Allow-Origin","*");
        $response->headers->set("Access-Control-Allow-Methods","GET,POST,PUT,DELETE,OPTIONS");
        $response->headers->set("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Authorization");
    }

    /**
     * 管理画面に APIクライアント一覧へのボタンを表示するイベント
     *
     * @param FilterResponseEvent $event
     */
    public function onRouteAdminMemberResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        if ($response->isRedirection()) {
            return;
        }
        $html = $response->getContent();
        $crawler = new Crawler($html);
        $oldElement= $crawler->filter('#common_button_box__insert_button');
        $oldHtml= $oldElement->html();
        $newHtml= $oldHtml.'<button class="btn btn-primary btn-block btn-lg" onclick="window.location.href=\'api\';">APIクライアント一覧</button>';
        $html = $crawler->html();
        $html =str_replace($oldHtml, $newHtml, $html);
        $response->setContent($html);
        $event->setResponse($response);
    }
}
