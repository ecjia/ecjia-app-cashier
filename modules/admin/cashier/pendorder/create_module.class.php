<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 收银台挂单创建
 * @author zrl
 * 
 */
class create_module extends api_admin implements api_interface {
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request) {

		$this->authadminSession();
        if ($_SESSION['staff_id'] <= 0) {
            return new ecjia_error(100, 'Invalid session');
        }
		
	    $rec_id = $this->requestData('rec_id', 0);
	   
	    if (empty($rec_id)) {
	        return new ecjia_error('invalid_parameter', '参数错误');
	    }
	    
	    $rec_ids = explode(',', $rec_id);
	    
	    $rec_cart_count = RC_DB::table('cart')->whereIn('rec_id', $rec_ids)->count();
	    if (empty($rec_cart_count)) {
	    	return new ecjia_error('cartgoods_not_exist', '指定的购物车商品不存在！');
	    }
	    
	    if ($_SESSION['staff_id'] > 0) {
	    	$store_id = $_SESSION['store_id'];
	    	$cashier_user_type = 'merchant';
	    } else {
	    	$store_id = 0;
	    	$cashier_user_type = 'admin';
	    }
	    
	    RC_Loader::load_app_class('pendorder', 'cashier', false);
	    $pendorder_sn = pendorder::gernerate_pendorder_sn();
	    $data = array(
	    		'store_id' 				=> $store_id,
	    		'pendorder_sn'			=> $pendorder_sn,
	    		'pendorder_time'		=> RC_Time::gmtime(),
	    		'cashier_user_id'		=> $_SESSION['staff_id'],	
	    		'cashier_user_type'		=> $cashier_user_type
	    );
	    
	    $pendorder_id = RC_DB::table('cashier_pendorder')->insertGetId($data);
	    if ($pendorder_id) {
	    	RC_DB::table('cart')->whereIn('rec_id', $rec_ids)->update(array('pendorder_id' => $pendorder_id));
	    }
	    
	    return array();
	}
	
}

// end