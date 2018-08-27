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
 *  ECJIA 散装商品管理程序
 */
class mh_bulk_goods extends ecjia_merchant {

	public function __construct() {
		parent::__construct();
		
		Ecjia\App\Cashier\Helper::assign_adminlog_content();
		
		RC_Script::enqueue_script('jquery-validate');
		RC_Script::enqueue_script('jquery-form');
		
		RC_Script::enqueue_script('smoke');
		RC_Script::enqueue_script('bootstrap-placeholder');
		RC_Style::enqueue_style('uniform-aristo');
		
		RC_Script::enqueue_script('ecjia-mh-editable-js');
		RC_Style::enqueue_style('ecjia-mh-editable-css');
		
		//时间控件
		RC_Script::enqueue_script('bootstrap-datepicker', RC_Uri::admin_url('statics/lib/datepicker/bootstrap-datepicker.min.js'));
		RC_Style::enqueue_style('datepicker', RC_Uri::admin_url('statics/lib/datepicker/datepicker.css'));
		
// 		RC_Style::enqueue_style('goods', RC_App::apps_url('statics/styles/goods.css', __FILE__), array());
		RC_Script::enqueue_script('mh_bulk_goods_list', RC_App::apps_url('statics/js/mh_bulk_goods_list.js', __FILE__));
		
		RC_Loader::load_app_func('merchant_goods', 'goods');
		
		ecjia_merchant_screen::get_current_screen()->set_parentage('cashier', 'cashier/mh_bulk_goods.php');
		ecjia_merchant_screen::get_current_screen()->add_nav_here(new admin_nav_here('散装商品管理', RC_Uri::url('cashier/mh_bulk_goods/init')));
	}

	/**
	* 散装商品列表
	*/
	public function init() {
	    $this->admin_priv('mh_bulk_goods_manage');

		$this->assign('ur_here', '散装商品列表');
		$this->assign('action_link', array('href' => RC_Uri::url('cashier/mh_bulk_goods/add'), 'text' => '添加散装商品'));
		
		$bulk_goods_list = $this->bulk_goods_list();
		$this->assign('bulk_goods_list', $bulk_goods_list);
		$this->assign('filter', $bulk_goods_list['filter']);
		$this->assign('cat_list', merchant_cat_list(0, 0, false));
		
		$this->assign('form_action', RC_Uri::url('cashier/mh_bulk_goods/batch'));

		$this->display('bulk_goods_list.dwt');
	}
	
