<?php

namespace ethercap\common\helpers;

use Yii;

/**
 * 提示文本处理
 *
 * @package frameworks
 * @subpackage core
 */

/**
 * 提示文本处理
 *
 * @package frameworks
 * @subpackage core
 */
class SysMsg
{
    /**
     * 提示信息模版定义
     */
    protected static $textTemplates = array();

    /**
     * 注册提示信息
     *
     * @param string $index
     * @param string $textTemplate
     */
    public static function register($index, $textTemplate, $code = 1)
    {
        if (isset(self::$textTemplates[$index])) {
            Yii::warning('系统消息已定义：'.$index, 'sysmsg');
        } else {
            self::$textTemplates[$index] = array(
                'text' => $textTemplate,
                'code' => $code,
            );
        }
    }

    /**
     * 设置错误信息
     *
     * @param String|Array(String $msg, Mixed args1, args2, ..) $msg
     */
    public static function getErrMsg($msg = null)
    {
        $args = array();
        if (!$msg) {
            $msg = 'A_GENERAL_ERR';
        } elseif (is_array($msg)) {
            $args = array_slice($msg, 1);
            $msg = $msg[0];
        }
        return SysMsg::get($msg, $args);
    }

    /**
     * 返回提示信息文本
     *
     * @param string  $index
     * @param Array() $args
     *
     * @return string
     */
    public static function get($index, $args = array())
    {
        if (!is_array($args)) {
            $args = array($args);
        }
        if (is_array($index)) {
            foreach ($index as $key => $val) {
                if (isset($args[$key])) {
                    $index[$key] = self::get($val, $args[$key]);
                } else {
                    $index[$key] = self::get($val);
                }
            }
            return $index;
        }
        if (isset(self::$textTemplates[$index])) {
            $index = self::$textTemplates[$index]['text'];
        }
        if ($args) {
            return call_user_func_array('sprintf', array_merge([$index], $args));
        } else {
            return $index;
        }
    }

    public static function getErrData($index, $errData = [])
    {
        $temp = $index;
        if (is_array($index)) {
            $temp = $index[0];
        }

        if ($index instanceof \yii\base\Model) {
            $temp = 'A_GENERAL_ERR';
            $errors = $index->getFirstErrors();
            if (!empty($errors)) {
                return self::getErrData(reset($errors));
            }
            $index = $temp;
        }

        $data = array(
            'code' => 1,
            'message' => $temp,
            'data' => $errData,
        );
        if (isset(self::$textTemplates[$temp])) {
            $data['code'] = self::$textTemplates[$temp]['code'];
            $data['message'] = self::getErrMsg($index);
        }
        return $data;
    }

    public static function getOkData($data = array(), $index = '')
    {
        $data = array(
            'code' => 0,
            'message' => '操作成功',
            'data' => $data,
        );
        if (isset(self::$textTemplates[$index])) {
            $data['message'] = self::get($index);
        }
        return $data;
    }

    public static function toFlash($type, $msg, $session = 'session')
    {
        if (!isset(\Yii::$app->$session)) {
            throw new \yii\base\InvalidConfigException('Session组件不存在，请检查');
        }
        $appSession = \Yii::$app->$session;
        if (!($appSession instanceof \yii\web\Session)) {
            throw new \yii\base\InvalidConfigException('Session组件不是\yii\web\Session的子类，请检查');
        }
        $appSession->setFlash($type, self::getErrData($msg)['message']);
    }
}
SysMsg::register('A_GENERAL_ERR', '操作失败');
SysMsg::register('A_GENERAL_OK', '操作成功');
