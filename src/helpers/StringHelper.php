<?php

namespace ethercap\common\helpers;

use Yii;
use yii\helpers\ArrayHelper;

class StringHelper extends \yii\helpers\StringHelper
{
    public static $password_blacklist = array('123456', '123456789', '000000', '111111', '123123', '5201314', '666666', '123321', '1314520', '1234567890', '888888', '1234567', '654321', '12345678', '520520', '7758521', '112233', '147258', '123654', '987654321', '88888888', '147258369', '666888', '5211314', '521521', 'a123456', 'zxcvbnm', '999999', '222222', '123123123', '1314521', '201314', 'woaini', '789456', '555555', 'qwertyuiop', '100200', '168168', 'qwerty', '258369', '456789', '110110', '789456123', '159357', '123789', '123456a', '121212', '456123', '987654', '111222', '1111111111', '7758258', '00000000', 'admin', 'administrator', '333333', '1111111', '369369', '888999', 'asdfgh', '11111111', 'woaini1314', '258258', '0123456789', '369258', 'aaaaaa', '778899', '0000000000', '0000000', '159753', 'abc123', '585858', 'asdfghjkl', '321654', '211314', '584520', 'abcdefg', '777777', '0123456', 'a123456789', '123654789', 'abc123456', '336699', 'abcdef', '518518', '888666', '708904', '135246', '12345678910', '147369', '110119', 'qq123456', '789789', '251314', '555666', '111111111', '123000', 'zxcvbn', 'qazwsx', '123456abc', 'hlj12345');

    //正则匹配一个电话是否为正确的电话号码
    public static function checkMobile($mobile)
    {
        if (preg_match("/^1[3-9]{1}\d{9}$/", $mobile)) {
            return true;
        }
        return false;
    }

