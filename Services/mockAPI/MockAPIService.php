<?php

namespace Plugins\DeliverySlip\Services\mockAPI;

use Plenty\Modules\Plugin\Services\ApiGateway\Contracts\ApiGatewayRequestInterface;

class MockAPIService
{
    /**
     * Handles a mock API request and returns dummy data.
     *
     * @param ApiGatewayRequestInterface $request The API request object.
     *
     * @return array The response data.
     */
    public function handleMockRequest(ApiGatewayRequestInterface $request)
    {
        // TODO: Perform any required authentication or authorization checks.

        // Retrieve the request parameters.
        $params = $request->getParams();
        
        // Get the order ID and tracking number from the request parameters.
        
        //                'order_id' => 1234,
        //      'tracking_number' => 'ABC123',
        
        $orderId = $params['order_id'] ?? '';
        $trackingNumber = $params['tracking_number'] ?? '';

        // TODO: Implement the actual API call to the shipping provider.
        // For now, return dummy response data.
        return [
            'success' => true,
            'data' => [
                'order_id' => $orderId,
                'tracking_number' => $trackingNumber,
            ],
        ];
    }
}
