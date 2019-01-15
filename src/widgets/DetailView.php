<?php

namespace lspbupt\common\widgets;

use lspbupt\common\helpers\ArrayHelper;
use yii\helpers\Html;

/** 改默认的DetailView为一行多列的模式
 *  默认一行为12个格子，每个td占两个坚格，一个横格, 大致的写法如下：
 *  echo DetailView::widget([
 *      'model' => $model,
 *      'attributesList' => [
 *          //每个array为一行，array中的写法与yii默认的detailView中的attributes写法完全一致
 *          [
 *              'id',
 *              'name,
 *              //不要label的一列，横向占2个格子，纵向占4个格式
 *              DetailView::noCaption([
 *                 'attribute' => 'avatar',
 *                 'contentOptions' => [
 *                      'rowspan' => 2,
 *                      'colspan' => 4,
 *                  ],
 *                  'value' => function($model){
 *                      $url = ArrayHelper::getValue($model->user, "avatar");
 *                      return Html::img($url);
 *                  },
 *                  'format' => 'raw',
 *              ]),
 *          ],
 *          [
 *              [
 *                  'attribute' => 'updateTime',
 *                  'label' => '更新时间',
 *                  'contentOptions' => [
 *                      'rowspan' => 1,
 *                      'colspan' => 2,
 *                  ],
 *                  'captionOptions' => [
 *                      'rowspan' => 1,
 *                      'colspan' => 2,
 *                  ],
 *              ],
 *              'creationTime',
 *          ],
 *      ],
 *  ]);
 *
 **/
class DetailView extends \yii\widgets\DetailView
{
    // 将每一列平均划分
    public $colAvg = true;

    // 默认有12列
    public $defaultColomns = 12;
    public $defaultColspan = 2;
    public $defaultRowspan = 1;

    public $template = '<th{captionOptions}>{label}</th><td{contentOptions}>{value}</td>';
    public $attributesList = [];

    //运行
    public function run()
    {
        $rows = [];
        if ($this->colAvg) {
            $width = $this->formatter->asPercent(1.0 / $this->defaultColomns, 2);
            $str = '';
            for ($i = 0; $i < $this->defaultColomns; $i++) {
                $str .= '<th width="'.$width.'"></th>';
            }
            $rows[] = '<tr style="visibility:hidden;">'.$str.'</tr>';
        }

        $i = 1;
        foreach ($this->attributesList as $attributes) {
            $str = '';
            foreach ($attributes as $attribute) {
                $str .= $this->renderAttribute($attribute, $i++);
            }
            $rows[] = '<tr>'.$str.'</tr>';
        }

        $options = $this->options;
        $tag = ArrayHelper::remove($options, 'tag', 'table');
        echo Html::tag($tag, implode("\n", $rows), $options);
    }

    protected function renderAttribute($attribute, $index)
    {
        if (is_string($this->template)) {
            $defaultConfig = [
                'colspan' => $this->defaultColspan,
                'rowspan' => $this->defaultRowspan,
                'data-index' => $index,
            ];
            $captionOptions = ArrayHelper::getValue($attribute, 'captionOptions', []);
            $captionOptions = Html::renderTagAttributes(ArrayHelper::merge($defaultConfig, $captionOptions));
            $contentOptions = ArrayHelper::getValue($attribute, 'contentOptions', []);
            $contentOptions = Html::renderTagAttributes(ArrayHelper::merge($defaultConfig, $contentOptions));
            return strtr($this->template, [
                '{label}' => $attribute['label'],
                '{value}' => $this->formatter->format($attribute['value'], $attribute['format']),
                '{captionOptions}' => $captionOptions,
                '{contentOptions}' => $contentOptions,
            ]);
        }
        return call_user_func($this->template, $attribute, $index, $this);
    }

    /**
     * Normalizes the attribute specifications.
     * @throws InvalidConfigException
     */
    protected function normalizeAttributes()
    {
        if (empty($this->attributesList)) {
            $this->attributesList = [null];
        }
        $arr = [];
        foreach ($this->attributesList as  $attributes) {
            $this->attributes = $attributes;
            parent::normalizeAttributes();
            $arr[] = $this->attributes;
        }
        $this->attributesList = $arr;
    }

    //不要caption的列
    public static function noCaption($arr)
    {
        $config = [
            'captionOptions' => [
                'style' => 'display:none;',
            ],
        ];
        return ArrayHelper::merge($config, $arr);
    }
}
