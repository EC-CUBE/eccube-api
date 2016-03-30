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

class EccubeApiController extends AbstractApiController
{

    /**
     * 商品一覧取得API
     *
     * @param Application $app
     * @param Request     $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function products(Application $app, Request $request)
    {
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

        /* @var $searchForm \Symfony\Component\Form\FormInterface */
        $searchForm = $builder->getForm();

        $searchForm->handleRequest($request);

        if (!$searchForm->isValid()) {
            $this->addErrors($app, 2001, $searchForm->getErrorsAsString());
            return $app->json($this->getErrors(), 400);
        }

        // paginator
        $searchData = $searchForm->getData();
        $qb = $app['eccube.repository.product']->getQueryBuilderBySearchData($searchData);

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

        $orderByForm = $builder->getForm();

        $orderByForm->handleRequest($request);

        $products = $qb->getQuery()->getResult();

        if (count($products) == 0) {
            $this->addErrors($app, 1002);
            return $app->json($this->getErrors());
        }

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

        $metadata = array(
            'totalItemCount' => count($products),
            'limit' => $searchData['disp_number']->getId(),
            'offset' => $searchData['disp_number']->getId() * $searchData['pageno'],
        );

        return $app->json(array('products' => $results, 'metadata' => $metadata));
    }


    /**
     * 商品詳細取得API
     *
     * @param Application $app
     * @param Request     $request
     * @param             $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws NotFoundHttpException
     */
    public function productsDetail(Application $app, Request $request, $id)
    {
        $BaseInfo = $app['eccube.repository.base_info']->get();
        if ($BaseInfo->getNostockHidden() === Constant::ENABLED) {
            $app['orm.em']->getFilters()->enable('nostock_hidden');
        }

        /* @var $Product \Eccube\Entity\Product */
        $Product = $app['eccube.repository.product']->find($id);

        if (!$Product || count($Product->getProductClasses()) < 1) {
            $this->addErrors($app, 1001);
            return $app->json($this->getErrors(), 400);
        }

        return $app->json(array('product' => $Product->toArray()));

    }


    /**
     * swagger画面を表示します。
     *
     * @param Application $app
     * @param Request     $request
     * @return Response
     */
    public function swaggerUI(Application $app, Request $request)
    {
        $swagger = file_get_contents(__DIR__.'/../Resource/swagger-ui/index.html');
        $swagger = str_replace('your-client-id', htmlspecialchars($request->get('client_id'), ENT_QUOTES), $swagger);
        $swagger = str_replace('scopeSeparator: ","', 'scopeSeparator: " "', $swagger);
        $swagger = str_replace('url = "http://petstore.swagger.io/v2/swagger.json";', 'url = "'.$app->url('swagger_yml').'"; window.oAuthRedirectUrl="'.$app->url('swagger_o2c').'";', $swagger);
        $swagger = preg_replace('/src=\'(.*)\'(.*)/', 'src=\''.$app['config']['root_urlpath'].'/plugin/api/assets/${1}\'${2}', $swagger);
        $swagger = preg_replace('/src="(.*)"(.*)/', 'src="'.$app['config']['root_urlpath'].'/plugin/api/assets/${1}"${2}', $swagger);
        $swagger = preg_replace('/link href=\'(.*)\'(.*)/', 'link href=\''.$app['config']['root_urlpath'].'/plugin/api/assets/${1}\'${2}', $swagger);

        $Response = new Response();
        $Response->setContent($swagger);
        return $Response;
    }

    /**
     * ymlファイルを読み込んで表示します。
     *
     * @param Application $app
     * @param Request     $request
     * @return Response
     */
    public function swagger(Application $app, Request $request)
    {
        $yml = file_get_contents(__DIR__.'/../eccubeapi.yml');
        $yml = str_replace('https://<your-host-name>', rtrim($app->url('homepage'), '/'), $yml);
        $yml = str_replace('<your-host-name>', $_SERVER['HTTP_HOST'], $yml);
        $yml = str_replace('<admin_dir>', rtrim($app['config']['admin_dir'], '/'), $yml);
        $yml = str_replace('<base-path>', $app['config']['root_urlpath'], $yml);
        $yml = str_replace('/<base-endpoint>', $app['config']['api.endpoint'], $yml);
        $yml = str_replace('<base-version>', $app['config']['api.version'], $yml);
        $Response = new Response();
        $Response->setContent($yml);
        return $Response;
    }


    /**
     * OAuth2用ダイアログを表示します。
     *
     * @param Application $app
     * @param Request     $request
     * @return Response
     */
    public function swaggerO2c(Application $app, Request $request)
    {
        $swagger = file_get_contents(__DIR__.'/../Resource/swagger-ui/o2c.html');
        $Response = new Response();
        $Response->setContent($swagger);
        return $Response;
    }
}
