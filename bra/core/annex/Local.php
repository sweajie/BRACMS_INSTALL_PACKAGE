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
namespace Bra\core\annex;

use Bra\core\objects\BraString;
use Bra\core\utils\BraForms;

class Local extends AnnexEngine {

	public function test () {
		return bra_res(1, 'ok');
	}

	public function form ($field) {

		$device = BraString::is_mobile() ? "mobile" : "desktop";//当前客户端
		$form_action = $device . "_form";

		return $this->$form_action($field);
	}

	public function mobile_form ($field) {

	}

	public function desktop_form ($field) {
		return BraForms::bra_flow_upload($field->field_name ,$field->form_name , $field->default_value ,  $field->max_count, '' , '' , $field->length , $field->keep_clear);
	}

	public function upload ($file, $local_path = "") {
		return bra_res(1, 'Local ok');
	}

	/**
	 * @param $annex array
	 * @return mixed
	 * @throws DbException
	 */
	public function delete ($annex) {
		$count = D('annex_idx')->where(['annex_id' => $annex['id']])->count();
		if ($count <= 1) {
			//delete the data
			D('annex')->where(['id' => $annex['id']])->delete();
			//if file exists
			if (is_file(SYS_PATH . $annex['url'])) {
				@unlink(SYS_PATH . $annex['url']); //delete the file
			}
		}

		return bra_res(1, 'Local ok');
	}

	public function convert_amr ($filePath, $mediaid) {
		$ffmpeg = FFMpeg::create();
		$audio = $ffmpeg->open($filePath);
		$format = new Mp3();
		$format->setAudioChannels(2)->setAudioKiloBitrate(256);
		$converted_path = str_replace(".amr", ".mp3", $filePath);
		$audio->save($format, $converted_path);

		return $this->get_prefix() . str_replace(SYS_PATH . DIRECTORY_SEPARATOR, "", $converted_path);
	}

}
