<?php

namespace ethercap\common\controllers;

use yii\helpers\Console;
use yii\helpers\ArrayHelper;
use yii\console\Exception;
use ethercap\common\filters\SynchronizedFilter;
use Yii;

/**
 * 允许你进行队列的操作
 *
 * 查看允许的命令列表
 *     yii query
 * 启动队列
 *     yii query/start
 * 队列状态
 *     yii query/status
 *
 * @author lishipeng <lishipeng@ethercap.com>
 */
class QueryController extends ServiceController
{
    public $query;
    //每次取的数量
    public $limit = 100;

    protected $offset = 0;
    private $data = [];

    // 最大实例数, 如果为零代表不限制实例数
    public $maxInstance = 0;
    // 采取分片的key,建议选择有索引的key
    public $dbKey = 'id';

    public function init()
    {
        if (empty($this->query)) {
            throw new Exception('需要配置查询语句');
        }
        parent::init();
    }

    public function behaviors()
    {
        if ($this->maxInstance == 0) {
            return parent::behaviors();
        }
        $arr = [
            'synchronized' => [
                'class' => SynchronizedFilter::class,
                'actions' => ['start'],
                'maxInstance' => $this->maxInstance,
            ],
        ];
        return ArrayHelper::merge(parent::behaviors(), $arr);
    }

    public function getData()
    {
        if (empty($this->data)) {
            $query = $this->getModQuery();
            $this->data = $query->limit($this->limit)
                ->offset($this->offset)->all();
        }
        return array_shift($this->data);
    }

    public function processData()
    {
    }

    public function actionStatus()
    {
        $this->printPrefix = false;
        $this->printInfo = true;
        $count1 = $this->query->count();

        $this->stdout('待处理：', Console::FG_GREEN);
        $this->stdout($count1."\n", Console::FG_YELLOW);
        $this->stdout('最大进程数：', Console::FG_GREEN);
        $this->stdout($this->maxInstance."\n", Console::FG_YELLOW);

        $this->stdout("\n", Console::FG_YELLOW);
        return parent::actionStatus();
    }

    // 获取当前脚本的index
    public function getCurrentIndex()
    {
        if ($this->maxInstance == 0) {
            return 0;
        }
        return $this->instanceIndex;
    }

    // query取模
    protected function getModQuery()
    {
        if ($this->maxInstance > 1) {
            return $this->query->andWhere($this->dbKey.' mod :max = :index', [
                ':max' => $this->maxInstance,
                ':index' => $this->getCurrentIndex(),
            ]);
        }
        return $this->query;
    }
}
