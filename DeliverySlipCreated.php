<?php namespace Plugins\DeliverySlip\src\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Templates\Twig;
use Plenty\Modules\Order\Shipping\Information\Contracts\ShippingInformationRepositoryContract;
use Plugins\DeliverySlip\src\Events\DeliverySlipCreatedEvent;
use Plenty\Plugin\Log\Loggable;

class DeliverySlipController extends Controller
{
    use Loggable;

    /**
     * @var ShippingInformationRepositoryContract
     */
    private $shippingInformationRepository;

    public function __construct(ShippingInformationRepositoryContract $shippingInformationRepository)
    {
        $this->shippingInformationRepository = $shippingInformationRepository;
    }

    public function createDeliverySlip(Request $request, Twig $twig)
    {
        $orderId = $request->get('orderId');
        $shippingOrder = $this->shippingInformationRepository->getShippingInformationByOrderId($orderId);

        $event = new DeliverySlipCreatedEvent($shippingOrder);
        $this->getLogger(__METHOD__)->info('Delivery slip created', [
            'orderId' => $orderId
        ]);

        return $twig->render('DeliverySlip::content.deliverySlip', ['orderId' => $orderId]);
    }
}

namespace Plugins\DeliverySlip\src\Handlers;

use Plugins\DeliverySlip\src\Events\DeliverySlipCreatedEvent;
use Plenty\Modules\Order\Shipping\Information\Models\ShippingOrder;

class DeliverySlipCreated
{
    public function handle(DeliverySlipCreatedEvent $event)
    {
        // API request code here to get PDF file from third-party API

        // Save PDF file to local storage
        $path = 'delivery_slips/' . $event->deliveryOrder->id . '.pdf';
        file_put_contents($path, $pdfData);

        // Update delivery order with PDF file path
        $shippingOrder = $event->deliveryOrder->getShippingOrder();
        $shippingOrder->setAttribute('pdf_path', $path);
        $shippingOrder->save();
    }
}
