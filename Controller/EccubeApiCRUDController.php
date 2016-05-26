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

class EccubeApiCRUDController extends AbstractApiController
{

    public function findAll(Application $app, Request $request, $table = null)
    {
        $metadatas = $app['orm.em']->getMetadataFactory()->getAllMetadata();
        $className = '';
        $table_name = '';
        foreach ($metadatas as $metadata) {
            $table_name = str_replace(array('dtb_', 'mtb_'), '', $metadata->table['name']);
            if ($table == $table_name) {
                $className = $metadata->getName();
                break;
            }
        }
        $Repository = $app['orm.em']->getRepository($className);
        $Results = array();
        // TODO LIMIT, OFFSET が必要
        foreach ($Repository->findAll() as $Entity) {
            $Results[] = $Entity->toArray();
        }

        return $this->getWrapperedResponseBy($app, array($table_name => $Results));
    }

    public function find(Application $app, Request $request, $table = null, $id = 0)
    {
        $metadatas = $app['orm.em']->getMetadataFactory()->getAllMetadata();
        $className = '';
        $table_name = '';
        foreach ($metadatas as $metadata) {
            $table_name = str_replace(array('dtb_', 'mtb_'), '', $metadata->table['name']);
            if ($table == $table_name) {
                $className = $metadata->getName();
                break;
            }
        }
        $Repository = $app['orm.em']->getRepository($className);
        $Results = $Repository->find($id);

        return $this->getWrapperedResponseBy($app, array($table_name => $Results->toArray()));
    }

    public function create(Application $app, Request $request, $table = null)
    {
        $metadatas = $app['orm.em']->getMetadataFactory()->getAllMetadata();
        $className = '';
        $table_name = '';
        foreach ($metadatas as $metadata) {
            $table_name = str_replace(array('dtb_', 'mtb_'), '', $metadata->table['name']);
            if ($table == $table_name) {
                $className = $metadata->getName();
                break;
            }
        }

        $Entity = new $className;
        $Entity->setPropertiesFromArray($request->request->all());
        try {
            $app['orm.em']->persist($Entity);
            $app['orm.em']->flush($Entity);
            $Response = $app['oauth2.server.resource']->getResponse();
            $Response->headers->set("Location", $app->url('api_operation_find',
                                                          array(
                                                              'table' => $table_name,
                                                              'id' => $Entity->getId()
                                                          )
            ));
            return $this->getWrapperedResponseBy($app, null, 201);
        } catch (\Exception $e) {
            return $this->getWrapperedResponseBy($app, $this->getErrors(), 400);
        }
    }

    public function update(Application $app, Request $request, $table = null, $id = 0)
    {
        $metadatas = $app['orm.em']->getMetadataFactory()->getAllMetadata();
        $className = '';
        $table_name = '';
        foreach ($metadatas as $metadata) {
            $table_name = str_replace(array('dtb_', 'mtb_'), '', $metadata->table['name']);
            if ($table == $table_name) {
                $className = $metadata->getName();
                break;
            }
        }

        $Entity = $Repository->find($id);
        $Entity->setPropertiesFromArray($request->request->all(), array('id'));
        try {
            $app['orm.em']->persist($Entity);
            $app['orm.em']->flush($Entity);
            return $this->getWrapperedResponseBy($app, array($table_name => $Entity->toArray()), 200);
        } catch (\Exception $e) {
            return $this->getWrapperedResponseBy($app, $this->getErrors(), 400);
        }
    }

    public function delete(Application $app, Request $request, $table = null, $id = 0)
    {
        $metadatas = $app['orm.em']->getMetadataFactory()->getAllMetadata();
        $className = '';
        $table_name = '';
        foreach ($metadatas as $metadata) {
            $table_name = str_replace(array('dtb_', 'mtb_'), '', $metadata->table['name']);
            if ($table == $table_name) {
                $className = $metadata->getName();
                break;
            }
        }
        $Repository = $app['orm.em']->getRepository($className);
        $Results = $Repository->find($id);
        try {
            // TODO 削除可能かどうかのチェック
            $Results->setDelFlg(1);
        } catch (\Exception $e) {
            return $this->getWrapperedResponseBy($app, $this->getErrors(), 400);
        }

        return $this->getWrapperedResponseBy($app, null, 204);
    }
}
