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
namespace Bra\core\field_types;

use Bra\core\field_types\FieldType;
use Bra\core\utils\BraException;
use Bra\core\utils\BraForms;

class editor extends FieldType
{
    public function ueditor()
    {
        global $_W , $_GPC;
        $field = $this->field;
        $content = '';
        if ($field->default_value && is_array($field->default_value)) {
            foreach ($field->default_value['contents'] as $k => $v) {
                $content .= '[page]' . $field->default_value['titles'][$k] . "[/page]";
                $content .= $v;
            }
        } else {
            $content = $field->default_value;
        }
        $field->default_value = $content;
        return  BraForms::bra_editor($field->field_name , $content , $field->form_name , '100%' , '600px' , '' );

    }

    public function process_model_output($input)
    {
        $content = $input;
        $content_bck = html_entity_decode($content);
        $CONTENT_POS = strpos($content_bck, '[page]');
        $CONTENT_POS_END = strpos($content_bck, '[/page]');
        if ($CONTENT_POS !== false && $CONTENT_POS != 0) {
            $before = substr($content_bck, 0, $CONTENT_POS);
            $content_bck = substr($content_bck, $CONTENT_POS);
        }
        if ($CONTENT_POS !== false) { // 开启分页了
            $pattens = <<<TAG
|\[page\](.*)\[/page\]|U
TAG;
            $contents = array_filter(preg_split($pattens, $content_bck));
            $contents[1] = $before . $contents[1];
            $data['contents'] = $contents;
//获取子标题

            if (preg_match_all($pattens, $content_bck, $m, PREG_PATTERN_ORDER)) {
                foreach ($m[1] as $k => $v) {
                    $p = $k + 1;
                    $titles[$p] = strip_tags($v);
                }
            }
            $data['titles'] = $titles;
        } else {
            $data = $input;
        }

        if (defined("BRA_ADMIN")) {
            $data = $input;
        }
        return $data;
    }


    /**
     * @param $input
     * @return mixed
     */
    public function process_output($input)
    {
        $content = $input;
        $content_bck = html_entity_decode($content);
        $CONTENT_POS = strpos($content_bck, '[page]');
        $CONTENT_POS_END = strpos($content_bck, '[/page]');
        if ($CONTENT_POS !== false && $CONTENT_POS != 0) {
            $before = substr($content_bck, 0, $CONTENT_POS);
            $content_bck = substr($content_bck, $CONTENT_POS);


        }
        if ($CONTENT_POS !== false) { // 开启分页了
            $pattens = <<<TAG
|\[page\](.*)\[/page\]|U
TAG;
            $contents = array_filter(preg_split($pattens, $content_bck));
            $contents[1] = $before . $contents[1];
            $data['contents'] = $contents;
//获取子标题

            if (preg_match_all($pattens, $content_bck, $m, PREG_PATTERN_ORDER)) {
                foreach ($m[1] as $k => $v) {
                    $p = $k + 1;
                    $titles[$p] = strip_tags($v);
                }
            }
            $data['titles'] = $titles;
        } else {
            $data['contents'] = [$input];
            $data['titles'] = [""];
        }
        return $data;
    }

    function parseSubject($content)
    {
        $pattern = <<<TAG
|\[subject\](.*)\[/subject\]|U
TAG;
        $replacement = '<div class="subjects" sid="${1}" id="subject_${1}"></div>';
        $content = preg_replace($pattern, $replacement, $content);
        return $content;
    }


}