	/**
	 * 批量操作
	 */
	public function batch() {
		/* 取得要操作的商品编号 */
		$goods_id = !empty($_POST['checkboxes']) ? $_POST['checkboxes'] : 0;
		
		if (!isset($_GET['type']) || $_GET['type'] == '') {
			return $this->showmessage('请选择操作', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		$goods_id = explode(',', $goods_id);
		$data = RC_DB::table('goods')->select('goods_name')->whereIn('goods_id', $goods_id)->get();
	
		if (isset($_GET['type'])) {
			/* 放入回收站 */
			if ($_GET['type'] == 'trash') {
				/* 检查权限 */
				$this->admin_priv('mh_bulk_goods_update', ecjia::MSGTYPE_JSON);
				Ecjia\App\Cashier\BulkGoods::update_goods($goods_id, 'is_delete', '1');
				$action = 'batch_trash';
			}
			/* 上架 */
			elseif ($_GET['type'] == 'on_sale') {
				/* 检查权限 */
				$this->admin_priv('mh_bulk_goods_update', ecjia::MSGTYPE_JSON);
				Ecjia\App\Cashier\BulkGoods::update_goods($goods_id, 'is_on_sale', '1');
				$action = 'batch_on';
			}
			/* 下架 */
			elseif ($_GET['type'] == 'not_on_sale') {
				/* 检查权限 */
				$this->admin_priv('mh_bulk_goods_update', ecjia::MSGTYPE_JSON);
				Ecjia\App\Cashier\BulkGoods::update_goods($goods_id, 'is_on_sale', '0');
				$action = 'batch_off';
			}
			/* 转移到分类 */
			elseif ($_GET['type'] == 'move_to') {
				/* 检查权限 */
				$this->admin_priv('mh_bulk_goods_update', ecjia::MSGTYPE_JSON);
				
				if (empty($_GET['target_cat'])) {
					return $this->showmessage('请先选择要转移的分类', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
				}
				Ecjia\App\Cashier\BulkGoods::update_goods($goods_id, 'merchant_cat_id', $_GET['target_cat']);
				$action = 'batch_move_cat';
			}
		}
	
		/* 记录日志 */
		if (!empty($data) && $action) {
			foreach ($data as $k => $v) {
				ecjia_merchant::admin_log($v['goods_name'], $action, 'goods');
			}
		}
	
		$page = empty($_GET['page']) ? '&page=1' : '&page='.$_GET['page'];
	
		$pjaxurl = RC_Uri::url('cashier/mh_bulk_goods/init' ,$page);
		
		/* 释放app缓存*/
		$orm_goods_db = RC_Model::model('goods/orm_goods_model');
		$goods_cache_array = $orm_goods_db->get_cache_item('goods_list_cache_key_array');
		if (!empty($goods_cache_array)) {
			foreach ($goods_cache_array as $val) {
				$orm_goods_db->delete_cache_item($val);
			}
			$orm_goods_db->delete_cache_item('goods_list_cache_key_array');
		}
		/*释放商品基本信息缓存*/
		if (!empty($goods_id)) {
			foreach ($goods_id as $v) {
				$cache_goods_basic_info_key = 'goods_basic_info_'.$v;
				$cache_basic_info_id = sprintf('%X', crc32($cache_goods_basic_info_key));
				$orm_goods_db->delete_cache_item($cache_basic_info_id);
			}
		}
	
		return $this->showmessage('批量操作成功', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('pjaxurl' => $pjaxurl));
	}
	
	/**
	 * 修改散装商品名称
	 */
	public function edit_goods_name() {
		$this->admin_priv('mh_bulk_goods_update', ecjia::MSGTYPE_JSON);
	
		$goods_id = intval($_POST['pk']);
		$goods_name = trim($_POST['value']);
		
		if (!empty($goods_name)) {
			RC_DB::table('goods')->where('goods_id', $goods_id)->where('store_id', $_SESSION['store_id'])->update(array('goods_name' => $goods_name, 'last_update' => RC_Time::gmtime()));
			/* 释放app缓存*/
			$orm_goods_db = RC_Model::model('goods/orm_goods_model');
			$goods_cache_array = $orm_goods_db->get_cache_item('goods_list_cache_key_array');
			if (!empty($goods_cache_array)) {
				foreach ($goods_cache_array as $val) {
					$orm_goods_db->delete_cache_item($val);
				}
				$orm_goods_db->delete_cache_item('goods_list_cache_key_array');
			}
			/*释放商品基本信息缓存*/
			$cache_goods_basic_info_key = 'goods_basic_info_'.$goods_id;
			$cache_basic_info_id = sprintf('%X', crc32($cache_goods_basic_info_key));
			$orm_goods_db->delete_cache_item($cache_basic_info_id);
			 
			return $this->showmessage('修改成功！', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('content' => stripslashes($goods_name)));
		} else {
			return $this->showmessage('请输入商品名称！', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
	}
	
	/**
	 * 修改散装商品货号
	 */
	public function edit_goods_sn() {
		$this->admin_priv('mh_bulk_goods_update', ecjia::MSGTYPE_JSON);
	
		$goods_id = intval($_POST['pk']);
		$goods_sn = trim($_POST['value']);
	
		if (empty($goods_sn)) {
			return $this->showmessage('请输入商品货号', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
	
		/* 检查是否重复 */
		if ($goods_sn) {
			$count = RC_DB::table('goods')->where('goods_sn', $goods_sn)->where('goods_id', '!=', $goods_id)->count();
			if ($count > 0) {
				return $this->showmessage('您输入的货号已存在，请换一个', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
			}
		}
		$query = RC_DB::table('products')->where('product_sn', $goods_sn)->pluck('goods_id');
	
		if ($query > 0) {
			return $this->showmessage('您输入的货号已存在，请换一个', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		//散装商品货号为7位
		$goods_sn_length = strlen($goods_sn);
		if ($goods_sn_length != 7) {
			return $this->showmessage('散装商品货号必须为7位', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		RC_DB::table('goods')->where('goods_id', $goods_id)->where('store_id', $_SESSION['store_id'])->update(array('goods_sn' => $goods_sn, 'last_update' => RC_Time::gmtime()));
	
		/* 释放app缓存*/
		$orm_goods_db = RC_Model::model('goods/orm_goods_model');
		$goods_cache_array = $orm_goods_db->get_cache_item('goods_list_cache_key_array');
		if (!empty($goods_cache_array)) {
			foreach ($goods_cache_array as $val) {
				$orm_goods_db->delete_cache_item($val);
			}
			$orm_goods_db->delete_cache_item('goods_list_cache_key_array');
		}
		/*释放商品基本信息缓存*/
		$cache_goods_basic_info_key = 'goods_basic_info_'.$goods_id;
		$cache_basic_info_id = sprintf('%X', crc32($cache_goods_basic_info_key));
		$orm_goods_db->delete_cache_item($cache_basic_info_id);
	
		return $this->showmessage(RC_Lang::get('goods::goods.edit_ok'),ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('content' => stripslashes($goods_sn)));
	}
	
	/**
	 * 修改散装商品价格
	 */
	public function edit_goods_price() {
		$this->admin_priv('mh_bulk_goods_update', ecjia::MSGTYPE_JSON);
	
		$goods_id = intval($_POST['pk']);
		$goods_price = floatval($_POST['value']);
		$price_rate = floatval(ecjia::config('market_price_rate') * $goods_price);
		$data = array(
				'shop_price'	=> $goods_price,
				'market_price'  => $price_rate,
				'last_update'   => RC_Time::gmtime()
		);
		if ($goods_price < 0 || $goods_price == 0 && $_POST['val'] != "$goods_price") {
			return $this->showmessage(RC_Lang::get('goods::goods.shop_price_invalid'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		} else {
			RC_DB::table('goods')->where('goods_id', $goods_id)->where('store_id', $_SESSION['store_id'])->update($data);
			//为更新用户购物车数据加标记
			RC_Api::api('cart', 'mark_cart_goods', array('goods_id' => $goods_id));
			/* 释放app缓存*/
			$orm_goods_db = RC_Model::model('goods/orm_goods_model');
			$goods_cache_array = $orm_goods_db->get_cache_item('goods_list_cache_key_array');
			if (!empty($goods_cache_array)) {
				foreach ($goods_cache_array as $val) {
					$orm_goods_db->delete_cache_item($val);
				}
				$orm_goods_db->delete_cache_item('goods_list_cache_key_array');
			}
			/*释放商品基本信息缓存*/
			$cache_goods_basic_info_key = 'goods_basic_info_'.$goods_id;
			$cache_basic_info_id = sprintf('%X', crc32($cache_goods_basic_info_key));
			$orm_goods_db->delete_cache_item($cache_basic_info_id);
				
			return $this->showmessage(RC_Lang::get('goods::goods.edit_ok'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('pjaxurl' => RC_Uri::url('cashier/mh_bulk_goods/init'), 'content' => number_format($goods_price, 2, '.', '')));
		}
	}
	
	/**
	 * 修改散装商品库存重量
	 */
	public function edit_weight_stock() {
		$this->admin_priv('mh_bulk_goods_update', ecjia::MSGTYPE_JSON);
	
		$goods_id = intval($_POST['pk']);
		$weight_stock = !empty($_POST['value']) ? intval($_POST['value']) : 0;
	
		$data = array(
				'weight_stock' 	=> $weight_stock,
				'last_update' 	=> RC_Time::gmtime()
		);
	
		if ($weight_stock < 0) {
			return $this->showmessage('商品重量库存错误', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		RC_DB::table('goods')->where('goods_id', $goods_id)->where('store_id', $_SESSION['store_id'])->update($data);
	
		/* 释放app缓存*/
		$orm_goods_db = RC_Model::model('goods/orm_goods_model');
		$goods_cache_array = $orm_goods_db->get_cache_item('goods_list_cache_key_array');
		if (!empty($goods_cache_array)) {
			foreach ($goods_cache_array as $val) {
				$orm_goods_db->delete_cache_item($val);
			}
			$orm_goods_db->delete_cache_item('goods_list_cache_key_array');
		}
		/*释放商品基本信息缓存*/
		$cache_goods_basic_info_key = 'goods_basic_info_'.$goods_id;
		$cache_basic_info_id = sprintf('%X', crc32($cache_goods_basic_info_key));
		$orm_goods_db->delete_cache_item($cache_basic_info_id);
	
		return $this->showmessage(RC_Lang::get('goods::goods.edit_ok'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('content' => $weight_stock));
	}
	
	/**
	 * 修改上架状态
	 */
	public function toggle_on_sale() {
		$this->admin_priv('mh_bulk_goods_update', ecjia::MSGTYPE_JSON);
	
		$goods_id = intval($_POST['id']);
		$on_sale = intval($_POST['val']);
	
		$data = array(
				'is_on_sale' 	=> $on_sale,
				'last_update' 	=> RC_Time::gmtime()
		);
		RC_DB::table('goods')->where('goods_id', $goods_id)->where('store_id', $_SESSION['store_id'])->update($data);
	
		/* 释放app缓存*/
		$orm_goods_db = RC_Model::model('goods/orm_goods_model');
		$goods_cache_array = $orm_goods_db->get_cache_item('goods_list_cache_key_array');
		if (!empty($goods_cache_array)) {
			foreach ($goods_cache_array as $val) {
				$orm_goods_db->delete_cache_item($val);
			}
			$orm_goods_db->delete_cache_item('goods_list_cache_key_array');
		}
		/*释放商品基本信息缓存*/
		$cache_goods_basic_info_key = 'goods_basic_info_'.$goods_id;
		$cache_basic_info_id = sprintf('%X', crc32($cache_goods_basic_info_key));
		$orm_goods_db->delete_cache_item($cache_basic_info_id);
	
		return $this->showmessage('已成功切换上架状态', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('content' => $on_sale));
	}
	
	/**
	 * 修改商品排序
	 */
	public function edit_sort_order() {
		$this->admin_priv('mh_bulk_goods_update', ecjia::MSGTYPE_JSON);
	
		$goods_id = intval($_POST['pk']);
		$sort_order = intval($_POST['value']);
		$data = array(
				'store_sort_order' 	=> $sort_order,
				'last_update' 		=> RC_Time::gmtime()
		);
		RC_DB::table('goods')->where('goods_id', $goods_id)->where('store_id', $_SESSION['store_id'])->update($data);
	
		return $this->showmessage('修改成功!', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('pjaxurl' => RC_uri::url('cashier/mh_bulk_goods/init', '&page='.$_GET['page'].'&sort_by='.$_GET['sort_by'].'&sort_order='.$_GET['sort_order']), 'content' => $sort_order));
	}
	
	/**
	 * 添加新商品
	 */
	public function add() {
		// 检查权限
		$this->admin_priv('mh_bulk_goods_update');
	
		ecjia_merchant_screen::get_current_screen()->add_nav_here(new admin_nav_here('散装商品列表'));
		$this->assign('action_link', array('href' => RC_Uri::url('cashier/mh_bulk_goods/init'), 'text' => '散装商品列表'));
	
		$merchant_cat = merchant_cat_list(0, 0, true, 2, false);		//店铺分类
		$this->assign('ur_here', '基本信息');
		
		$goods = array(
				'goods_id'				=> 0,
				'goods_desc'			=> '',
				'cat_id'				=> 0,
				'brand_id'				=> 0,
				'is_on_sale'			=> '1',
				'is_alone_sale'			=> '1',
				'is_shipping'			=> '0',
				'other_cat'				=> array(), // 扩展分类
				'goods_type'			=> 0, 		// 商品类型
				'shop_price'			=> 0,
				'promote_price'			=> 0,
				'market_price'			=> 0,
				'integral'				=> 0,
				'goods_number'			=> 0,
				'weight_stock'			=> 0.000,
				'warn_number'			=> 1,
				'promote_start_date'	=> RC_Time::local_date('Y-m-d'),
				'promote_end_date'		=> RC_Time::local_date('Y-m-d', RC_Time::local_strtotime('+1 month')),
				'goods_weight'			=> 0,
				'give_integral'			=> -1,
				'rank_integral'			=> -1
		);
	
		/* 商品名称样式 */
		$goods_name_style = isset($goods['goods_name_style']) ? $goods['goods_name_style'] : '';
	
		$this->assign('goods', $goods);
		$this->assign('goods_name_color', $goods_name_style);
	
		$this->assign('unit_list', Ecjia\App\Cashier\BulkGoods::unit_list());
		$this->assign('user_rank_list', Ecjia\App\Cashier\BulkGoods::get_rank_list());
	
	
		$volume_price_list = '';
		if (isset($_GET['goods_id'])) {
			$volume_price_list = Ecjia\App\Cashier\BulkGoods::get_volume_price_list($_GET['goods_id']);
		}
		if (empty($volume_price_list)) {
			$volume_price_list = array('0' => array('number' => '', 'price' => ''));
		}
		$this->assign('volume_price_list', $volume_price_list);
		$this->assign('form_action', RC_Uri::url('cashier/mh_bulk_good/insert'));
	
		$this->display('bulk_goods_info.dwt');
	}
	
	/**
	 * 获得商家商品列表
	 * @return array
	 */
	private function bulk_goods_list() {
		/* 过滤条件 */
		$filter ['keywords'] 		= empty ($_GET['keywords']) 		? '' 	: trim($_GET['keywords']);
		$filter ['type'] 			= !empty($_GET['type']) 			? $_GET['type'] : '';
	
		$filter ['sort_by'] 		= empty($_GET['sort_by']) 	? 'store_sort_order' : trim($_GET['sort_by']);
		$filter ['sort_order'] 		= empty($_GET['sort_order'])? 'asc' 			: trim($_GET['sort_order']);
	
		$db_goods = RC_DB::table('goods');
		$db_goods->where('store_id', $_SESSION['store_id'])->where('is_delete', 0)->where('extension_code', 'bulk');
		
		if ($filter ['type'] == '1') {
			$db_goods->where('is_alone_sale', 1);
		} elseif ($filter ['type'] == '2') {
			$db_goods->where('is_alone_sale', 0);
		}
		/* 关键字 */
		if (!empty ($filter ['keywords'])) {
			$db_goods->whereRaw("goods_name LIKE '%" . mysql_like_quote($filter ['keywords']) . "%'");
		}
	
		//筛选全部 已上架 未上架 商家
		$filter_count = $db_goods
		->select(RC_DB::raw('count(*) as count_goods_num, SUM(IF(is_on_sale = 1, 1, 0)) as count_on_sale, SUM(IF(is_on_sale = 0, 1, 0)) as count_not_sale'))->first();
	
		$dbgoods = RC_DB::table('goods')
			->where('extension_code', 'bulk')
			->where('store_id', $_SESSION['store_id'])
			->where('is_delete', 0);
		
		if ($filter ['type'] == '1') {
			$dbgoods->where('is_alone_sale', 1);
		} elseif ($filter ['type'] == '2') {
			$dbgoods->where('is_alone_sale', 0);
		}
		
		/* 关键字 */
		if (!empty ($filter ['keywords'])) {
			$dbgoods->whereRaw("goods_name LIKE '%" . mysql_like_quote($filter ['keywords']) . "%'");
		}
		/* 记录总数 */
		$count = $dbgoods->count('goods_id');
		$page = new ecjia_merchant_page ($count, 10, 3);
		$filter ['count'] 	= $filter_count;
	
		$row = $dbgoods
		->select('goods_id', 'goods_name', 'goods_type', 'goods_sn', 'shop_price', 'goods_thumb', 'is_on_sale', 'store_best', 'store_new', 'store_hot', 'store_sort_order', 'goods_number', 'integral', 'review_status')
		->orderBy($filter ['sort_by'], $filter['sort_order'])
		->orderBy('goods_id', 'desc')
		->take(10)
		->skip($page->start_id-1)
		->get();
		
		$filter ['keywords'] = stripslashes($filter ['keywords']);
		return array(
				'goods'		=> $row,
				'filter'	=> $filter,
				'page'		=> $page->show(2),
				'desc'		=> $page->page_desc()
		);
	}
	
}

// end