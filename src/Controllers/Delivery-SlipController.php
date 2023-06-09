<?php

namespace Plugins\DeliverySlip\src\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Templates\Twig;
use Plenty\Modules\Order\Shipping\Information\Contracts\ShippingInformationRepositoryContract;
use Plenty\Modules\Order\Shipping\Information\Models\ShippingInformation;
use Plenty\Modules\Order\Shipping\Package\Contracts\ShippingPackageRepositoryContract;
use Plenty\Modules\Order\Shipping\Package\Models\ShippingPackage;
use Plenty\Plugin\Log\Loggable;
use Plenty\Plugin\ConfigRepository;
use Plenty\Plugin\PDFGenerator\PdfGenerator;

class DeliverySlipController extends Controller
{
    use Loggable;

    /**
     * @var ShippingInformationRepositoryContract
     */
    private $shippingInformationRepository;

    /**
     * @var ShippingPackageRepositoryContract
     */
    private $shippingPackageRepository;

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(
        ShippingInformationRepositoryContract $shippingInformationRepository,
        ShippingPackageRepositoryContract $shippingPackageRepository,
        ConfigRepository $configRepository
    ) {
        $this->shippingInformationRepository = $shippingInformationRepository;
        $this->shippingPackageRepository = $shippingPackageRepository;
        $this->configRepository = $configRepository;
    }

    public function createDeliverySlip(Request $request, Twig $twig, PdfGenerator $pdfGenerator)
    {
        $orderId = $request->get('orderId');

        // Get the order's shipping information
        $shippingOrder = $this->shippingInformationRepository->getShippingInformationByOrderId($orderId);

        // Get the shipment ID and tracking code from the request
        $shipmentId = $request->get('shipmentId');
        $trackingCode = $request->get('trackingCode');

        // Add the shipment ID and tracking code to the shipping information
        $shippingOrder->setAttribute('shipmentId', $shipmentId);
        $shippingOrder->setAttribute('trackingCode', $trackingCode);

        // Save the updated shipping information
        $this->shippingInformationRepository->updateShippingInformation($shippingOrder);

        // Get the order's shipping packages
        $shippingPackages = $this->shippingPackageRepository->listShippingPackages($orderId);

        // Update the shipping package(s) with the shipment ID and tracking code
        foreach ($shippingPackages as $shippingPackage) {
            $shippingPackage->setAttribute('shipmentId', $shipmentId);
            $shippingPackage->setAttribute('trackingCode', $trackingCode);
            $this->shippingPackageRepository->updateShippingPackage($shippingPackage);
        }

        // Get the order's shipping status
        $shippingStatus = $shippingOrder->status;

        // Render the delivery slip template with the order ID, shipment ID, tracking code, and shipping status
        $pdfContent = $twig->render('@DeliverySlip::content.deliverySlip.twig', [
            'orderId' => $orderId,
            'shipmentId' => $shipmentId,
            'trackingCode' => $trackingCode,
            'shippingStatus' => $shippingStatus
        ]);

        // Generate the PDF document from the template content
        $pdf = $pdfGenerator->generateFromHtml($pdfContent, [
            'format' => 'A4',
            'orientation' => 'portrait'
        ]);

        // Download the PDF if "Label generation" is enabled
        $config = $this->configRepository->get('Plentymarkets-Delivery-Slip.label_generation');
        if ($config) {
            $pdf->download('order_'.$orderId.'.pdf');
        }

         // Return the PDF document
        return $pdf;
    }
}
