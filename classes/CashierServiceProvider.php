<?php

namespace Ecjia\App\Cashier;

use Royalcms\Component\App\AppParentServiceProvider;

class CashierServiceProvider extends  AppParentServiceProvider
{
    
    public function boot()
    {
        $this->package('ecjia/app-cashier', null, dirname(__DIR__));
    }
    
    public function register()
    {
        
    }
    
    
    
}