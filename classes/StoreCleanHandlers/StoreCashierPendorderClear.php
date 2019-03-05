<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2018/12/12
 * Time: 14:04
 */

namespace Ecjia\App\Cashier\StoreCleanHandlers;

use Ecjia\App\Store\StoreCleanAbstract;
use RC_Uri;
use RC_DB;
use RC_Api;
use ecjia_admin;

class StoreCashierPendorderClear extends StoreCleanAbstract
{

    /**
     * 代号标识
     * @var string
     */
    protected $code = 'store_cashier_pendorder_clear';

    /**
     * 名称
     * @var string
     */
    protected $name = '收银台挂单';

    /**
     * 排序
     * @var int
     */
    protected $sort = 22;

    /**
     * 数据描述及输出显示内容
     */
    public function handlePrintData()
    {
        $text = __('将当前店铺内，收银台上的挂单数据全部删除', 'cashier');
        return <<<HTML

<span class="controls-info w300">$text</span>

HTML;

    }

    /**
     * 获取数据统计条数
     *
     * @return mixed
     */
    public function handleCount()
    {
        $count = RC_DB::table('cashier_pendorder')->where('store_id', $this->store_id)->count();

        return $count;
    }


    /**
     * 执行清除操作
     *
     * @return mixed
     */
    public function handleClean()
    {
        $count = $this->handleCount();
        if (empty($count)) {
            return true;
        }

        $result = RC_DB::table('cashier_pendorder')->where('store_id', $this->store_id)->delete();

        if ($result) {
            $this->handleAdminLog();
        }

        return $result;
    }

    /**
     * 返回操作日志编写
     *
     * @return mixed
     */
    public function handleAdminLog()
    {
        \Ecjia\App\Store\Helper::assign_adminlog_content();

        $store_info = RC_Api::api('store', 'store_info', array('store_id' => $this->store_id));

        $merchants_name = !empty($store_info) ? sprintf(__('店铺名是%s', 'cashier'), $store_info['merchants_name']) : sprintf(__('店铺ID是%s', 'cashier'), $this->store_id);

        ecjia_admin::admin_log($merchants_name, 'clean', 'store_cashier_pendorder');
    }

    /**
     * 是否允许删除
     *
     * @return mixed
     */
    public function handleCanRemove()
    {
        return $this->handleCount() != 0 ? true : false;
    }


}