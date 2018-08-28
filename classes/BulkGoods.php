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
	 * 取得保质期单位列表
	 *
	 * @return array 保质期单位列表
	 */
	public static function limit_days_unit_list() {
		$arr = array(
				'1' =>	'天',
				'2' =>	'月',
				'3'	=>  '年'
		);
	
		return $arr;
	}
	
	/**
	 * 获取用户等级列表数组
	 */
	public static function get_rank_list() {
	
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
	public static function get_volume_price_list($goods_id, $price_type = '1') {
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
	
	
	/**
	 * 获取用户等级列表数组
	 */
	public static function get_scales_sn_arr($store_id=0) {
		$arr = [];
		if ($store_id) {
			$arr = RC_DB::table('cashdesk_scales')->where('store_id', $store_id)->lists('scale_sn');
		}
		return $arr;
	}
	
	/**
	 * 保存某商品的会员价格
	 *
	 * @param int $goods_id
	 *            商品编号
	 * @param array $rank_list
	 *            等级列表
	 * @param array $price_list
	 *            价格列表
	 * @return void
	 */
	public static function handle_member_price($goods_id, $rank_list, $price_list) {
		/* 循环处理每个会员等级 */
		if (!empty($rank_list)) {
			foreach ($rank_list as $key => $rank) {
				/* 会员等级对应的价格 */
				$price = $price_list [$key];
				// 插入或更新记录
				$count = RC_DB::table('member_price')->where('goods_id', $goods_id)->where('user_rank', $rank)->count();
	
				if ($count) {
					/* 如果会员价格是小于0则删除原来价格，不是则更新为新的价格 */
					if ($price < 0) {
						RC_DB::table('member_price')->where('goods_id', $goods_id)->where('user_rank', $rank)->delete();
					} else {
						$data = array(
								'user_price' => $price
						);
						RC_DB::table('member_price')->where('goods_id', $goods_id)->where('user_rank', $rank)->update($data);
					}
				} else {
					if ($price == -1) {
						$sql = '';
					} else {
						$data = array(
								'goods_id' 		=> $goods_id,
								'user_rank' 	=> $rank,
								'user_price' 	=> $price
						);
						RC_DB::table('member_price')->insert($data);
					}
				}
			}
		}
	}
	
	/**
	 * 保存某商品的优惠价格
	 * @param   int $goods_id 商品编号
	 * @param   array $number_list 优惠数量列表
	 * @param   array $price_list 价格列表
	 * @return  void
	 */
	public static function handle_volume_price($goods_id, $number_list, $price_list) {
		RC_DB::table('volume_price')->where('price_type', 1)->where('goods_id', $goods_id)->delete();
		/* 循环处理每个优惠价格 */
		foreach ($price_list AS $key => $price) {
			/* 价格对应的数量上下限 */
			$volume_number = $number_list[$key];
			if (!empty($price)) {
				$data = array(
						'price_type'	=> 1,
						'goods_id'		=> $goods_id,
						'volume_number' => $volume_number,
						'volume_price'  => $price,
				);
				RC_DB::table('volume_price')->insert($data);
			}
		}
	}
}