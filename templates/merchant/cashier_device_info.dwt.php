<?php defined('IN_ECJIA') or exit('No permission resources.');?> 
<!-- {extends file="ecjia-merchant.dwt.php"} -->

<!-- {block name="footer"} -->
<script type="text/javascript">
	ecjia.merchant.cashier_device.init();
</script>
<style>
.ecjia-dn{
	display:none;
}
</style>
<!-- {/block} -->

<!-- {block name="home-content"} -->
<div class="page-header">
	<div class="pull-left">
		<h2><!-- {if $ur_here}{$ur_here}{/if} --></h2>
  	</div>
  	<div class="pull-right">
  		{if $action_link}
		<a href="{$action_link.href}" class="btn btn-primary data-pjax">
			<i class="fa fa-reply"></i> {$action_link.text}
		</a>
		{/if}
  	</div>
  	<div class="clearfix"></div>
</div>

<div class="row">
    <div class="col-lg-12">
        <section class="panel">
            <div class="panel-body">
                <div class="form">
                    <form class="cmxform form-horizontal" name="theForm" action="{$form_action}"  method="post" enctype="multipart/form-data" data-toggle='from'>
                        <div class="form-group">
                            <label class="control-label col-lg-2">{t}设备名称：{/t}</label>
                            <div class="controls col-lg-6">
                                <input class="form-control" name="device_name" type="text" value="{$cashier_device_info.device_name}"/>
                            </div>
                            <span class="input-must">{lang key='system::system.require_field'}</span>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-2">{t}设备MAC地址：{/t}</label>
                            <div class="controls col-lg-6">
                                <input class="form-control" name="device_mac" type="text" value="{$cashier_device_info.device_mac}"/>
                            </div>
                            <span class="input-must">{lang key='system::system.require_field'}</span>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-2">{t}产品序列号：{/t}</label>
                            <div class="controls col-lg-6">
                                <input class="form-control" name="product_sn" type="text" value="{$cashier_device_info.product_sn}"/>
                            </div>
                            <span class="input-must">{lang key='system::system.require_field'}</span>
                        </div>
                        
                         <div class="form-group">
                            <label class="control-label col-lg-2">{t}收银设备类型：{/t}</label>
                            <div class="col-lg-6">
                                <select class="form-control w510" name="cashier_type" device_id="{$cashier_device_info}">
                                	<option value="">{t}请选择收银设备类型...{/t}</option>
									<option value="cashier-desk" {if $cashier_device_info.cashier_type eq "cashier-desk"}selected{/if}>收银台</option>
									<option value="cashier-pos" {if $cashier_device_info.cashier_type eq "cashier-pos"}selected{/if}>收银POS机</option>
					            </select>
                            </div>
                            <span class="input-must">{lang key='system::system.require_field'}</span>
                        </div>
                        
                        <div class="form-group kooldesk-type {if $cashier_device_info.cashier_type neq 'cashier-desk'}ecjia-dn{/if}">
                            <label class="control-label col-lg-2">{t}机型：{/t}</label>
                            <div class="col-lg-10">
                                <input id="koolpos-kool11" type="radio" name="device_type" value="koolpos-kool11" {if $cashier_device_info.device_type eq 'koolpos-kool11'} checked="true" {/if} />
                                <label for="koolpos-kool11">koolpos-kool11</label>
                            </div>
                        </div>
                        
                        <div class="form-group koolpos-type {if $cashier_device_info.cashier_type neq 'cashier-pos'}ecjia-dn{/if}">
                            <label class="control-label col-lg-2">{t}机型：{/t}</label>
                            <div class="col-lg-10">
                                <input id="koolpos-N910" type="radio" name="device_type" value="koolpos-N910" {if $cashier_device_info.device_type eq 'koolpos-N910'} checked="true" {/if} />
                                <label for="koolpos-N910">koolpos-N910</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-2">{t}设备号：{/t}</label>
                            <div class="controls col-lg-6">
                                <input class="form-control" name="device_sn" type="text" value="{$cashier_device_info.device_sn}"/>
                            </div>
                            <span class="input-must">{lang key='system::system.require_field'}</span>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-2">{t}密码键盘序列号：{/t}</label>
                            <div class="controls col-lg-6">
                                <input class="form-control" name="keyboard_sn" type="text" value="{$cashier_device_info.keyboard_sn}"/>
                            </div>
                        </div>
                        
                        <div class="form-group ">
                            <label class="control-label col-lg-2">{t}状态：{/t}</label>
                            <div class="col-lg-10">
                                <input id="formatone" type="radio" name="status" value="1" {if $cashier_device_info.status eq '1'} checked="true" {/if} />
                                <label for="formatone">开启</label>
                                <input id="formattwo" type="radio" name="status" value="0" {if $scales_info.status eq '0'} checked="true" {/if} />
                                <label for="formattwo">关闭</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="col-lg-6 col-md-offset-2">
                            	<input type="hidden" name="id" value="{$cashier_device_info.id}">
                                <input class="btn btn-info" type="submit" value="{if $cashier_device_info.id}更新{else}确定{/if}">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>
<!-- {/block} -->
