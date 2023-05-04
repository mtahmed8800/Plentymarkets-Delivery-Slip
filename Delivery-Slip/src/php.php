<script src="js/scripts.js"></script><?php

namespace App\Providers;
namespace App\Services;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Plenty\Modules\EventProcedures\Events\EventProceduresTriggered;
use Plenty\Plugin\ConfigRepository;
use Plenty\Plugin\Log\Loggable;
use Plenty\Plugin\Templates\Twig;
use Illuminate\Support\ServiceProvider;
use App\Services\DeliveryApiService;

class DeliveryPlugin
{
    use Loggable;

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * @var MockApiService
     */
    private $mockApiService;

    public function __construct(ConfigRepository $configRepository, MockApiService $mockApiService)
    {
        $this->configRepository = $configRepository;
        $this->mockApiService = $mockApiService;
    }

    public function handle_delivery_slip_created(EventProceduresTriggered $eventTriggered)
    {
        $order = $eventTriggered->getOrder();

        // Check if label generation is enabled
        $labelGenerationEnabled = $this->configRepository->get('MyPlugin.labelGeneration');
        if ($labelGenerationEnabled) {
            // Send the full order data to the label endpoint
            $response = $this->mockApiService->sendOrderDataToLabelEndpoint($order);

            // Extract the shipment ID, tracking code and PDF URL from the response
            $shipmentId = $response['shipmentId'];
            $trackingCode = $response['trackingCode'];
            $pdfUrl = $response['pdfUrl'];

            // Download the PDF file and save it alongside the delivery slip
            $pdfFile = file_get_contents($pdfUrl);
            file_put_contents(__DIR__ . '/pdf-files/delivery-slip-' . $order['id'] . '.pdf', $pdfFile);
        } else {
            // Send the data to the shipment endpoint
            $response = $this->mockApiService->sendOrderDataToShipmentEndpoint($order);

            // Extract the shipment ID and tracking code from the response
            $shipmentId = $response['shipmentId'];
            $trackingCode = $response['trackingCode'];
        }

        // Add the shipment ID and tracking code to the delivery order attributes
        $shipmentIdAttribute = 'shipmentId';
        $trackingCodeAttribute = 'trackingCode';
        $deliveryOrder = $order['shippingInformation'];
        $deliveryOrder['attributes'][$shipmentIdAttribute] = $shipmentId;
        $deliveryOrder['attributes'][$trackingCodeAttribute] = $trackingCode;

        // Save the updated delivery order
        $eventTriggered->setOrder($order);
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

            // Get the order and delivery objects from the Plentymarkets API
            $orderRepository = pluginApp(OrderRepositoryContract::class);
            $order = $orderRepository->findOrderById($orderId);
            $delivery = $order->getDeliveryById($deliveryId);

            // Check whether the "Label generation" checkbox is enabled in the plugin configuration
            $labelGenerationEnabled = $this->getConfigValue('label_generation_enabled');

            // Build the data to send to the API endpoint
            $payload = [
                // Add any other data you need to send here
            ];

            // Choose the appropriate API endpoint based on whether label generation is enabled
            if ($labelGenerationEnabled) {
                $endpoint = 'https://api.example.com/label';
            } else {
                $endpoint = 'https://api.example.com/shipment';
            }

            // Send the API request
            $response = $apiService->sendToApi($endpoint, $payload);

            // Handle the API response based on the endpoint
            if ($labelGenerationEnabled) {
                // Get the shipment ID, tracking code, and PDF URL from the API response
                $shipmentId = $response['shipment_id'];
                $trackingCode = $response['tracking_code'];
                $pdfUrl = $response['pdf_url'];

                // Download the PDF file and save it alongside the delivery slip
                $pdfContents = file_get_contents($pdfUrl);
                $pdfFilename = $orderId . '_' . $deliveryId . '.pdf';
                file_put_contents('/path/to/delivery_slips/' . $pdfFilename, $pdfContents);

                // Add the shipment ID and tracking code to the delivery attributes
                $delivery->setAttribute('Shipment ID', $shipmentId);
                $delivery->setAttribute('Tracking Code', $trackingCode);
            } else {
                // Get the shipment ID and tracking code from the API response
                $shipmentId = $response['shipment_id'];
                $trackingCode = $response['tracking_code'];

                // Add the shipment ID and tracking code to the delivery attributes
                $delivery->setAttribute('Shipment ID', $shipmentId);
                $delivery->setAttribute('Tracking Code', $trackingCode);
            }

            // Save the updated delivery object
            $deliveryRepository = pluginApp(DeliveryRepositoryContract::class);
            $deliveryRepository->saveDelivery($delivery);
        });

        // Register a listener for the AfterCreateDeliveryNote event
        $eventDispatcher->listen(AfterCreateDeliveryNote::class, function(AfterCreateDeliveryNote $event) use ($apiService, $shippingPackageRepository) {
            $deliveryOrder = $event->getDeliveryOrder();

            // Get delivery
