<?php

namespace Ecjia\App\Cashier;

use RC_DB;
use RC_Time;
use ecjia;
use RC_Lang;
use RC_Api;
use RC_Loader;
use RC_Logger;
use OrderStatusLog;
use order_ship;

/**
 * 获取不同类型订单的打印数据
 */
class GetDiffOrderPrintData
{    
	
	/**
	 * 取得不同类型订单打印数据
	 * @param object $record_model 支付交易记录信息
	 * @param array  $orderinfo    订单信息
	 * @param array  $notify_data  通知数据
	 * @return array 
	 */
	public static function Get_printData($record_model, $order_info = array(), $notify_data = array()) 
	{
		$printdata = [];
		if (!empty($order_info)) {
			if ($record_model->trade_type == 'buy' ) {
    			$printdata = self::getBuyPrintdata($record_model, $order_info, $notify_data);
    		} elseif ($record_model->trade_type == 'quickpay') {
    			$printdata = self::getQuickpayPrintData($record_model, $order_info, $notify_data);
    		} elseif ($record_model->trade_type == 'surplus') {
    			$printdata = self::getSurplusPrintData($record_model, $order_info, $notify_data);
    		}
		}
		
		return $printdata;
	}
	
	/**
	 *  获取消费订单打印数据
	 */
	public static function getBuyPrintData($record_model, $order_info = array(), $notify_data = array())
	{

		$buy_print_data = array();
		if (!empty($order_info)) {
			$order_goods 			= self::getOrderGoods($order_info['order_id']);
			$total_discount 		= $order_info['discount'] + $order_info['integral_money'] + $order_info['bonus'];
			$money_paid 			= $order_info['money_paid'] + $order_info['surplus'];
		
			//下单收银员
			$cashier_name = RC_DB::table('cashier_record as cr')
			->leftJoin('staff_user as su', RC_DB::raw('cr.staff_id'), '=', RC_DB::raw('su.user_id'))
			->where(RC_DB::raw('cr.order_id'), $order_info['order_id'])
			->whereIn('action', array('check_order', 'billing'))
			->pluck('name');
		
			$user_info = [];
			//有没用户
			if ($order_info['user_id'] > 0) {
				$userinfo = self::getUserInfo($order_info['user_id']);
				if (!empty($userinfo)) {
					$user_info = array(
							'user_name' 			=> empty($userinfo['user_name']) ? '' : trim($userinfo['user_name']),
							'mobile'				=> empty($userinfo['mobile_phone']) ? '' : trim($userinfo['mobile_phone']),
							'user_points'			=> $userinfo['pay_points'],
							'user_money'			=> $userinfo['user_money'],
							'formatted_user_money'	=> $userinfo['user_money'] > 0 ? price_format($userinfo['user_money'], false) : '',
					);
				}
			}
			
			if (!empty($notify_data['detailList']['batAccount'])) {
				$payment_account = $notify_data['detailList']['batAccount'];
			} elseif (!empty($notify_data['detailList']['cardNo'])) {
				$payment_account = $notify_data['detailList']['cardNo'];
			} else {
				$payment_account = '';
			}
			
			$buy_print_data = array(
					'order_sn' 						=> $order_info['order_sn'],
					'trade_no'						=> $record_model->trade_no ? $record_model->trade_no : '',
					'order_trade_no'				=> $record_model->order_trade_no ? $record_model->order_trade_no : '',
					'trade_type'					=> 'buy',
					'pay_time'						=> empty($order_info['pay_time']) ? '' : RC_Time::local_date(ecjia::config('time_format'), $order_info['pay_time']),
					'goods_list'					=> $order_goods['list'],
					'total_goods_number' 			=> $order_goods['total_goods_number'],
					'total_goods_amount'			=> $order_goods['taotal_goods_amount'],
					'formatted_total_goods_amount'	=> price_format($order_goods['taotal_goods_amount'], false),
					'total_discount'				=> $total_discount,
					'formatted_total_discount'		=> price_format($total_discount, false),
					'money_paid'					=> $money_paid,
					'formatted_money_paid'			=> price_format($money_paid, false),
					'integral'						=> intval($order_info['integral']),
					'integral_money'				=> $order_info['integral_money'],
					'formatted_integral_money'		=> price_format($order_info['integral_money'], false),
					'pay_code'						=> $record_model->pay_code ? $record_model->pay_code : '',
					'pay_name'						=> $record_model->pay_name ? $record_model->pay_name : '',
					'payment_account'				=> $payment_account,
					'user_info'						=> $user_info,
					'refund_sn'						=> '',
					'refund_total_amount'			=> 0,
					'formatted_refund_total_amount' => '',
					'cashier_name'					=> empty($cashier_name) ? '' : $cashier_name
			);
		}
		 
		return $buy_print_data;
		
	}
	
