<?php

namespace Delivery-Slip\Providers;

use Plenty\Plugin\ServiceProvider;

/**
 * Class Delivery-SlipServiceProvider
 * @package Delivery-Slip\Providers
 */
class Delivery-SlipServiceProvider extends ServiceProvider
{
    /**
    * Register the route service provider
    */
    public function register()
    {
        $this->getApplication()->register(Delivery-SlipRouteServiceProvider::class);
    }
}

<?php

use Plenty\Modules\Order\Shipping\Dispatcher\Event\ShippingEvent;
use Plenty\Plugin\Events\Dispatcher;
use Plenty\Plugin\ServiceProvider;
use Plugin\ThirdPartyLogistics\Services\ApiService;
use Plenty\Modules\Order\Shipping\Events\AfterCreateDeliveryNote;
use Plenty\Modules\Order\Shipping\Package\Contracts\ShippingPackageRepositoryContract;

class MyPluginServiceProvider extends ServiceProvider
{
    public function boot(Dispatcher $eventDispatcher, ApiService $apiService, ShippingPackageRepositoryContract $shippingPackageRepository)
    {
        // Register a listener for the ShippingEvent::AFTER_CREATE_DELIVERY_SLIP event
        $eventDispatcher->listen(ShippingEvent::AFTER_CREATE_DELIVERY_SLIP, function (ShippingEvent $event) use ($apiService) {
            // Get the order ID and delivery ID from the event
            $orderId = $event->getOrderId();
            $deliveryId = $event->getDeliveryId();

            // Get the order
