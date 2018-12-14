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
 * 收银台订单支付完成；订单默认发货处理
 */
class CashierPaidProcessOrder
{    
	
	/**
	 * 取得不同类型的订单信息
	 * @param string $trade_type
	 * @param string $order_sn
	 * @return array 
	 */
	public static function GetDiffTypeOrderInfo($trade_type = '', $order_sn = '') 
	{
		$orderinfo = [];
		if ($trade_type == 'buy') {
				
			$orderinfo = RC_Api::api('orders', 'order_info', array('order_sn' => $order_sn));
				
		} elseif ($trade_type== 'quickpay') {
				
			 $orderinfo = RC_Api::api('quickpay', 'quickpay_order_info', array('order_sn' => $order_sn));
				
		} elseif ($trade_type == 'surplus') {
				
			$orderinfo = RC_Api::api('finance', 'user_account_order_info', array('order_sn' => $order_sn));
		}
		
		return $orderinfo;
	}
	
	/**
	 * 收银台消费订单默认发货
	 * @param array $orderinfo
	 */
	public static function processOrderDefaultship($orderinfo = array())
	{
		if (!empty($orderinfo)) {
			RC_Loader::load_app_class('OrderStatusLog', 'orders', false);
			//配货
			self::Prepare($orderinfo);
			//分单（生成发货单）
			self::Split($orderinfo);
			//发货
			self::Ship($orderinfo);
			//确认收货
			self::Affirm_received($orderinfo);
			//更新商品销量
			$res = RC_Api::api('goods', 'update_goods_sales', array('order_id' => $orderinfo['order_id']));
			if (is_ecjia_error($res)) {
				RC_Logger::getLogger('error')->info('收银台订单发货后更新商品销量失败【订单id|'.$orderinfo['order_id'].'】：'.$res->get_error_message());
			}
		}
	}
	
	/**
	 * 订单配货
	 */
	public static function Prepare($order_info) {
		$result = RC_Api::api('orders', 'order_operate', array('order_id' => $order_info['order_id'], 'order_sn' => '', 'operation' => 'prepare', 'note' => array('action_note' => '收银台配货')));
		if (is_ecjia_error($result)) {
			RC_Logger::getLogger('error')->info('收银台订单配货【订单id|'.$order_info['order_id'].'】：'.$result->get_error_message());
		}
	}
	
	/**
	 * 订单分单（生成发货单）
	 */
	public static function Split($order_info)
	{
		$result = RC_Api::api('orders', 'order_operate', array('order_id' => $order_info['order_id'], 'order_sn' => '', 'operation' => 'split', 'note' => array('action_note' => '收银台生成发货单')));
		if (is_ecjia_error($result)) {
			RC_Logger::getLogger('error')->info('收银台订单分单【订单id|'.$order_info['order_id'].'】：'.$result->get_error_message());
		} else {
			/*订单状态日志记录*/
			OrderStatusLog::generate_delivery_orderInvoice(array('order_id' => $order_info['order_id'], 'order_sn' => $order_info['order_sn']));
		}
	}
	
	/**
	 * 订单发货
	 */
	public static function Ship($order_info)
	{
		RC_Loader::load_app_class('order_ship', 'orders', false);
	
		$delivery_id = RC_DB::table('delivery_order')->where('order_sn', $order_info['order_sn'])->pluck('delivery_id');
		$invoice_no  = '';
		$result = order_ship::delivery_ship($order_info['order_id'], $delivery_id, $invoice_no, '收银台发货');
		if (is_ecjia_error($result)) {
			RC_Logger::getLogger('error')->info('收银台订单发货【订单id|'.$order_info['order_id'].'】：'.$result->get_error_message());
		} else {
			/*订单状态日志记录*/
			OrderStatusLog::delivery_ship_finished(array('order_id' => $order_info['order_id'], 'order_sn' => $order_info['order_sn']));
		}
	}
	
	/**
	 * 订单确认收货
	 */
	public static function Affirm_received($order_info)
	{
		$order_operate = RC_Loader::load_app_class('order_operate', 'orders');
		$order_info['pay_status'] = PS_PAYED;
		$order_operate->operate($order_info, 'receive', array('action_note' => '系统操作'));
		 
		/*订单状态日志记录*/
		OrderStatusLog::affirm_received(array('order_id' => $order_info['order_id']));
		 
		/* 记录log */
		order_action($order_info['order_sn'], OS_SPLITED, SS_RECEIVED, PS_PAYED, '收银台确认收货');
	}
	
	/**
	 * 获取不同类型订单支付成功需返回的数据
	 */
	public static function GetPaymentData($record_model, $order_info = array())
	{
		$pay_data = [];
		if (!empty($order_info)) {
			if ($record_model->trade_type == 'buy') {
				$pay_data = array(
					'order_id' 					=> intval($order_info['order_id']),
					'money_paid'				=> $order_info['money_paid'] + $order_info['surplus'],
					'formatted_money_paid'		=> price_format(($order_info['money_paid'] + $order_info['surplus']), false),
					'order_amount'				=> $order_info['order_amount'],
					'formatted_order_amount'	=> price_format($order_info['order_amount'], false),
					'pay_code'					=> $record_model->pay_code,
					'pay_name'					=> $record_model->pay_name,
					'pay_status'				=> 'success',
					'desc'						=> '订单支付成功！'
				);
			} elseif ($record_model->trade_type == 'quickpay') {
				$pay_data = array(
						'order_id' 					=> intval($order_info['order_id']),
						'money_paid'				=> $order_info['order_amount'] + $order_info['surplus'],
						'formatted_money_paid'		=> price_format(($order_info['order_amount'] + $order_info['surplus']), false),
						'order_amount'				=> 0.00,
						'formatted_order_amount'	=> price_format(0, false),
						'pay_code'					=> $record_model->pay_code,
						'pay_name'					=> $record_model->pay_name,
						'pay_status'				=> 'success',
						'desc'						=> '订单支付成功！'
				);
			} elseif ($record_model->trade_type == 'surplus') {
				$pay_data = array(
						'order_id' 					=> intval($order_info['id']),
						'money_paid'				=> $order_info['amount'],
						'formatted_money_paid'		=> price_format(($order_info['amount']), false),
						'order_amount'				=> 0.00,
						'formatted_order_amount'	=> price_format(0, false),
						'pay_code'					=> $record_model->pay_code,
						'pay_name'					=> $record_model->pay_name,
						'pay_status'				=> 'success',
						'desc'						=> '订单支付成功！'
				);
			}
		}
		
		return $pay_data;
	}
	
}