	/**
	 *  获取收款（快捷买单）订单打印数据
	 */
	public static function getQuickpayPrintData($record_model, $order_info = array(), $notify_data = array())
	{

		$quickpay_print_data = [];
		if ($order_info) {
			$total_discount 		= $order_info['discount'] + $order_info['integral_money'] + $order_info['bonus'];
			$money_paid 			= $order_info['order_amount'] + $order_info['surplus'];
		
			//下单收银员
			$cashier_name = RC_DB::table('cashier_record as cr')
			->leftJoin('staff_user as su', RC_DB::raw('cr.staff_id'), '=', RC_DB::raw('su.user_id'))
			->where(RC_DB::raw('cr.order_id'), $order_info['order_id'])
			->where('action', 'receipt')
			->pluck('name');
		
			$user_info = [];
			//有没用户
			if ($order_info['user_id'] > 0) {
				$userinfo = self::getUserInfo($order_info['user_id']);
				if (!empty($userinfo)) {
					$user_info = array(
							'user_name' 			=> empty($userinfo['user_name']) ? '' : trim($userinfo['user_name']),
							'mobile'				=> empty($userinfo['mobile_phone']) ? '' : trim($userinfo['mobile_phone']),
							'user_points'			=> $userinfo['pay_points'],
							'user_money'			=> $userinfo['user_money'],
							'formatted_user_money'	=> price_format($userinfo['user_money'], false),
					);
				}
			}
		
			if (!empty($notify_data['detailList']['batAccount'])) {
				$payment_account = $notify_data['detailList']['batAccount'];
			} elseif (!empty($notify_data['detailList']['cardNo'])) {
				$payment_account = $notify_data['detailList']['cardNo'];
			} else {
				$payment_account = '';
			}
			
			$quickpay_print_data = array(
					'order_sn' 						=> $order_info['order_sn'],
					'trade_no'						=> $record_model->trade_no ? $record_model->trade_no : '',
					'order_trade_no'				=> $record_model->order_trade_no ? $record_model->order_trade_no : '',
					'trade_type'					=> 'quickpay',
					'pay_time'						=> empty($order_info['pay_time']) ? '' : RC_Time::local_date(ecjia::config('time_format'), $order_info['pay_time']),
					'goods_list'					=> [],
					'total_goods_number' 			=> 0,
					'total_goods_amount'			=> $order_info['goods_amount'],
					'formatted_total_goods_amount'	=> price_format($order_info['goods_amount'], false),
					'total_discount'				=> $total_discount,
					'formatted_total_discount'		=> price_format($total_discount, false),
					'money_paid'					=> $money_paid,
					'formatted_money_paid'			=> price_format($money_paid, false),
					'integral'						=> intval($order_info['integral']),
					'integral_money'				=> $order_info['integral_money'],
					'formatted_integral_money'		=> price_format($order_info['integral_money'], false),
					'pay_code'						=> !empty($record_model->pay_code) ? $record_model->pay_code : '',
					'pay_name'						=> !empty($record_model->pay_name) ? $record_model->pay_name : '',
					'payment_account'				=> $payment_account,
					'user_info'						=> $user_info,
					'refund_sn'						=> '',
					'refund_total_amount'			=> 0,
					'formatted_refund_total_amount' => '',
					'cashier_name'					=> empty($cashier_name) ? '' : $cashier_name
			);
		}
		 
		return $quickpay_print_data;
	}
	
