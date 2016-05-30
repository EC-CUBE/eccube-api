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
use Eccube\Entry\AbstractEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EccubeApiCRUDController extends AbstractApiController
{

    public function findAll(Application $app, Request $request, $table = null)
    {
        $metadata = $this->findMetadata($app, $table);
        if (!is_object($metadata)) {
            throw new NotFoundHttpException();
        }
        $className = $metadata->getName();

        $Repository = $app['orm.em']->getRepository($className);
        $Results = array();
        // TODO LIMIT, OFFSET が必要
        foreach ($Repository->findAll() as $Entity) {
            $Results[] = $Entity->toArray();
        }

        return $this->getWrapperedResponseBy($app, array($table => $Results));
    }

    public function find(Application $app, Request $request, $table = null, $id = 0)
    {
        $metadata = $this->findMetadata($app, $table);
        if (!is_object($metadata)) {
            throw new NotFoundHttpException();
        }
        $className = $metadata->getName();

        $Repository = $app['orm.em']->getRepository($className);
        $Results = $Repository->find($id);

        return $this->getWrapperedResponseBy($app, array($table => $Results->toArray()));
    }

    public function create(Application $app, Request $request, $table = null)
    {
        $metadata = $this->findMetadata($app, $table);
        if (!is_object($metadata)) {
            throw new NotFoundHttpException();
        }
        $className = $metadata->getName();

        $Entity = new $className;
        $Entity->setPropertiesFromArray($request->request->all()); // TODO not null な外部リレーションの対応
        try {
            $app['orm.em']->persist($Entity);
            $app['orm.em']->flush($Entity);
            $Response = $app['oauth2.server.resource']->getResponse();
            $Response->headers->set("Location", $app->url('api_operation_find',
                                                          array(
                                                              'table' => $table,
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
        $metadata = $this->findMetadata($app, $table);
        if (!is_object($metadata)) {
            throw new NotFoundHttpException();
        }
        $className = $metadata->getName();

        $Entity = $Repository->find($id);
        $Entity->setPropertiesFromArray($request->request->all(), array('id')); // TODO not null な外部リレーションの対応
        try {
            $app['orm.em']->persist($Entity);
            $app['orm.em']->flush($Entity);
            return $this->getWrapperedResponseBy($app, array($table => $Entity->toArray()), 200);
        } catch (\Exception $e) {
            return $this->getWrapperedResponseBy($app, $this->getErrors(), 400);
        }
    }

    public function delete(Application $app, Request $request, $table = null, $id = 0)
    {
        $metadata = $this->findMetadata($app, $table);
        if (!is_object($metadata)) {
            throw new NotFoundHttpException();
        }
        $className = $metadata->getName();

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

    /**
     * テーブル名から Metadata を検索する.
     *
     * テーブル名の, `dtb_`, `mtb_` といった prefix は省略可能.
     *
     * @param Application $app
     * @param string $table テーブル名.
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadata
     */
    protected function findMetadata(Application $app, $table)
    {
        $metadatas = $app['orm.em']->getMetadataFactory()->getAllMetadata();
        foreach ($metadatas as $metadata) {
            $table_name = $metadata->table['name'];
            if ($table == $table_name
                || $table == $this->shortTableName($table_name)) {
                return $metadata;
            }
        }
        return  null;
    }

    /**
     * `dtb_`, `mtb_` といった prefix を除いたテーブル名を返す.
     *
     * @param string $table テーブル名
     * @return string prefix を除いたテーブル名
     */
    protected function shortTableName($table)
    {
        return str_replace(array('dtb_', 'mtb_'), '', $table);
    }
}
