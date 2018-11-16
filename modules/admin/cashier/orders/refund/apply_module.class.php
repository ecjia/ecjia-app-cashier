<?php
//
//    ______         ______           __         __         ______
//   /\  ___\       /\  ___\         /\_\       /\_\       /\  __ \
//   \/\  __\       \/\ \____        \/\_\      \/\_\      \/\ \_\ \
//    \/\_____\      \/\_____\     /\_\/\_\      \/\_\      \/\_\ \_\
//     \/_____/       \/_____/     \/__\/_/       \/_/       \/_/ /_/
//
//   上海商创网络科技有限公司
//
//  ---------------------------------------------------------------------------------
//
//   一、协议的许可和权利
//
//    1. 您可以在完全遵守本协议的基础上，将本软件应用于商业用途；
//    2. 您可以在协议规定的约束和限制范围内修改本产品源代码或界面风格以适应您的要求；
//    3. 您拥有使用本产品中的全部内容资料、商品信息及其他信息的所有权，并独立承担与其内容相关的
//       法律义务；
//    4. 获得商业授权之后，您可以将本软件应用于商业用途，自授权时刻起，在技术支持期限内拥有通过
//       指定的方式获得指定范围内的技术支持服务；
//
//   二、协议的约束和限制
//
//    1. 未获商业授权之前，禁止将本软件用于商业用途（包括但不限于企业法人经营的产品、经营性产品
//       以及以盈利为目的或实现盈利产品）；
//    2. 未获商业授权之前，禁止在本产品的整体或在任何部分基础上发展任何派生版本、修改版本或第三
//       方版本用于重新开发；
//    3. 如果您未能遵守本协议的条款，您的授权将被终止，所被许可的权利将被收回并承担相应法律责任；
//
//   三、有限担保和免责声明
//
//    1. 本软件及所附带的文件是作为不提供任何明确的或隐含的赔偿或担保的形式提供的；
//    2. 用户出于自愿而使用本软件，您必须了解使用本软件的风险，在尚未获得商业授权之前，我们不承
//       诺提供任何形式的技术支持、使用担保，也不承担任何因使用本软件而产生问题的相关责任；
//    3. 上海商创网络科技有限公司不对使用本产品构建的商城中的内容信息承担责任，但在不侵犯用户隐
//       私信息的前提下，保留以任何方式获取用户信息及商品信息的权利；
//
//   有关本产品最终用户授权协议、商业授权与技术服务的详细内容，均由上海商创网络科技有限公司独家
//   提供。上海商创网络科技有限公司拥有在不事先通知的情况下，修改授权协议的权力，修改后的协议对
//   改变之日起的新授权用户生效。电子文本形式的授权协议如同双方书面签署的协议一样，具有完全的和
//   等同的法律效力。您一旦开始修改、安装或使用本产品，即被视为完全理解并接受本协议的各项条款，
//   在享有上述条款授予的权力的同时，受到相关的约束和限制。协议许可范围以外的行为，将直接违反本
//   授权协议并构成侵权，我们有权随时终止授权，责令停止损害，并保留追究相关责任的权力。
//
//  ---------------------------------------------------------------------------------
//
defined('IN_ECJIA') or exit('No permission resources.');

/**
 * 收银台订单退款申请
 * @author zrl
 *
 */
