<?php

namespace Ecjia\App\Cashier;

use Royalcms\Component\App\AppServiceProvider;

class CashierServiceProvider extends  AppServiceProvider
{
    
    public function boot()
    {
        $this->package('ecjia/app-cashier');
    }
    
    public function register()
    {
        
    }
    
    
    
}