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
namespace Bra\core\utils;



use Illuminate\Support\Facades\Cookie;

trait BraArea {

	public function change_city ($city_id) {
		global $_W, $_GPC;
		Cookie::set('area_id_' . $_W['site']['id'], (int)$city_id);
		$ret['code'] = 1;

		return $ret;
	}

	public function change_area ($module = '') {
		global $_W, $_GPC;
		$res = self::load_area();
		A('area_info', $res['data']);
		A('c_area', $this->init_area());
		A('module', $module);

		return T();
	}


	/**
	 * 找出所有的地区
	 * @return array
	 */
	public static function load_all_area () {
		global $_W, $_GPC;
		$area_model = D('area');
		$where = [];
		$where['site_id'] = $_W['site']['id'];
		$areas = $area_model->bra_where($where)->order('listorder desc')->select()->toArray();
		$citylists = [];
		$org_data = [];
		foreach ($areas as $val) {
			$a = $val['letter'];
			$citylists[$a][] = $val;
			$org_data[$val['id']] = $val;
		}
		ksort($citylists);

		//$ret['data']['org_areas'] = $org_data;

		return $citylists;
	}
	/**
	 * 找出所有的中间地区 仅支持三级地区
	 * @return array
	 */
	public static function load_mid_area () {
		global $_W, $_GPC;
		$area_model = D('area');
		$where = [];
		$where['site_id'] = $_W['site']['id'];
		$where['has_child'] = 1;
		$where['parent_id'] = ['>' , 0];
		$areas = $area_model->bra_where($where)->order('listorder desc')->select()->toArray();
		$citylists = [];
		$org_data = [];
		foreach ($areas as $val) {
			$a = $val['letter'];
			$citylists[$a][] = $val;
			$org_data[$val['id']] = $val;
		}
		ksort($citylists);

		//$ret['data']['org_areas'] = $org_data;

		return $citylists;
	}

	public static function load_top_area ($donde = [] , $raw_where = []) {
		global $_W, $_GPC;
		$area_model = D('area');
		$where = $donde;

		if(!$raw_where){
			$areas = $area_model->with_site()->bra_where($where)->order('listorder desc')->bra_get();
		}else{
			$areas = $area_model->with_site()->bra_where($where)->whereRaw($raw_where)->order('listorder desc')->bra_get();
		}

		$citylists = [];
		foreach ($areas as $val) {
			$a = $val['letter'];
			$val['children'] = D('area')->bra_where(['parent_id' => $val['id']])->bra_get();
			$citylists[$a][] = $val;
		}
		ksort($citylists);
		return $citylists;
	}

	public static function load_level_2_area ($donde = []) {
		global $_W, $_GPC;
		$area_model = D('area');
		$where = $donde;
		$areas = $area_model->with_site()->bra_where($where)->order('listorder desc')->bra_get();
		$citylists = [];
		$org_data = [];
		foreach ($areas as $val) {
			if ($val['parent_id'] > 0) {
				$a = $val['letter'];
				$citylists[$a][] = $val;
			}
			$org_data[$val['id']] = $val;
		}
		ksort($citylists);

		//$ret['data']['org_areas'] = $org_data;

		return $citylists;
	}

	/*Area Change*/

	public static function load_area () {
		$ret['data']['areas'] = self::load_top_area();
		$ret['code'] = 1;

		return $ret;
	}

	/*detect area*/

	public function init_area () {
		global $_W;

		return D('area')->find((int)Cookie::get('area_id_' . $_W['site']['id']));
	}

	/* GET Current Area Data*/

	public function detect_area ($site_id) {
		global $_W, $_GPC;
		$query = $_GPC['query'];
		$area_model = D('area');

		//detect district
		$where['site_id'] = $site_id;
		$where['parent_id'] = 0;
		//$where['module'] = ['IN', ['', '"core"']];

		$district = $query['district'];
		$where['title'] = $district;

		$target_area = $area_model->find($where);
		if ($target_area) {
			$ret['code'] = 1;
			$ret['data'] = $target_area;
			Cookie::set('area_id_' . $site_id, $target_area['id']);

			return $ret;
		}
		//detect city
		$city = $query['city'];
		$where['title'] = $city;

		$target_area = $area_model->find($where);
		if ($target_area) {
			$ret['code'] = 1;
			$ret['data'] = $target_area;
			Cookie::set('area_id_' . $site_id, $target_area['id']);

			return $ret;
		}

		$province = $query['province'];
		$where['title'] = $province;
		$target_area = $area_model->find($where);
		if ($target_area) {
			$ret['code'] = 1;
			$ret['data'] = $target_area;
			Cookie::set('area_id_' . $site_id, $target_area['id']);

			return $ret;
		}


		if (!$target_area) {
			$where = [];
			$where['site_id'] = $site_id;
			$where['parent_id'] = 0;
			$target_area = $area_model->bra_where($where)->order('listorder desc')->find();
		}

		if ($target_area) {
			Cookie::set('area_id_' . $site_id, $target_area['id']);
			$ret['code'] = 1;
			$ret['data'] = $target_area;

			return $ret;
		} else {
			$ret['code'] = 404;
			$ret['data'] = $target_area;

			return $ret;
		}
	}

	public function range ($u_lat, $u_lon, $list) {
		/*
		*u_lat 用户纬度
		*u_lon 用户经度
		*list sql语句
		*/
		if (!empty($u_lat) && !empty($u_lon)) {
			foreach ($list as $row) {
				$row['km'] = $this->nearby_distance($u_lat, $u_lon, $row['lat'], $row['lon']);
				$row['km'] = round($row['km'], 1);
				$res[] = $row;
			}
			if (!empty($res)) {
				foreach ($res as $user) {
					$ages[] = $user['km'];
				}
				array_multisort($ages, SORT_ASC, $res);

				return $res;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	//计算经纬度两点之间的距离
	public function nearby_distance ($lat1, $lon1, $lat2, $lon2) {
		$EARTH_RADIUS = 6378.137;
		$radLat1 = $this->rad($lat1);
		$radLat2 = $this->rad($lat2);
		$a = $radLat1 - $radLat2;
		$b = $this->rad($lon1) - $this->rad($lon2);
		$s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
		$s1 = $s * $EARTH_RADIUS;
		$s2 = round($s1 * 10000) / 10000;

		return $s2;
		//print_r($s2);
	}

	private function rad ($d) {
		return $d * 3.1415926535898 / 180.0;
	}


	public static function st__detect_area ($query , $get_default = true , $is_top = true) {
		global $_W, $_GPC;
		$area_model = D('area');
		//detect district
		$where['site_id'] = $_W['site']['id'];
		if($is_top){
			$where['parent_id'] = 0;
		}

		$where['area_name'] = $query['district'];
		$target_area = $area_model->find($where);
		if ($target_area) {
			return bra_res(1 , $query , '' , $target_area);
		}
		//detect city
		$where['area_name'] = $query['city'];
		$target_area = $area_model->find($where);
		if ($target_area) {
			return bra_res(1 , '!' , '' , $target_area);
		}
		$where['area_name'] = $query['province'];
		$target_area = $area_model->find($where);
		if ($target_area) {
			return bra_res(1 , '!' , '' , $target_area);
		}
		if (!$target_area && $get_default) {
			$where = [];
			$where['parent_id'] = 0;
			$target_area = $area_model->with_site()->bra_where($where)->order('listorder desc')->bra_one();
			return bra_res(1 , '地区未找到!' , '' , $target_area);
		}
		return bra_res(404 , '地区未找到!');
	}

}