class admin_cashier_orders_refund_apply_module extends api_admin implements api_interface {
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request) {

		$this->authadminSession();
        if ($_SESSION['staff_id'] <= 0) {
            return new ecjia_error(100, 'Invalid session');
        }
        
		$order_id			= $this->requestData('order_id', 0);
		$refund_way			= $this->requestData('refund_way', ''); //original原路退回,cash退现金，balance退回余额
		$refund_way_arr 	= array('original', 'cash', 'balance');
		
		$device 			=  $this->device;
		
		$reasons = RC_Loader::load_app_config('refund_reasons', 'refund');
		$auto_refuse = $reasons['cashier_refund'];
		$refund_reason = $auto_refuse['0']['reason_id'];
		$refund_content = $auto_refuse['0']['reason_name'];
		
		if (empty($order_id) || empty($refund_way) || !in_array($refund_way, $refund_way_arr)) {
			return new ecjia_error('invalid_parameter', '参数错误');
		}
		
		$order_info = RC_Api::api('orders', 'order_info', array('order_id' => $order_id, 'store_id' => $_SESSION['store_id'], 'referer' => 'ecjia-cashdesk'));
		
		if (empty($order_info)) {
			return new ecjia_error('not_exists_info', '订单信息不存在！');
		}
		$options = array(
				'refund_type' 			=> 'return',
				'refund_content'		=> $refund_content,
				'device'				=> $device,
				'refund_reason'			=> $refund_reason,
				'order_id'				=> $order_id,
				'order_info'			=> $order_info
		);
		
		//生成退款申请单
		$generate_refund = RC_Api::api('refund', 'refund_apply', $options);
		
		if (is_ecjia_error($generate_refund)) {
			return $generate_refund;
		} else {
			if (!empty($generate_refund)) {
				//商家同意退款申请
				$agree_options = array(
						'refund_id' => $generate_refund,
						'staff_id'	=> $_SESSION['staff_id'],
						'staff_name'=> $_SESSION['staff_name']
				);
				$refund_agree = RC_Api::api('refund', 'refund_agree', $agree_options);
				if (is_ecjia_error($refund_agree)) {
					return $refund_agree;
				} else {
				$refund_agree = true;
					if ($refund_agree) {
						$returnway_shop_options = array(
								'refund_id' 	=> $generate_refund, 
						);
						//买家退货给商家
						$refund_returnway_shop = RC_Api::api('refund', 'refund_returnway_shop', $returnway_shop_options);
						if (is_ecjia_error($refund_returnway_shop)) {
							return $refund_returnway_shop;
						} else {
						$refund_returnway_shop = true;
							if ($refund_returnway_shop) {
								$merchant_confirm_options = array(
										'refund_id'		=> $generate_refund,
										'action_note'	=> '审核通过',
										'store_id'      => $_SESSION['store_id'],
										'staff_id'		=> $_SESSION['staff_id'],
										'staff_name'	=> $_SESSION['staff_name'],
								);
								//商家确认收货
								$refund_merchant_confirm = RC_Api::api('refund', 'merchant_confirm', $merchant_confirm_options);
								if (is_ecjia_error($refund_merchant_confirm)) {
									return $refund_merchant_confirm;
								} else {
									//去退款
									if ($refund_merchant_confirm) {
										//原路退回
										if ($refund_way == 'original') { 
											//TODO
											$back_type = 'original';
										} elseif ($refund_way == 'cash') { //退现金
											$back_type = 'cash';
										}
										//现金和原路退款成功后，后续操作
										if ($refund_way == 'original' || $refund_way == 'cash') {
											RC_Loader::load_app_class('RefundOrderInfo', 'refund', false);
											$refund_info = RefundOrderInfo::get_refund_order_info($generate_refund);
											
											$back_money_total = $refund_info['surplus'] + ['money_paid'];
											$back_integral = $refund_info['integral'];
											
											if ($refund_info['user_id'] > 0) {
												if ($refund_info['integral'] > 0) { //下单有没使用积分
													//退还下单使用的积分
													RC_DB::table('users')->where('user_id', $refund_info['user_id'])->increment('pay_points', $refund_info['integral']);
												}
												/*所退款订单，有没赠送积分；有赠送的话，赠送的积分扣除*/
												$order_give_integral_info = RC_DB::table('account_log')->where('user_id', $refund_info['user_id'])->where('from_type', 'order_give_integral')->where('from_value', $refund_info['order_sn'])->first();
												if (!empty($order_give_integral_info)) {
													$options = array(
															'user_id'			=> $order_give_integral_info['user_id'],
															'rank_points'		=> intval($order_give_integral_info['rank_points'])*(-1),
															'pay_points'		=> intval($order_give_integral_info['pay_points'])*(-1),
															'change_desc'		=> '订单退款，扣除订单'.$refund_info['order_sn'].'下单时赠送的积分',
															'change_type'		=> ACT_REFUND,
															'from_type'			=> 'refund_deduct_integral',
															'from_value'		=> $refund_info['order_sn']
													);
													RC_Api::api('user', 'account_change_log',$options);
												}
											}
											
											if ($refund_way == 'cash') {
												$action_back_content = '收银台申请退款，现金退款成功';
											} else {
												$action_back_content = '收银台申请退款，原路退回成功';
											}
											
											//更新打款表
											$data = array(
													'action_back_type'			=>	$back_type,
													'action_back_time'			=>	RC_Time::gmtime(),
													'action_back_content'		=>	$action_back_content,
													'action_user_type'			=>  'merchant',
													'action_user_id'			=>	$_SESSION['staff_id'],
													'action_user_name'			=>	$_SESSION['staff_name'],
											);
											
											RC_DB::table('refund_payrecord')->where('id', $refund_merchant_confirm)->update($data);

											//更新售后订单表
											$data = array(
												'refund_status'	=> Ecjia\App\Refund\RefundStatus::TRANSFERED,
												'refund_time'	=> RC_Time::gmtime(),
											);
											RC_DB::table('refund_order')->where('refund_id', $generate_refund)->update($data);

											//更新订单操作表
											$action_note = '退款金额已退回'.$back_money_total.'元，退回积分为：'.$back_integral;
											$data = array(
												'refund_id' 		=> $generate_refund,
												'action_user_type'	=>	'merchant',
												'action_user_id'	=>  $_SESSION['staff_id'],
												'action_user_name'	=>	$_SESSION['staff_name'],
												'status'		    =>  Ecjia\App\Refund\RefundStatus::AGREE,
												'refund_status'		=>  Ecjia\App\Refund\RefundStatus::TRANSFERED,
												'return_status'		=>  Ecjia\App\Refund\RefundStatus::CONFIRM_RECV,
												'action_note'		=>  $action_note,
												'log_time'			=>  RC_Time::gmtime(),
											);
											RC_DB::table('refund_order_action')->insertGetId($data);

											RC_Api::api('commission', 'add_bill_queue', array('order_type' => 'refund', 'order_id' => $refund_info['refund_id']));

											//售后订单状态变动日志表
											RefundStatusLog::refund_payrecord(array('refund_id' => $generate_refund, 'back_money' => $back_money_total));
											
											//普通订单状态变动日志表
											$order_id = RC_DB::table('refund_order')->where('refund_id', $generate_refund)->pluck('order_id');
											OrderStatusLog::refund_payrecord(array('order_id' => $order_id, 'back_money' => $back_money_total));
											
											//更新商家会员
											if ($refund_info['user_id'] > 0 && $refund_info['store_id'] > 0) {
												RC_Api::api('customer', 'store_user_buy', array('store_id' => $refund_info['store_id'], 'user_id' => $refund_info['user_id']));
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
}
// end