<?php

namespace Delivery-Slip\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;

/**
 * Class Delivery-SlipRouteServiceProvider
 * @package Delivery-Slip\Providers
 */
class Delivery-SlipRouteServiceProvider extends RouteServiceProvider
{
    /**
     * @param Router $router
     */
    public function map(Router $router)
    {
        $router->get('hello-world','Delivery-Slip\Controllers\Delivery-SlipController@getHelloWorldPage');
    }
}

<?php

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
