<?php

namespace Plugins\DeliverySlip\Providers;

use Plenty\Modules\Order\Shipping\Contracts\ParcelServiceContract;
use Plenty\Plugin\ServiceProvider;
use Plugins\DeliverySlip\Services\MockApiService;
use Plugins\DeliverySlip\Services\DeliverySlip;

class DeliverySlipServiceProvider extends ServiceProvider
{
    public function register()
    {
        // TODO: Register any necessary bindings or services here.
        // Bind the MockApiService class to the container.
        $this->getApplication()->bind(MockApiService::class, function () {
            // TODO: Use the appropriate config value for the debug URL.
            return new MockApiService(config('plugins.delivery_slip.debug_url'));
        });

        // Bind the DeliverySlip class to the container.
        $this->getApplication()->bind(DeliverySlip::class, function () {
            return new DeliverySlip($this->getApplication()->get(ParcelServiceContract::class));
        });
    }

    public function boot()
    {
        // TODO: Define any necessary routes or event listeners here.
        // Get the router instance.
        $router = $this->getApplication()->getRouter();

        // Register the mock API route.
        $router->post('delivery-slip/mock-api', 'Plugins\DeliverySlip\Controllers\MockAPIController@handleMockRequest');
    }
}
