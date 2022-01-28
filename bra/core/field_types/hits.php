<?php
// +----------------------------------------------------------------------
// | 鸣鹤CMS [ New Better  ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2017 http://www.bracms.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( 您必须获取授权才能进行使用 )
// +----------------------------------------------------------------------
// | Author: new better <1620298436@qq.com>
// +----------------------------------------------------------------------
namespace app\bra\forms;

use app\common\model\NodeHits;
use think\Cache;

class hits extends AbsFormTag
{

    public function process_input($input)
    {
        return $input;
    }

    public function process_output($input)
    {
        if($this->node_id){
            $hit = NodeHits::get(['node_id'=>$this->node_id]);
            if (!$hit) {
                $hit = new NodeHits();
                $hit->node_id = $this->node_id;
                $hit->views = 1;
                $hit->dayviews = 1;
                $hit->weekviews = 1;
                $hit->monthviews = 1;
                $hit->updatetime = SYS_TIME;
                $hit->views = 1;
                $hit->save();
            }else {
                $hit->views++;
                $hit->yesterdayviews = (date('Ymd', $hit['updatetime']) == date('Ymd', strtotime('-1 day'))) ? $hit['dayviews'] : $hit['yesterdayviews'];
                $hit->dayviews = (date('Ymd', $hit['updatetime']) == date('Ymd', SYS_TIME)) ? ($hit['dayviews'] + 1) : 1;
                $hit->weekviews = (date('YW', $hit['updatetime']) == date('YW', SYS_TIME)) ? ($hit['weekviews'] + 1) : 1;
                $hit->monthviews = (date('Ym', $hit['updatetime']) == date('Ym', SYS_TIME)) ? ($hit['monthviews'] + 1) : 1;
                $hit->updatetime = SYS_TIME;
                $hit->save();
            }
            return $hit;
        }

    }
}
