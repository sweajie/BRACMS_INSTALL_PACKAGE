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
namespace Bra\core\utils\map;


class AMap
{

	/**
	 * @param $form_name
	 * @param $field_name
	 * @param string $default  lng,lat
	 * @return string
	 */
	public static function render_form ($form_name , $field_name , $default = '') {
		global $_W;
		if(empty($default)){
			if(isset($_W['site']['config']['map']) && isset($_W['site']['config']['map']['site_coordinate'])){
				$default = $_W['site']['config']['map']['site_coordinate'];
			}else{
				$default = "116.404, 39.915";
			}
		}

		$point = explode(',' ,$default);
		$lat = $point[1];
		$lng = $point[0];
		$ret_str = <<<EOF

<div id="bra-html-el_$field_name" style="display:none;height:90%">
<div class="bra-body columns is-mobile centered is-multiline" style="position:relative ;height:100%">
<div class="column is-12 ">
 <div class="field has-addons is-margin m-l m-r m-t">
                            <div class="control"><a class="bra-btn is-info  is-static">标注地图</a></div>
                            <div class="control">
                                <input class="bra-input bra-search-input" style="width: 190px;" placeholder="输入关键字搜索" autocomplete="off" />
                            </div>
                            <div class="control">
                            	<div class="bra-btns">
                            	<div class="bra-btn is-info do-map-search" type="button">搜索</div>

                            	<div class="bra-btn is-danger bra-confirm-select"><i class="layui-icon">&#xe605;</i> 确定</div>
                            	</div>
                            </div>
                        </div>
		</div>
		<div class="column is-12 bra-select-map-group  " style="position: relative;height:100%;">
		<div class="is-margin m-l m-r " style="position: relative;height:100%;">
			 <div id="bra-map-el_$field_name" style="min-height:350px;min-width:300px;height:100%"></div>

</div>
		</div>
   </div>

</div>


<input type="text" readonly name="$form_name" value="$default" id='$field_name'  class="bra-btn is-primary">
<a class="bra-btn icon-btn" id="bra-btn-el_$field_name">
<i class="layui-icon">&#xe715;</i>地图选择位置
</a>
EOF;
		$_W->bra_scripts[] = <<<EOF

<script type="text/javascript">
require(['bra_map'] , function(bra_map) {
  bra_map.init_choose({
  	html_el : '#bra-html-el_$field_name' ,
  	map_el : 'bra-map-el_$field_name' ,
  	btn_el : '#bra-btn-el_$field_name' ,
  	point : [ "$lng" , "$lat" ] ,
  	title : '地图选择' ,
  	input_el : '.do-map-search' ,
  	success : function(data) {
  	  $("#$field_name").val(data.lng + ',' + data.lat);
  	}
  })
});
</script>
EOF;
		return $ret_str;
	}


	/**
	 * @param $form_name
	 * @param $field_name
	 * @param string $default  lng,lat
	 * @return string
	 */
	public static function render_input_form ($form_name , $field_name , $default  , $lng_lat = [] , $lng_key = 'lng' , $lat_key = 'lat') {
		global $_W;

		$lat = $lng_lat['lat'] ? $lng_lat['lat']  : '116.404';
		$lng = $lng_lat['lng'] ? $lng_lat['lng'] :  '39.915';
		$ret_str = <<<EOF

<div id="bra-html-el_$field_name" style="display:none;height:90%">
<div class="bra-body columns is-mobile margined centered is-multiline" style="position:relative ;height:100%">
<div class="column is-12 ">
 <div class="field has-addons is-margin m-l m-r m-t">
                            <div class="control"><a class="bra-btn is-info  is-static">标注地图</a></div>
                            <div class="control">
                                <input class="bra-input bra-search-input" style="width: 190px;" placeholder="输入关键字搜索" autocomplete="off" />
                            </div>
                            <div class="control">
                            	<div class="bra-btns">
                            	<div class="bra-btn is-info do-map-search" type="button">搜索</div>

                            	<div class="bra-btn is-danger bra-confirm-select"><i class="layui-icon">&#xe605;</i> 确定</div>
                            	</div>
                            </div>
                        </div>
		</div>

		<div class="column is-12 bra-select-map-group" style="position: relative;height:100%">
			 <div id="bra-map-el_$field_name" style="min-height:350px;min-width:300px;height:100%"></div>
		</div>
   </div>

</div>


<input type="text"  name="$form_name" value="$default" id='$field_name'  class="layui-btn layui-btn-primary">
<input type="text" readonly name="$lng_key" value="$lng" id='{$field_name}_lng'  class="layui-btn layui-btn-primary">
<input type="text" readonly name="$lat_key" value="$lat" id='{$field_name}_lat'  class="layui-btn layui-btn-primary">
<a class="layui-btn layui-btn-normal icon-btn" id="bra-btn-el_$field_name"><i class="layui-icon">&#xe715;</i>地图选择位置</a>
EOF;

		$_W->bra_scripts[] = <<<EOF

<script type="text/javascript">
require(['bra_map'] , function(bra_map) {
  bra_map.init_choose({
  	html_el : '#bra-html-el_$field_name' ,
  	map_el : 'bra-map-el_$field_name' ,
  	btn_el : '#bra-btn-el_$field_name' ,
  	point : [ "$lng" , "$lat" ] ,
  	title : '地图选择' ,
  	input_el : '.do-map-search' ,
  	success : function(data) {
  	  $("#{$field_name}_lat").val(data.lat);
  	  $("#{$field_name}_lng").val(data.lng);
  	}
  })
});
</script>
EOF;

		return $ret_str;
	}
}
