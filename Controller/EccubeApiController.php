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

class EccubeApiController extends AbstractApiController
{

    /**
     * 商品一覧取得API
     *
     * @param Application $app
     * @param Request     $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function index(Application $app, Request $request)
    {
        if ($request->getMethod() === "OPTIONS") {
            return new Response();
        }
        // OAuth2 Authorization
        $scope_reuqired= 'read';
        if (!$this->verifyRequest($app, $scope_reuqired)) {
            return $app['oauth2.server.resource']->getResponse();
        }

        $BaseInfo = $app['eccube.repository.base_info']->get();

        // Doctrine SQLFilter
        if ($BaseInfo->getNostockHidden() === Constant::ENABLED) {
            $app['orm.em']->getFilters()->enable('nostock_hidden');
        }

        // handleRequestは空のqueryの場合は無視するため
        $request->query->set('pageno', $request->query->get('pageno', ''));

        // searchForm
        /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
        $builder = $app['form.factory']->createNamedBuilder('', 'search_product');
        $builder->setMethod('GET');

        $event = new EventArgs(
            array(
                'builder' => $builder,
            ),
            $request
        );
        $app['eccube.event.dispatcher']->dispatch(EccubeEvents::FRONT_PRODUCT_INDEX_INITIALIZE, $event);

        /* @var $searchForm \Symfony\Component\Form\FormInterface */
        $searchForm = $builder->getForm();


        $searchForm->handleRequest($request);

        if (!$searchForm->isValid()) {
            $errors = array(
                'errors' => array(
                    array(
                        'code' => 100,
                        'message' => 'エラーです。',
                    ),
                    array(
                        'code' => 101,
                        'message' => $searchForm->getErrorsAsString(),
                    ),
                ),
            );
            return $app->json($errors, 400);
        }

        // paginator
        $searchData = $searchForm->getData();
        $qb = $app['eccube.repository.product']->getQueryBuilderBySearchData($searchData);

        $event = new EventArgs(
            array(
                'searchData' => $searchData,
                'qb' => $qb,
            ),
            $request
        );
        $app['eccube.event.dispatcher']->dispatch(EccubeEvents::FRONT_PRODUCT_INDEX_SEARCH, $event);
        $searchData = $event->getArgument('searchData');

        $pagination = $app['paginator']()->paginate(
            $qb,
            !empty($searchData['pageno']) ? $searchData['pageno'] : 1,
            $searchData['disp_number']->getId()
        );

        // 表示件数
        $builder = $app['form.factory']->createNamedBuilder('disp_number', 'product_list_max', null, array(
            'empty_data' => null,
            'required' => false,
            'label' => '表示件数',
            'allow_extra_fields' => true,
        ));
        $builder->setMethod('GET');

        $event = new EventArgs(
            array(
                'builder' => $builder,
            ),
            $request
        );
        $app['eccube.event.dispatcher']->dispatch(EccubeEvents::FRONT_PRODUCT_INDEX_DISP, $event);

        $dispNumberForm = $builder->getForm();

        $dispNumberForm->handleRequest($request);

        // ソート順
        $builder = $app['form.factory']->createNamedBuilder('orderby', 'product_list_order_by', null, array(
            'empty_data' => null,
            'required' => false,
            'label' => '表示順',
            'allow_extra_fields' => true,
        ));
        $builder->setMethod('GET');

        $event = new EventArgs(
            array(
                'builder' => $builder,
            ),
            $request
        );
        $app['eccube.event.dispatcher']->dispatch(EccubeEvents::FRONT_PRODUCT_INDEX_ORDER, $event);

        $orderByForm = $builder->getForm();

        $orderByForm->handleRequest($request);

        $products = $qb->getQuery()->getResult();

        $results = array();
        /** @var \Eccube\Entity\Product $Product */
        foreach ($products as $Product) {
            $productImages = $Product->getProductImage();
            $images = array();
            foreach ($productImages as $ProductImage) {
                $images[] = $ProductImage->toArray();
            }
            $results[] = array(
                'product' => $Product->toArray(),
                'image' => $images,
            );
        }

        // Wrappered OAuth2 response
        return $this->getWrapperedResponseBy($app, array('products' => $results));
    }

    public function swagger(Application $app, Request $request)
    {
        $yml = file_get_contents(__DIR__.'/../eccubeapi.yml');
        $yml = str_replace('https://<your-host-name>', rtrim($app->url('homepage'), '/'), $yml);
        $yml = str_replace('<your-host-name>', $_SERVER['HTTP_HOST'], $yml);
        $yml = str_replace('<admin_dir>', rtrim($app['config']['admin_dir'], '/'), $yml);
        $Response = new Response();
        $Response->setContent($yml);
        return $Response;
    }

    public function swaggerUI(Application $app, Request $request)
    {
        $swagger = file_get_contents(__DIR__.'/../Resource/swagger-ui/index.html');
        $swagger = str_replace('your-client-id', htmlspecialchars($request->get('client_id'), ENT_QUOTES), $swagger);
        $swagger = str_replace('scopeSeparator: ","', 'scopeSeparator: " "', $swagger);
        $swagger = str_replace('http://petstore.swagger.io/v2/swagger.json', $app->url('swagger_yml'), $swagger);
        $swagger = preg_replace('/src=\'(.*)\'(.*)/', 'src=\'/plugin/api/swagger-ui/${1}\'${2}', $swagger);
        $swagger = preg_replace('/src="(.*)"(.*)/', 'src="/plugin/api/swagger-ui/${1}"${2}', $swagger);
        $swagger = preg_replace('/link href=\'(.*)\'(.*)/', 'link href=\'/plugin/api/swagger-ui/${1}\'${2}', $swagger);

        $Response = new Response();
        $Response->setContent($swagger);
        return $Response;
    }

    public function swaggerO2c(Application $app, Request $request)
    {
        $swagger = file_get_contents(__DIR__.'/../Resource/swagger-ui/o2c.html');
        $Response = new Response();
        $Response->setContent($swagger);
        return $Response;
    }
}
