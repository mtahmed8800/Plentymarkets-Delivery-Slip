<?php
namespace Plugins\DeliverySlip\src\PdfGenerator;

use Plenty\Plugin\ConfigRepository;
use Plenty\Plugin\Log\Loggable;
use Plenty\Plugin\PDFGenerator\PdfGenerator;
use Plenty\Modules\Order\Models\Order;

class DeliverySlipGenerator extends PdfGenerator
{
    use Loggable;

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * Generates the PDF for the given order.
     *
     * @param Order $order
     *
     * @return string
     */
    public function generatePdf(Order $order): string
    {
        // Get the path to the template file
        $templatePath = __DIR__ . '/../../templates/delivery-slip.html';

        // Load the template
        $template = file_get_contents($templatePath);

        // Replace template placeholders with actual data
        $template = str_replace('{{orderNumber}}', $order->orderNumber, $template);
        $template = str_replace('{{customerName}}', $order->customer->firstName . ' ' . $order->customer->lastName, $template);
        $template = str_replace('{{shippingAddress}}', $order->shippingAddress->address1, $template);

        // Generate the PDF using the HTML template
        $pdf = $this->generateFromHtml($template);

        return $pdf;
    }
}
