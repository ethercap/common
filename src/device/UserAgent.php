<?php

namespace ethercap\common\device;

use Yii;
use yii\base\Component;

class UserAgent extends Component
{
    public $platform;
    public $browser;
    public $browserversion;
    public $mobile;
    public $robot;
    public $agent;

    public static $platforms = array(
        'windows nt 6.2' => 'Win8',
        'windows nt 6.1' => 'Win7',
        'windows nt 6.0' => 'Win Longhorn',
        'windows nt 5.2' => 'Win2003',
        'windows nt 5.0' => 'Win2000',
        'windows nt 5.1' => 'WinXP',
        'windows nt 4.0' => 'Windows NT 4.0',
        'winnt4.0' => 'Windows NT 4.0',
        'winnt 4.0' => 'Windows NT',
        'winnt' => 'Windows NT',
        'windows 98' => 'Win98',
        'win98' => 'Win98',
        'windows 95' => 'Win95',
        'win95' => 'Win95',
        'windows' => 'Unknown Windows OS',
        'os x' => 'MacOS X',
        'ppc mac' => 'Power PC Mac',
        'freebsd' => 'FreeBSD',
        'ppc' => 'Macintosh',
        'linux' => 'Linux',
        'debian' => 'Debian',
        'sunos' => 'Sun Solaris',
        'beos' => 'BeOS',
        'apachebench' => 'ApacheBench',
        'aix' => 'AIX',
        'irix' => 'Irix',
        'osf' => 'DEC OSF',
        'hp-ux' => 'HP-UX',
        'netbsd' => 'NetBSD',
        'bsdi' => 'BSDi',
        'openbsd' => 'OpenBSD',
        'gnu' => 'GNU/Linux',
        'unix' => 'Unknown Unix OS',
        'ios' => 'IOS',
        'okhttp' => 'Android',
        'android' => 'Android',
    );

    // 不要改动这个数组的顺序,很多浏览器会返回多个浏览器类型, 所以我们想要检测到子类型
    public static $browsers = array(
        'Flock' => 'Flock',
        'Chrome' => 'Chrome',
        'Opera' => 'Opera',
        'MSIE' => 'IE',
        'Internet Explorer' => 'IE',
        'Shiira' => 'Shiira',
        'Firefox' => 'Firefox',
        'Chimera' => 'Chimera',
        'Phoenix' => 'Phoenix',
        'Firebird' => 'Firebird',
        'Camino' => 'Camino',
        'Netscape' => 'Netscape',
        'OmniWeb' => 'OmniWeb',
        'Safari' => 'Safari',
        'Mozilla' => 'Mozilla',
        'Konqueror' => 'Konqueror',
        'icab' => 'iCab',
        'Lynx' => 'Lynx',
        'Links' => 'Links',
        'hotjava' => 'HotJava',
        'amaya' => 'Amaya',
        'IBrowse' => 'IBrowse',
    );

