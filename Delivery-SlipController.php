<?php

namespace Plugins\DeliverySlip\src\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Templates\Twig;
use Plugins\DeliverySlip\src\Services\ThirdPartyApiService;
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

    /**
     * @var ThirdPartyApiService
     */
    private $thirdPartyApiService;

    public function __construct(
        ShippingInformationRepositoryContract $shippingInformationRepository,
        ThirdPartyApiService $thirdPartyApiService
    ) {
        $this->shippingInformationRepository = $shippingInformationRepository;
        $this->thirdPartyApiService = $thirdPartyApiService;
    }

    public function createDeliverySlip(Request $request, Twig $twig)
    {
        $orderId = $request->get('orderId');
        $shippingOrder = $this->shippingInformationRepository->getShippingInformationByOrderId($orderId);

        // Call third-party API to generate PDF file
        $pdfData = $this->thirdPartyApiService->generatePdf($shippingOrder);

        // Save PDF file to local storage
        $path = 'delivery_slips/' . $shippingOrder->id . '.pdf';
        file_put_contents($path, $pdfData);

        // Update delivery order with PDF file path
        $shippingOrder->pdf_path = $path;
        $shippingOrder->save();

        $event = new DeliverySlipCreatedEvent($shippingOrder);
        $this->getLogger(__METHOD__)->info('Delivery slip created', [
            'orderId' => $orderId
        ]);

        return $twig->render('DeliverySlip::content.deliverySlip', ['orderId' => $orderId]);
    }
}
