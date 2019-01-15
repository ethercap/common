<?php

namespace ethercap\common\consts;

class Currency extends BaseConst
{
    public static $default = [
        null => ['val' => -1, 'desc' => '未知币种'],
    ];

    const CNY = 1;
    const USD = 2;

    public static $currencies = [
        self::CNY => 'CNY',
        self::USD => 'USD',
    ];

    public static $names = [
        self::CNY => '人民币',
        self::USD => '美元',
    ];

    public static $symbols = [
        self::CNY => '¥',
        self::USD => '$',
    ];

    const CNY_PER_USD = 6;
}