    public static $mobiles = array(
        'mobileexplorer' => 'Mobile Explorer',
        'palmsource' => 'Palm',
        'palmscape' => 'Palmscape',

        // Phones and Manufacturers
        'motorola' => 'Motorola',
        'nokia' => 'Nokia',
        'palm' => 'Palm',
        'iphone' => 'Apple iPhone',
        'ipad' => 'iPad',
        'ipod' => 'Apple iPod Touch',
        'sony' => 'Sony Ericsson',
        'ericsson' => 'Sony Ericsson',
        'blackberry' => 'BlackBerry',
        'cocoon' => 'O2 Cocoon',
        'blazer' => 'Treo',
        'lg' => 'LG',
        'amoi' => 'Amoi',
        'xda' => 'XDA',
        'mda' => 'MDA',
        'vario' => 'Vario',
        'htc' => 'HTC',
        'samsung' => 'Samsung',
        'sharp' => 'Sharp',
        'sie-' => 'Siemens',
        'alcatel' => 'Alcatel',
        'benq' => 'BenQ',
        'ipaq' => 'HP iPaq',
        'mot-' => 'Motorola',
        'playstation portable' => 'PlayStation Portable',
        'hiptop' => 'Danger Hiptop',
        'nec-' => 'NEC',
        'panasonic' => 'Panasonic',
        'philips' => 'Philips',
        'sagem' => 'Sagem',
        'sanyo' => 'Sanyo',
        'spv' => 'SPV',
        'zte' => 'ZTE',
        'sendo' => 'Sendo',

        // Operating Systems
        'symbian' => 'Symbian',
        'SymbianOS' => 'SymbianOS',
        'elaine' => 'Palm',
        'series60' => 'Symbian S60',
        'windows ce' => 'Windows CE',

        // Browsers
        'obigo' => 'Obigo',
        'netfront' => 'Netfront Browser',
        'openwave' => 'Openwave Browser',
        'mobilexplorer' => 'Mobile Explorer',
        'operamini' => 'Opera Mini',
        'opera mini' => 'Opera Mini',

        // Other
        'digital paths' => 'Digital Paths',
        'avantgo' => 'AvantGo',
        'xiino' => 'Xiino',
        'novarra' => 'Novarra Transcoder',
        'vodafone' => 'Vodafone',
        'docomo' => 'NTT DoCoMo',
        'o2' => 'O2',

        // Fallback
        'mobile' => 'Generic Mobile',
        'wireless' => 'Generic Mobile',
        'j2me' => 'Generic Mobile',
        'midp' => 'Generic Mobile',
        'cldc' => 'Generic Mobile',
        'up.link' => 'Generic Mobile',
        'up.browser' => 'Generic Mobile',
        'smartphone' => 'Generic Mobile',
        'cellphone' => 'Generic Mobile',
    );

    // 只列举几个比较有名的robot
    public static $robots = array(
        'googlebot' => 'Googlebot',
        'msnbot' => 'MSNBot',
        'slurp' => 'Inktomi Slurp',
        'yahoo' => 'Yahoo',
        'askjeeves' => 'AskJeeves',
        'fastcrawler' => 'FastCrawler',
        'infoseek' => 'InfoSeek Robot 1.0',
        'lycos' => 'Lycos',
        'yaiduspider' => 'Baidu',
        'youdaoBot' => 'YoudaoBot',
        'sogou' => 'Sogou',
        'sosospider' => 'Soso',
        '360spider' => '360spider',
    );

    public function init()
    {
        parent::init();
        $headers = Yii::$app->request->headers;
        if ($headers->has('User-Agent')) {
            $this->agent = $headers->get('User-Agent');
        }
        $this->setBrowser();
        $this->setPlatform();
        $this->setMobile();
        $this->setRobot();
    }

    private function setBrowser()
    {
        $this->browserversion = '';
        $this->browser = '';
        foreach (self::$browsers as $key => $val) {
            if (preg_match('|'.preg_quote($key).".*?([0-9\.]+)|i", $this->agent, $match)) {
                $this->browserversion = $match[1];
                $this->browser = $val;
            }
        }
    }

    private function setPlatform()
    {
        $this->platform = '';
        foreach (self::$platforms as $key => $val) {
            if (preg_match('/'.preg_quote($key).'/i', $this->agent)) {
                $this->platform = $val;
                return;
            }
        }
    }

    private function setMobile()
    {
        $this->mobile = '';
        foreach (self::$mobiles as $key => $val) {
            if (preg_match('/'.preg_quote($key).'/i', $this->agent)) {
                $this->mobile = $val;
                return;
            }
        }
    }

    private function setRobot()
    {
        $this->robot = '';
        foreach (self::$robots as $key => $val) {
            if (preg_match('/'.preg_quote($key).'/i', $this->agent)) {
                $this->robot = $val;
                return;
            }
        }
    }
}
