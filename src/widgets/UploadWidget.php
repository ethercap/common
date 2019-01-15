<?php

namespace lspbupt\common\widgets;

use kartik\file\FileInput;
use yii\helpers\Html;
use yii\base\InvalidConfigException;

class UploadWidget extends \yii\bootstrap\Widget
{
    public static function widget($config = [])
    {
        if (!(isset($config['uploadUrl']) && $config['uploadUrl'])) {
            throw new InvalidConfigException('必须有ajax上传的url。');
        }
        $model = $config['model'];
        $field = $config['attribute'];
        !isset($config['maxFileCount']) && $config['maxFileCount'] = 1;
        !isset($config['attachAppend']) && $config['attachAppend'] = true;
        $hint = $config['attachAppend'] ? '本次上传' : '全部';

        !($attachment = json_decode($model->$field)) && $attachment = [];
        $files = json_encode($attachment);
        $initial = $config['attachAppend'] ? $files : '[]';

        return  '<i class="text-danger col-md-12" style="text-align:right;padding-right:0px;">Hint：清空'.$hint.'附件请点它⬇️ </i>'.
                '<input type="hidden" id="'.Html::getInputId($model, $field).'" class="form-control" '.
                'name="'.Html::getInputName($model, $field).'" value=\''.$files.'\' data-initial=\''.$initial.'\'>'.
                self::multiInput(Html::getInputId($model, $field), $config['uploadUrl'], $config['maxFileCount'], $attachment, $config['attachAppend']);
    }

    private static function multiInput($fieldId, $dest, $maxFileCount = 1, $preview = [], $previewAppend = true, $id = 'fileinput')
    {
        $multiple = $maxFileCount > 1;
        $pluginOption = self::getLastPreview($preview, !$previewAppend);
        $pluginOption['uploadUrl'] = $dest;
        $pluginOption['maxFileCount'] = $maxFileCount;
        $pluginOption['showRemove'] = false;
        $pluginOption['showUpload'] = false;
        return FileInput::widget([
            'name' => '',
            'language' => 'zh-cn',
            'pluginOptions' => $pluginOption,
            'options' => [
                'multiple' => $multiple,
                'id' => $id,
            ],
            'pluginEvents' => [
                //在成功上传后将文件的url写入到model字段
                'fileuploaded' => 'function(event, files, extra) {
                    var list = JSON.parse($("#'.$fieldId.'").attr("value"));
                    list.push({name: files.response.data.name, url: files.response.data.url});
                    $("#'.$fieldId.'").attr("value", JSON.stringify(list));
                    //不样前端删除单个图片，因为不知道删除的是哪一个。
                    $(".kv-file-remove").addClass("hidden");
                }',
                //单个删除，不再处理。如果您能懂如何解析key-i，就能处理单个删除。
                //这控件实在是太坑了。。这方法kartik的文档都没有。。
                'fileremoved' => 'function(event, key, i) {
                }',
                //在文件清空时，全部清空。
                'filecleared' => 'function(event, key) {
                    var initial = $("#'.$fieldId.'").data("initial");
                    $("#'.$fieldId.'").val(JSON.stringify(initial));
                }',
                'filebatchselected' => 'function(event, files){
                    $("#'.$id.'").fileinput("upload");
                }',
            ],
        ]);
    }

    private static function getLastPreview($files = [], $overWriteInitial = false)
    {
        if (!$files) {
            return [];
        }
        $option = [
            'initialPreview' => [],
            'initialPreviewAsData' => true,
            'initialPreviewShowDelete' => false,
            'overwriteInitial' => $overWriteInitial,
            'initialCaption' => 'Hint: 上传新的附件，之前的附件也会保留。',
            'initialPreviewConfig' => [],
        ];
        foreach ($files as $file) {
            $option['initialPreview'][] = $file->url;
            $option['initialPreviewConfig'][] = ['caption' => $file->name];
        }
        return $option;
    }
}
