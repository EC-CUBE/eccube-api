<?php

/*
 * This file is part of the EccubeApi
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\EccubeApi\Controller;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EccubeApiSampleController extends AbstractApiController
{

    /**
     * 商品詳細取得API
     *
     * @param Application $app
     * @param Request     $request
     * @param             $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse|Response
     */
    public function productsDetail(Application $app, Request $request, $id)
    {

        if ($request->getMethod() === "OPTIONS") {
            return new Response();
        }
        // OAuth2 Authorization
        $scope_reuqired = 'read';
        if (!$this->verifyRequest($app, $request, $scope_reuqired)) {
            return $app['oauth2.server.resource']->getResponse();
        }


        $BaseInfo = $app['eccube.repository.base_info']->get();
        if ($BaseInfo->getNostockHidden() === Constant::ENABLED) {
            $app['orm.em']->getFilters()->enable('nostock_hidden');
        }

        /* @var $Product \Eccube\Entity\Product */
        $Product = $app['eccube.repository.product']->find($id);

        if (!$Product || count($Product->getProductClasses()) < 1) {
            $this->addErrors($app, 101);
            return $app->json($this->getErrors(), 404);
        }

        // Wrappered OAuth2 response
        return $this->getWrapperedResponseBy($app, array('product' => $Product->toArray()));

    }

}
