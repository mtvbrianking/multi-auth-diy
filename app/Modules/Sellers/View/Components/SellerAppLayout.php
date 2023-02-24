<?php

namespace App\Modules\Sellers\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class SellerAppLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('seller::layouts.app');
    }
}
