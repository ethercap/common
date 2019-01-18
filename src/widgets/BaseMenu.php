<?php

namespace ethercap\common\widgets;

use dmstr\widgets\Menu;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\helpers\Html;

class BaseMenu extends Menu
{
    public $options = ['class' => 'sidebar-menu', 'id' => 'sidebar-menu'];

    public $linkTemplate = '<a href="{url}">{icon} {label} {noticeLabel}</a>';
    public $submenuTemplate = "\n<ul class='treeview-menu' {show}>\n{items}\n</ul>\n";
    public $activateParents = true;
    public $defaultIconHtml = '';

    public $tplMap = [];

    /**
     * @var string
     */
    public static $iconClassPrefix = 'fa fa-';

    private $noDefaultAction;
    private $noDefaultRoute;

    /**
     * @inheritdoc
     */
    protected function renderItem($item, $root = true)
    {
        $nested = false;
        if (isset($item['items'])) {
            $nested = true;
            $labelTemplate = '<a href="{url}">{icon} {label} <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>{noticeLabel}</a>';
            $linkTemplate = '<a href="{url}">{icon} {label} <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>{noticeLabel}</a>';
        } else {
            $labelTemplate = $this->labelTemplate;
            $linkTemplate = $this->linkTemplate;
        }

        if (isset($item['url'])) {
            $template = ArrayHelper::getValue($item, 'template', $linkTemplate);
            $replace = !empty($item['icon']) ?
                ['{url}' => Url::to($item['url']), '{icon}' => '<i class="' . self::$iconClassPrefix . $item['icon'] . '"></i> '] :
                ['{url}' => Url::to($item['url']), '{icon}' => $this->defaultIconHtml, ];
        } else {
            $template = ArrayHelper::getValue($item, 'template', $labelTemplate);
            $replace = !empty($item['icon']) ?
                ['{icon}' => '<i class="' . self::$iconClassPrefix . $item['icon'] . '"></i> '] :
                ['{icon}' => $this->defaultIconHtml];
        }

        $label = ['{label}' => $root ? Html::tag('span', $item['label']) : $item['label']];
        $noticeReplace = ['{noticeLabel}' => ''];
        if (isset($item['noticeLabel']) && $noticeLabel = trim($item['noticeLabel'])) {
            $pullRight = $nested ? '' : ' pull-right';
            $type = \yii\helpers\ArrayHelper::getValue($item, 'noticeType', 'warning');
            $noticeReplace = ['{noticeLabel}' => Html::tag('span', $noticeLabel, ['class' => 'label label-' . $type . $pullRight])];
        }

        return strtr($template, array_merge($replace, $noticeReplace, $label));
    }

    protected function renderItems($items, $root = true, $level = 0)
    {
        $n = count($items);
        $lines = [];
        $template = ArrayHelper::getValue($this->tplMap, $level, $this->submenuTemplate);
        foreach ($items as $i => $item) {
            $options = array_merge($this->itemOptions, ArrayHelper::getValue($item, 'options', []));
            $tag = ArrayHelper::remove($options, 'tag', 'li');
            $class = [];
            if ($item['active']) {
                $class[] = $this->activeCssClass;
            }

            if ($i === 0 && $this->firstItemCssClass !== null) {
                $class[] = $this->firstItemCssClass;
            }

            if ($i === $n - 1 && $this->lastItemCssClass !== null) {
                $class[] = $this->lastItemCssClass;
            }

            if (!empty($class)) {
                if (empty($options['class'])) {
                    $options['class'] = implode(' ', $class);
                } else {
                    $options['class'] .= ' ' . implode(' ', $class);
                }
            }
            (isset($item['node-key']) && !empty($item['node-key'])) && $options['id'] = $item['node-key'];
            $menu = $this->renderItem($item, $root);
            if (!empty($item['items'])) {
                $menu .= strtr($template, [
                    '{show}' => $item['active'] ? "style='display: block'" : '',
                    '{items}' => $this->renderItems($item['items'], false, $level + 1),
                ]);
            }

            $lines[] = Html::tag($tag, $menu, $options);
        }

        return implode("\n", $lines);
    }
}
