<?php

namespace Ecjia\App\Cashier;

use RC_DB;
use RC_Time;
use ecjia;
use RC_Lang;


class BulkGoods
{


	/**
	 * 修改商品某字段值
	 *
	 * @param string $goods_id
	 *            商品编号，可以为多个，用 ',' 隔开
	 * @param string $field
	 *            字段名
	 * @param string $value
	 *            字段值
	 * @return bool
	 */
	public static function update_goods($goods_id, $field, $value) {
		if ($goods_id) {
			$data = array(
					$field 			=> $value,
					'last_update' 	=> RC_Time::gmtime()
			);
			$db_goods = RC_DB::table('goods')->whereIn('goods_id', $goods_id);
			if (!empty($_SESSION['store_id'])) {
				$db_goods->where('store_id', $_SESSION['store_id']);
			}
			$db_goods->update($data);
		} else {
			return false;
		}
	}
    
	/**
	 * 取得重量单位列表
	 *
	 * @return array 重量单位列表
	 */
	public static function unit_list() {
		$arr = array(
				'1' =>	'克',
				'2' =>	'千克'
		);
	
		return $arr;
	}
	
	/**
	 * 获取用户等级列表数组
	 */
	function get_rank_list() {
	
		return RC_DB::table('user_rank')->orderBy('min_points', 'asc')->get();
	}
	
	
	/**
	 * 取得商品优惠价格列表
	 *
	 * @param string $goods_id
	 *        	商品编号
	 * @param string $price_type
	 *        	价格类别(0为全店优惠比率，1为商品优惠价格，2为分类优惠比率)
	 *
	 * @return 优惠价格列表
	 */
	function get_volume_price_list($goods_id, $price_type = '1') {
		$res = RC_DB::table('volume_price')
		->select('volume_number', 'volume_price')
		->where('goods_id', $goods_id)
		->where('price_type', $price_type)
		->orderBy('volume_number', 'asc')
		->get();
	
		$volume_price = array();
		$temp_index = '0';
		if (!empty($res)) {
			foreach ($res as $k => $v) {
				$volume_price[$temp_index] 					= array();
				$volume_price[$temp_index]['number'] 		= $v['volume_number'];
				$volume_price[$temp_index]['price'] 		= $v['volume_price'];
				$volume_price[$temp_index]['format_price'] 	= price_format($v['volume_price']);
				$temp_index ++;
			}
		}
		return $volume_price;
	}
}