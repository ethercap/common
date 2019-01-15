<?php

namespace ethercap\common\helpers;

use yii\helpers\ArrayHelper;
use yii\base\InvalidParamException;
use yii\db\ActiveQuery;
use yii\db\Query;

/**
 * Class QueryHelper
 *
 * @package console\helpers
 *
 * @author caiwei
 *
 * 由于Yii原生的query::batch有性能问题的, 因此改进batch方法, 用于解决性能问题。
 * 调用形式
 * $query = ActiveQuery::find()->where();
 * while (!empty($models = QueryHelper::batch($query, 100))) {
 *      foreach ($models as $model) {
 *          //对于model的处理逻辑
 *      }
 * }
 * 注意:
 * 1、当多条sql同时使用QueryHelper, 需要使用$group参数, 用于区分, 默认是default
 *      QueryHelper::batch($query, $limit, $group)
 * 2、当需要进行划分区间的字段不是id, 需要使用$primaryFiled参数指定。
 *      QueryHelper::batch($query, $limit, $group, $primaryFiled)
 *  *** 需要注意, QueryHelper目前不支持对limit类型的query
 */
class QueryHelper
{
    protected static $_groupIdArr = [];

    /**
     * @param $query ActiveQuery 查询sql
     * @param int    $limit        获得模型个数
     * @param string $group        当多个sql同时使用QueryHelper, 需要使用该参数进行划分
     * @param string $primaryFiled 划分区间使用的字段
     *
     * @return array
     */
    public static function batch($templateQuery, $limit = 100, $group = 'default', $primaryFiled = 'id')
    {
        if ($templateQuery instanceof Query) {
            try {
                $query = clone $templateQuery;
                $query->orderBy([$primaryFiled => SORT_ASC]);
                empty(self::getMaxId($group)) && self::setMaxId($query->max($primaryFiled), $group);
                empty(self::getMinId($group)) && self::setMinId($query->min($primaryFiled), $group);
                $models = [];
                while (self::getMinId($group) <= self::getMaxId($group)) {
                    $maxId = self::getMinId($group) + $limit;
                    $modelTemps = (clone $query)->andWhere(['>=', $primaryFiled, self::getMinId($group)])
                        ->andWhere(['<', $primaryFiled, $maxId])->all();
                    foreach ($modelTemps as $model) {
                        $models[] = $model;
                        if (count($models) == $limit) {
                            // AR支持数组形式取值, 使用数组形式取值, 可以兼容asArray
                            self::setMinId($model[$primaryFiled] + 1, $group);
                            break 2;
                        }
                    }
                    self::setMinId($maxId, $group);
                }
                self::recycle($models, $group);
                return $models;
            } catch (\Exception $e) {
                throw new InvalidParamException('参数错误');
            }
        } else {
            throw new InvalidParamException('参数错误');
        }
    }

    /**
     * 当查询彻底结束, 回收临时数据
     *
     * @param $models
     * @param $group
     */
    protected function recycle($models, $group)
    {
        if (empty($models) && isset(self::$_groupIdArr[$group])) {
            unset(self::$_groupIdArr[$group]);
        }
    }

    protected function getMaxId($group)
    {
        return ArrayHelper::getValue(self::$_groupIdArr, $group . '.maxId', 0);
    }

    protected function setMaxId($value, $group)
    {
        self::$_groupIdArr[$group]['maxId'] = (int) $value;
    }

    protected function getMinId($group)
    {
        return ArrayHelper::getValue(self::$_groupIdArr, $group . '.minId', 0);
    }

    protected function setMinId($value, $group)
    {
        self::$_groupIdArr[$group]['minId'] = (int) $value;
    }
}