    //正则匹配一个邮箱是否为正确的邮箱
    public static function checkEmail($email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }
        return false;
    }

    /*
     * 防止单条消息过长
     */
    public static function truncateMsg($msg, $len = 250)
    {
        $arridx = 0;
        $line = '';
        $subidx = 0;
        $count = 0;

        while ($subidx < strlen($msg)) {
            $uch = '';
            if ($count == $len - 2) {
                $line = $line . '..';
                break;
            }
            if ((ord($msg[$subidx]) & 0x80) == 0x00) {
                $uch .= $msg[$subidx];
                $subidx += 1;
                $count += 1;
            } elseif ((ord($msg[$subidx]) & 0xc0) == 0x80) {
                $subidx += 1;
                continue;
            } elseif ((ord($msg[$subidx]) & 0xe0) == 0xc0) {
                $uch .= $msg[$subidx];
                $subidx += 1;
                $uch .= $msg[$subidx];
                $subidx += 1;
                $count += 1;
            } elseif ((ord($msg[$subidx]) & 0xf0) == 0xe0) {
                $uch .= $msg[$subidx];
                $subidx += 1;
                $uch .= $msg[$subidx];
                $subidx += 1;
                $uch .= $msg[$subidx];
                $subidx += 1;
                $count += 1;
            } elseif ((ord($msg[$subidx]) & 0xf8) == 0xf0) {
                $uch .= $msg[$subidx];
                $subidx += 1;
                $uch .= $msg[$subidx];
                $subidx += 1;
                $uch .= $msg[$subidx];
                $subidx += 1;
                $uch .= $msg[$subidx];
                $subidx += 1;
                $count += 1;
            }

            $line .= $uch;
        }
        return $line;
    }

    public static function checkPasswdValid($password)
    {
        //判断密码长度
        if (empty($password) || strlen($password) < 6 || strlen($password) > 16) {
            return '密码长度应该在6-16位之间';
        }
        if (in_array($password, self::$password_blacklist)) {
            return '您的密码过于简单';
        }
        return false;
    }

    // 判断ip是否在某个范围内
    // This function takes 2 arguments, an IP address and a "range" in several
    // different formats.
    // Network ranges can be specified as:
    // 1. Wildcard format:     1.2.3.*
    // 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
    // 3. Start-End IP format: 1.2.3.0-1.2.3.255
    // The function will return true if the supplied IP is within the range.
    // Note little validation is done on the range inputs - it expects you to
    // use one of the above 3 formats.
    public static function isIPInRange($ip, $range)
    {
        if (strpos($range, '/') !== false) {
            // $range is in IP/NETMASK format
            list($range, $netmask) = explode('/', $range, 2);
            if (strpos($netmask, '.') !== false) {
                // $netmask is a 255.255.0.0 format
                $netmask = str_replace('*', '0', $netmask);
                $netmask_dec = ip2long($netmask);
                return (ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec);
            } else {
                // $netmask is a CIDR size block
                // fix the range argument
                $x = explode('.', $range);
                while (count($x) < 4) {
                    $x[] = '0';
                }
                list($a, $b, $c, $d) = $x;
                $range = sprintf('%u.%u.%u.%u', empty($a) ? '0' : $a, empty($b) ? '0' : $b, empty($c) ? '0' : $c, empty($d) ? '0' : $d);
                $range_dec = ip2long($range);
                $ip_dec = ip2long($ip);

                // Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
                //$netmask_dec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));

                // Strategy 2 - Use math to create it
                $wildcard_dec = pow(2, (32 - $netmask)) - 1;
                $netmask_dec = ~$wildcard_dec;

                return ($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec);
            }
        } elseif (strpos($range, '*') !== false || strpos($range, '-') !== false) {
            // range might be 255.255.*.* or 1.2.3.0-1.2.3.255
            if (strpos($range, '*') !== false) { // a.b.*.* format
                // Just convert to A-B format by setting * to 0 for A and 255 for B
                $lower = str_replace('*', '0', $range);
                $upper = str_replace('*', '255', $range);
                $range = "$lower-$upper";
            }

            if (strpos($range, '-') !== false) { // A-B format
                list($lower, $upper) = explode('-', $range, 2);
                $lower_dec = (float) sprintf('%u', ip2long($lower));
                $upper_dec = (float) sprintf('%u', ip2long($upper));
                $ip_dec = (float) sprintf('%u', ip2long($ip));
                return ($ip_dec >= $lower_dec) && ($ip_dec <= $upper_dec);
            }
            return false;
        } else {
            return $ip == $range;
        }
    }

    public static function getRealIp($default = null)
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return $default;
    }

    public static function mb_substr_replace($original, $replacement, $position, $length)
    {
        $startString = mb_substr($original, 0, $position, 'UTF-8');
        $endString = mb_substr($original, $position + $length, mb_strlen($original), 'UTF-8');
        $out = $startString . $replacement . $endString;
        return $out;
    }

    /**
     * 得到加星号的手机号 133****4444
     *
     * @params $number 手机号
     *
     * @return string 加过星号的
     */
    public static function getMaskMobile($number)
    {
        $masked = self::mb_substr_replace($number, '****', 3, 4);
        return $masked;
    }

    /**
     * 得到加星号的Weixin 133*********44
     *
     * @params $weixin 微信号
     *
     * @return string 加过星号的
     */
    public static function getMaskWeixin($weixin)
    {
        $mask = '';
        $weixinLen = mb_strlen($weixin);
        $maskedLen = (int) floor($weixinLen / 3);
        if ($maskedLen > 0) {
            $postfix = mb_substr($weixin, -$maskedLen);
            $weixin = mb_substr($weixin, 0, $maskedLen);
            $weixin .= str_pad($mask, $maskedLen, '*');
            $weixin .= $postfix;
        }
        return $weixin;
    }

    //每四位加一个空格，方便显示
    public static function formatCode($str)
    {
        $ret = '';
        for ($i = 0; $i < strlen($str); $i++) {
            if ($i % 4 == 0) {
                $ret .= ' ';
            }
            $ret .= $str[$i];
        }
        return trim($ret);
    }

    //显示日期
    public static function showDate($timestamp)
    {
        if (empty($timestamp)) {
            return '-';
        }
        return date('Y-m-d', $timestamp);
    }

    public static function randomString($length = 64)
    {
        $code = '';
        $charSet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_';
        $len = strlen($charSet);
        for ($i = 0; $i < $length; $i++) {
            $tmp = $charSet[rand() % $len];
            $code .= $tmp;
        }
        return $code;
    }

    public static function numToWord($num)
    {
        $chiNum = ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九'];
        $chiUni = ['', '十', '百', '千', '万', '亿', '十', '百', '千'];
        $num_str = (string) $num;

        $count = strlen($num_str);
        $last_flag = true; //上一个 是否为0
        $zero_flag = true; //是否第一个
        $temp_num = null; //临时数字

        $chiStr = ''; //拼接结果
        if ($count == 2) { //两位数
            $temp_num = $num_str[0];
            $chiStr = $temp_num == 1 ? $chiUni[1] : $chiNum[$temp_num].$chiUni[1];
            $temp_num = $num_str[1];
            $chiStr .= $temp_num == 0 ? '' : $chiNum[$temp_num];
        } elseif ($count > 2) {
            $index = 0;
            for ($i = $count - 1; $i >= 0; $i--) {
                $temp_num = $num_str[$i];
                if ($temp_num == 0) {
                    if (!$zero_flag && !$last_flag) {
                        $chiStr = $chiNum[$temp_num]. $chiStr;
                        $last_flag = true;
                    }
                } else {
                    $chiStr = $chiNum[$temp_num].$chiUni[$index % 9] .$chiStr;
                    $zero_flag = false;
                    $last_flag = false;
                }
                $index++;
            }
        } else {
            $chiStr = $chiNum[$num_str[0]];
        }
        return $chiStr;
    }

    /**
     * NOTE: 这个方法不能用在controller/module的init方法里
     * 获取独一无二的action uniq id
     *
     * @param string $delimeter
     *
     * @return string
     */
    public static function getUniqueActionId($delimeter = ':')
    {
        $appId = Yii::$app->id;
        $moduleId = Yii::$app->controller->module->id;
        $controllerId = Yii::$app->controller->id;
        $action = Yii::$app->controller->action->id;
        $arr = [$appId, $moduleId, $controllerId, $action];
        $key = implode($arr, $delimeter);
        return $key;
    }

    public static function isScien($num)
    {
        if (!is_string($num) || !is_numeric($num)) {
            return false;
        }
        return stripos($num, 'e') === false ? false : true;
    }

    /**
     * 获得过过滤后的中文字符
     *
     * @param string $str           待过滤字符串
     * @param string $defaultValue  没有中文字符的默认值
     * @param bool   $truncateRight true将过滤除中文字符以外的所有字符, false仅仅获得字符串最左边的连续中文字符
     *
     * @return string
     */
    public static function chinessCharacterFilter($str, $defaultValue = '', $truncateRight = false)
    {
        $ret = [];
        // \x{4e00}-\x{9fbb} 基本汉字 ; \x{9fa6}-\x{9fcb} 基本汉字补充 ;
        // \x{3400}-\x{4db5} 扩展a ; \x{20000}-\x{2a6d6} 扩展b; \x{2a700}-\x{2b734} 拓展c
        // \x{f900}-\x{fad9} 兼容汉字; \x{2f800}-\x{2fa1d} 兼容汉字;
        // 参考链接: https://blog.csdn.net/gywtzh0889/article/details/71083459
        $reg = '/[\x{4e00}-\x{9fa5}\x{9fa6}-\x{9fcb}\x{3400}-\x{4db5}\x{20000}-\x{2a6d6}\x{2a700}-\x{2b734}\x{2b740}-\x{2b81d}\x{f900}-\x{fad9}\x{2f800}-\x{2fa1d}]*/u';
        preg_match_all($reg, $str, $ret);
        $ret = ArrayHelper::getValue($ret, 0, []);
        $str = '';
        foreach ($ret as $value) {
            if (empty($value)) {
                continue;
            }
            $str .= $value;
            if (!$truncateRight) {
                break;
            }
        }
        return $str ?: $defaultValue;
    }

    /**
     * 判断词中是否全是英文字母 可以用来排除拼音搜索的特殊情况
     *
     * @param $str
     *
     * @return $str
     */
    public static function isAlpha($str, $useTrim = true)
    {
        $ret = true;
        if ($str && is_string($str)) {
            $useTrim && $str = str_replace(' ', '', $str);
            $ret = ctype_alpha($str);
        }
        return $ret;
    }
}