	/**
	 *  获取充值订单打印数据
	 */
	public static function getSurplusPrintData($record_model, $order_info = array(), $notify_data = array())
	{

		$surplus_print_data = [];
		if (!empty($order_info)) {
			$user_info = [];
			//有没用户
			if ($order_info['user_id'] > 0) {
				$userinfo = $this->get_user_info($order_info['user_id']);
				if (!empty($userinfo)) {
					$user_info = array(
							'user_name' 			=> empty($userinfo['user_name']) ? '' : trim($userinfo['user_name']),
							'mobile'				=> empty($userinfo['mobile_phone']) ? '' : trim($userinfo['mobile_phone']),
							'user_points'			=> $userinfo['pay_points'],
							'user_money'			=> $userinfo['user_money'],
							'formatted_user_money'	=> price_format($userinfo['user_money'], false),
					);
				}
			}
		
			//充值操作收银员
			$cashier_name = empty($order_info['admin_user']) ? '' : $order_info['admin_user'];
		
			if (!empty($notify_data['detailList']['batAccount'])) {
				$payment_account = $notify_data['detailList']['batAccount'];
			} elseif (!empty($notify_data['detailList']['cardNo'])) {
				$payment_account = $notify_data['detailList']['cardNo'];
			} else {
				$payment_account = '';
			}
			
			$surplus_print_data = array(
					'order_sn' 						=> trim($order_info['order_sn']),
					'trade_no'						=> $record_model->trade_no ? $record_model->trade_no : '',
					'order_trade_no'				=> $record_model->order_trade_no ? $record_model->order_trade_no : '',
					'trade_type'					=> 'surplus',
					'pay_time'						=> empty($order_info['paid_time']) ? '' : RC_Time::local_date(ecjia::config('time_format'), $order_info['paid_time']),
					'goods_list'					=> [],
					'total_goods_number' 			=> 0,
					'total_goods_amount'			=> $order_info['amount'],
					'formatted_total_goods_amount'	=> price_format($order_info['amount'], false),
					'total_discount'				=> 0,
					'formatted_total_discount'		=> '',
					'money_paid'					=> $order_info['amount'],
					'formatted_money_paid'			=> price_format($order_info['amount'], false),
					'integral'						=> 0,
					'integral_money'				=> '',
					'formatted_integral_money'		=> '',
					'pay_code'						=> $record_model->pay_code ? $record_model->pay_code : '',
					'pay_name'						=> $record_model->pay_name ? $record_model->pay_name : '',
					'payment_account'				=> $payment_account,
					'user_info'						=> $user_info,
					'refund_sn'						=> '',
					'refund_total_amount'			=> 0,
					'formatted_refund_total_amount' => '',
					'cashier_name'					=> $cashier_name
			);
		}
		 
		return $surplus_print_data;
	}
	
	
	/**
	 * 订单商品
	 */
	public static function getOrderGoods ($order_id) {
		$field = 'goods_id, goods_name, goods_number, (goods_number*goods_price) as subtotal';
		$order_goods = RC_DB::table('order_goods')->where('order_id', $order_id)->select(RC_DB::raw($field))->get();
		$total_goods_number = 0;
		$taotal_goods_amount = 0;
		$list = [];
		if ($order_goods) {
			foreach ($order_goods as $row) {
				$total_goods_number += $row['goods_number'];
				$taotal_goods_amount += $row['subtotal'];
				$list[] = array(
						'goods_id' 			=> $row['goods_id'],
						'goods_name'		=> $row['goods_name'],
						'goods_number'		=> $row['goods_number'],
						'subtotal'			=> $row['subtotal'],
						'formatted_subtotal'=> price_format($row['subtotal'], false),
				);
			}
		}
	
		return array('list' => $list, 'total_goods_number' => $total_goods_number, 'taotal_goods_amount' => $taotal_goods_amount);
	}
	
	/**
	 * 用户信息
	 */
	public static function getUserInfo ($user_id = 0) {
		$user_info = RC_DB::table('users')->where('user_id', $user_id)->first();
		return $user_info;
	}
}