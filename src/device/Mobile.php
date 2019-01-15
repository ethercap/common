<?php

namespace ethercap\common\device;

use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;

class Mobile extends Component
{
    //机型
    public $model;
    //系统型号
    public $system;
    //系统版本
    public $sysversion;
    //应用
    public $campaign;
    //来源
    public $source;
    //app版本
    public $appversion = '';
    //uuid
    public $uuid;

    const SYSTEM_H5 = 0;
    const SYSTEM_IOS = 1;
    const SYSTEM_ANDROID = 2;
    const SYSTEM_SMALLAPP = 3;

    private $ua;

    public static $systemDesc = [
        self::SYSTEM_H5 => 'h5',
        self::SYSTEM_IOS => 'ios',
        self::SYSTEM_ANDROID => 'android',
        self::SYSTEM_SMALLAPP => 'smallapp',
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->setSys();
        $this->setUuid();
        $this->setCampaign();
        $this->setSource();
        $this->setAppversion();
    }

    //获取机型
    private function setSys()
    {
        $this->system = self::SYSTEM_H5;
        $this->sysversion = '';
        $this->model = '';
        $utm_media = Yii::$app->request->get('utm_media', '');
        if (!empty($utm_media)) {
            $arr = explode('|', $utm_media);
            $this->system = self::getSysByStr($arr[0]);
            if (count($arr) > 1) {
                $this->sysversion = $arr[1];
            }
            if (count($arr) > 2) {
                $this->model = $arr[2];
            }
        } elseif ($os = Yii::$app->request->post('os', '')) {
            //兼容历史，可能会通过post来传输系统类型
            $arr = explode('|', $os);
            $this->system = self::getSysByStr($arr[0]);
        } else {
            $this->ua = new UserAgent();
            $this->system = self::getSysByStr($this->ua->platform);
        }
    }

    private function setUuid()
    {
        $this->uuid = trim(Yii::$app->request->get('utm_content', ''));
    }

    private function setCampaign()
    {
        $this->campaign = trim(Yii::$app->request->get('utm_campaign', ''));
    }

    private function setSource()
    {
        $this->source = trim(Yii::$app->request->get('utm_source', ''));
        if (empty($this->source)) {
            $this->source = Yii::$app->request->get('from');
        }
    }

    private function setAppversion()
    {
        $version = trim(Yii::$app->request->get('utm_term', ''));
        if (empty($version)) {
            $version = Yii::$app->request->post('version', '');
        }
        !empty($version) && $this->appversion = $version;
    }

    public static function getSysByStr($string)
    {
        if (preg_match('/(ios|iphone|ipad|apple)/i', $string)) {
            return self::SYSTEM_IOS;
        } elseif (preg_match('/android/i', $string)) {
            return self::SYSTEM_ANDROID;
        } elseif (preg_match('/smallapp/i', $string)) {
            return self::SYSTEM_SMALLAPP;
        }
        return self::SYSTEM_H5;
    }

    public function isIOS()
    {
        return $this->system == self::SYSTEM_IOS;
    }

    public function isAndroid()
    {
        return $this->system == self::SYSTEM_ANDROID;
    }

    public function isApp()
    {
        return $this->isIOS() || $this->isAndroid();
    }

    public function isSmallApp()
    {
        return $this->system == self::SYSTEM_SMALLAPP;
    }

    public function isH5()
    {
        return $this->system == self::SYSTEM_H5;
    }

    public function getSysDesc()
    {
        return ArrayHelper::getValue(self::$systemDesc, $this->system, 'h5');
    }

    public function compareVersion($version)
    {
        return version_compare($this->appversion, $version);
    }
}
