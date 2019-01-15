<?php

namespace ethercap\common\helpers;

use yii;

/**
 * Location的解析 和 转换
 * 可以将网上大部分的地址信息 解析成 省-市-区 这样的格式
 * usage:
 *  $addressHelper = new AddressHelper(['address' => '北京市海淀区清河中街68号']);
 *  $addressHelper->validate();
 *  var_dump($addressHelper->province . $addressHelper->city . $addressHelper->area);
 */
class AddressHelper extends yii\base\Model
{
    public $address = '';
    public $province = '';
    public $city = '';
    public $area = '';

    public function init()
    {
        parent::init();
    }

    public function rules()
    {
        return [
            ['address', 'filter', 'filter' => [$this, 'parse']],
        ];
    }

    public function parse()
    {
        $matches = [];
        $address = $this->address;
        preg_match('/(.*?(省|自治区|北京市|天津市|上海市|重庆市))/', $address, $matches);
        if (count($matches) > 1) {
            $this->province = $matches[count($matches) - 2];
            $address = str_replace($this->province, '', $address);
        }
        preg_match('/(.*?(市|自治州|地区|区划|县))/', $address, $matches);
        if (count($matches) > 1) {
            $this->city = $matches[count($matches) - 2];
            $address = str_replace($this->city, '', $address);
        }
        preg_match('/(.*?(区|县|镇|乡|街道))/', $address, $matches);
        if (count($matches) > 1) {
            $this->area = $matches[count($matches) - 2];
            $address = str_replace($this->area, '', $address);
        }
        return $this->address;
    }
}
