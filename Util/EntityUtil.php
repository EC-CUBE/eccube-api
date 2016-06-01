<?php

namespace Plugin\EccubeApi\Util;

use Eccube\Application;
use Eccube\Entity\AbstractEntity;
use Eccube\Util\EntityUtil as BaseEntityUtil;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Proxy\Proxy;

/**
 * Api用の EntityUtil.
 *
 * 本体に取り込む際には, \Eccube\Util\EntityUtil にマージする
 */
class EntityUtil extends BaseEntityUtil
{
    /**
     * テーブル名から Metadata を検索する.
     *
     * テーブル名の, `dtb_`, `mtb_` といった prefix は省略可能.
     *
     * @param Application $app
     * @param string $table テーブル名.
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadata
     */
    public static function findMetadata(Application $app, $table)
    {
        $metadatas = $app['orm.em']->getMetadataFactory()->getAllMetadata();
        foreach ($metadatas as $metadata) {
            $table_name = $metadata->table['name'];
            if ($table == $table_name
                || $table == self::shortTableName($table_name)) {
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
    public static function shortTableName($table)
    {
        return str_replace(array('dtb_', 'mtb_'), '', $table);
    }

    /**
     * JSON Respoinse 用の配列を返します.
     *
     * @see AbstractEntity::toArray()
     */
    public static function entityToArray(Application $app, AbstractEntity $Entity, array $excludeAttribute = array('__initializer__', '__cloner__', '__isInitialized__'))
    {
        $Reflect = new \ReflectionClass($Entity);
        if ($Entity instanceof Proxy) {
            $Reflect = $Reflect->getParentClass();
        }
        $Properties = $Reflect->getProperties();
        $Results = array();
        foreach ($Properties as $Property) {
            $Property->setAccessible(true);
            $name = $Property->getName();
            if (in_array($name, $excludeAttribute)) {
                continue;
            }
            $PropertyValue = $Property->getValue($Entity);
            if ($PropertyValue instanceof \DateTime) {
                $Results[$name] = $PropertyValue->format(\Datetime::ATOM);
            } elseif ($PropertyValue instanceof AbstractEntity) {
                // Entity の場合は [id => value] の配列を返す
                $Results[$name] = self::getEntityIdentifierAsArray($app, $PropertyValue);
            } elseif ($PropertyValue instanceof PersistentCollection) {
                // Collection の場合は ID を持つオブジェクトの配列を返す
                $Collections = $PropertyValue->getValues();
                foreach ($Collections as $Child) {
                    $Results[$name][] = self::getEntityIdentifierAsArray($app, $Child);
                }
            } else {
                $Results[$name] = $PropertyValue;
            }
        }
        return $Results;
    }

    /**
     * Entity のID情報を配列で返します.
     */
    public static function getEntityIdentifierAsArray(Application $app, AbstractEntity $Entity)
    {
        $metadata = $app['orm.em']->getMetadataFactory()->getMetadataFor(get_class($Entity));
        $idField = '';
        $Result = array();
        foreach ($metadata->fieldMappings as $field => $mapping) {
            if (array_key_exists('id', $mapping) === true && $mapping['id'] === true) {
                $idField = $mapping['fieldName'];
                $PropReflect = new \ReflectionClass($Entity);
                if ($Entity instanceof Proxy) {
                    // Doctrine Proxy の場合は親クラスを取得
                    $PropReflect = $PropReflect->getParentClass();
                }
                $IdProperty = $PropReflect->getProperty($idField);
                $IdProperty->setAccessible(true);
                $Result[$idField] = $IdProperty->getValue($Entity);
            }
        }

        return $Result;
    }

    /**
     * srcProperties のフィールドを destEntity にコピーします.
     */
    public static function copyRelatePropertiesFromArray(Application $app, AbstractEntity $destEntity, array $srcProperties, array $excludeAttribute = array('__initializer__', '__cloner__', '__isInitialized__'))
    {
        $metadata = $app['orm.em']->getMetadataFactory()->getMetadataFor(get_class($destEntity));
        $fieldMappings = $metadata->fieldMappings;
        $associationMappings = $metadata->associationMappings;
        $reflClass = $metadata->reflClass;
        $associationProperties = array_keys($associationMappings);

        $Reflect = new \ReflectionClass($destEntity);
        if ($destEntity instanceof Proxy) {
            // Doctrine Proxy の場合は親クラスを取得
            $Reflect = $Reflect->getParentClass();
        }
        $Properties = $Reflect->getProperties();
        foreach ($Properties as $Property) {
            $Property->setAccessible(true);
            $name = $Property->getName();

            if (in_array($name, $excludeAttribute) || !array_key_exists($name, $srcProperties)) {
                continue;
            }

            // 外部キーのオブジェクトをセットする
            if (in_array($name, $associationProperties)) {
                if (!is_array($srcProperties[$name])) {
                    // 値が空の場合はスキップ
                    continue;
                }

                if (array_values($srcProperties[$name]) === $srcProperties[$name]) { // 配列 or 連想配列のチェック
                    // 配列の場合は Collection
                    $Collections = array();
                    foreach ($srcProperties[$name] as $manyProperty) {
                        $assocEntity = $app['orm.em']->getRepository($associationMappings[$name]['targetEntity'])->findOneBy($manyProperty);
                        $Collections[] = $assocEntity;
                    }
                    $Property->setValue($destEntity, $Collections);
                    continue;
                } else {
                    // 連想配列の場合は Entity
                    $assocEntity = $app['orm.em']->getRepository($associationMappings[$name]['targetEntity'])->find($srcProperties[$name]);
                    $Property->setValue($destEntity, $assocEntity);
                }
                // TODO 複合キーの対応
                continue;
            }

            if (!array_key_exists($name, $fieldMappings)) {
                continue;
            }

            // フィールドをセットする
            switch ($fieldMappings[$name]['type']) {
                case 'datetime':
                case 'datetimetz':
                    $Property->setValue($destEntity, new \Datetime($srcProperties[$name]));
                    break;
                default:
                    $Property->setValue($destEntity, $srcProperties[$name]);
            }

        }
    }
}
