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
use Plugin\EccubeApi\Util\EntityUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EccubeApiCRUDController extends AbstractApiController
{

    /**
     * すべてデータを返す.
     */
    public function findAll(Application $app, Request $request, $table = null)
    {
        $metadata = EntityUtil::findMetadata($app, $table);
        if (!is_object($metadata)) {
            throw new NotFoundHttpException();
        }
        $className = $metadata->getName();

        $Repository = $app['orm.em']->getRepository($className);
        $Results = array();
        // TODO LIMIT, OFFSET が必要
        foreach ($Repository->findAll() as $Entity) {
            $Results[] = EntityUtil::entityToArray($app, $Entity);
        }

        return $this->getWrapperedResponseBy($app, array($table => $Results));
    }

    /**
     * IDを指定してエンティティを検索する
     */
    public function find(Application $app, Request $request, $table = null, $id = 0)
    {
        $metadata = EntityUtil::findMetadata($app, $table);
        if (!is_object($metadata)) {
            throw new NotFoundHttpException();
        }
        $className = $metadata->getName();

        $Repository = $app['orm.em']->getRepository($className);
        $Results = $Repository->find($id);

        return $this->getWrapperedResponseBy($app, array($table => EntityUtil::entityToArray($app, $Results)));
    }

    /**
     * エンティティを生成する.
     */
    public function create(Application $app, Request $request, $table = null)
    {
        $this->verifyRequest($app);
        $metadata = EntityUtil::findMetadata($app, $table);
        if (!is_object($metadata)) {
            throw new NotFoundHttpException();
        }
        $className = $metadata->getName();
        $Entity = new $className;
        EntityUtil::copyRelatePropertiesFromArray($app, $Entity, $request->request->all());

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
            return $this->getWrapperedResponseBy($app, array(), 201);
        } catch (\Exception $e) {
            $this->addErrors($app, 400, $e->getMessage());
            return $this->getWrapperedResponseBy($app, $this->getErrors(), 400);
        }
    }

    /**
     * エンティティを更新する.
     */
    public function update(Application $app, Request $request, $table = null, $id = 0)
    {
        $metadata = EntityUtil::findMetadata($app, $table);
        if (!is_object($metadata)) {
            throw new NotFoundHttpException();
        }
        $className = $metadata->getName();

        $Entity = $app['orm.em']->getRepository($className)->find($id);
        EntityUtil::copyRelatePropertiesFromArray($app, $Entity, $request->request->all());
        try {
            $app['orm.em']->flush($Entity);
            return $this->getWrapperedResponseBy($app, null, 204);
        } catch (\Exception $e) {
            $this->addErrors($app, 400, $e->getMessage());
            return $this->getWrapperedResponseBy($app, $this->getErrors(), 400);
        }
    }

    /**
     * エンティティを削除する.
     */
    public function delete(Application $app, Request $request, $table = null, $id = 0)
    {
        $metadata = EntityUtil::findMetadata($app, $table);
        if (!is_object($metadata)) {
            throw new NotFoundHttpException();
        }
        if (!array_key_exists('del_flg', $metadata->fieldMappings)) {
            // TODO エラーメッセージ
            return $this->getWrapperedResponseBy($app, $this->getErrors(), 405);
        }
        $className = $metadata->getName();

        $Repository = $app['orm.em']->getRepository($className);
        $Results = $Repository->find($id);
        try {
            $Results->setDelFlg(Constant::ENABLED);
        } catch (\Exception $e) {
            return $this->getWrapperedResponseBy($app, $this->getErrors(), 400);
        }

        return $this->getWrapperedResponseBy($app, null, 204);
    }
}
