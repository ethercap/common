<?php

namespace ethercap\common\helpers;

class DingBot extends \ethercap\curl\BaseCurlHttp
{
    public $protocol = 'https';
    public $host = 'oapi.dingtalk.com';
    public $access_token = null;

    public function init()
    {
        if (empty($this->access_token)) {
            throw new \Exception('必须要配置access_token');
        }
        return parent::init();
    }

    public function sendText($text, $atMobiles = [], $atAll = false)
    {
        $arr = [
            'msgtype' => 'text',
            'text' => ['content' => $text],
        ];
        return $this->sendRaw($arr, $atMobiles, $atAll);
    }

    public function sendMarkDown($markdown = '', $title = '', $atMobiles = [], $atAll = false)
    {
        $arr = [
            'msgtype' => 'markdown',
            'markdown' => [
                'title' => $title,
                'text' => $markdown,
            ],
        ];
        return $this->sendRaw($arr, $atMobiles, $atAll);
    }

    public function sendLink($text, $picUrl, $link, $title = '', $atMobiles = [], $atAll = false)
    {
        $arr = [
            'msgtype' => 'link',
            'link' => [
                'text' => $text,
                'title' => $title,
                'picUrl' => $picUrl,
                'messageUrl' => $link,
            ],
        ];
        return $this->sendRaw($arr, $atMobiles, $atAll);
    }

    public function sendActionCard($title, $text, $singleTitle, $singleURL, $btns = [], $btnOrientation = 0, $hideAvatar = 0)
    {
        $arr = [
            'msgtype' => 'actionCard',
            'actionCard' => [
                //首屏会话透出的展示内容
                'title' => $title,
                //markdown格式的消息
                'text' => $text,
                //0-正常发消息者头像,1-隐藏发消息者头像
                'hideAvatar' => $hideAvatar,
                //单个按钮的方案。(设置此项和singleURL后btns无效。)
                'singleTitle' => $singleTitle,
                'singleURL' => $singleURL,
                'btns' => $btns,
                //0-按钮竖直排列，1-按钮横向排列
                'btnOrientation' => $btnOrientation,
            ],
        ];
        return $this->sendRaw($arr);
    }

    public function sendFeed($feeds = [])
    {
        $arr = [
            'msgtype' => 'feedCard',
            'feedCard' => ['links' => $feeds],
        ];
        return $this->sendRaw($arr);
    }

    public function sendRaw($arr, $atMobiles = [], $atAll = false)
    {
        if (empty($arr['at'])) {
            $arr['at'] = [
                'atMobiles' => $atMobiles,
                'isAtAll' => $atAll,
            ];
        }
        return  $this->setPostJson()->httpExec('/robot/send?access_token='.$this->access_token, $arr);
    }
}
