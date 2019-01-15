<?php
/**
 * @see http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace ethercap\common\log;

use yii\log\Target;
use ethercap\curl\CurlHttp;

/**
 * DingTarget Send a message to the ding Talk group by the chat robot.
 *
 * ```php
 * 'components' => [
 *     'log' => [
 *          'targets' => [
 *              [
 *                  'class' => 'common\log\DingTarget',
 *                  'levels' => ['info'],
 *                  'logVars' => [],
 *                  'categories' => ['ding.*'],
 *                  'accessToken' => 'token',
 *              ],
 *          ],
 *     ],
 * ],
 * ```
 *
 *
 * @author YangGuoShuai
 */
class DingTarget extends Target
{
    /**
     * @var string accessToken of Dingtalk robot
     */
    public $accessToken = '';

    /**
     * Sends log messages to specified Dingtalk robot.
     */
    public function export()
    {
        $serverName = $_SERVER['SERVER_NAME'];

        foreach ($this->messages as $message) {
            $content = is_array($message[0]) ? json_encode($message[0]) : $message[0];
            $category = $message[2];
            $ret = (new CurlHttp([
                'host' => 'oapi.dingtalk.com',
                'protocol' => 'https',
            ]))->setPostJson()->httpExec('/robot/send?access_token='.$this->accessToken, [
                'msgtype' => 'text',
                'text' => ['content' => '机器：'.$serverName.'类别：'.$category."\n日志内容:".$content],
            ]);
        }
    }
}
