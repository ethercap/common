<?php

namespace ethercap\common\helpers;

class MathHelper
{
    public static function percent($value, $max, $precision = 0, $default = null)
    {
        if ($max == 0) {
            if (is_null($default)) {
                throw new \Exception('计算百分数分母不能为零', 1);
            } else {
                return $default;
            }
        }
        return round($value / $max * 100, $precision);
    }
}
