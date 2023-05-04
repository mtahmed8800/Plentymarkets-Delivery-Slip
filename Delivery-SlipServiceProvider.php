<?php

namespace App\Providers;

use App\Services\MockApiService;
use Plenty\Modules\Order\Shipping\Contracts\ParcelServiceContract;
use Plenty\Plugin\ServiceProvider;

class DeliverySlipServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->getApplication()->register(ParcelServiceProvider::class);

        $this->getApplication()->bind(MockApiService::class, function () {
            return new MockApiService(config('services.mock_api.debug_url'));
        });

        $this->getApplication()->bind(DeliverySlip::class, function () {
            return new DeliverySlip($this->getApplication()->get(ParcelServiceContract::class));
        });
    }
}
