<?php

namespace App\Http\Controllers;

use App\Models\DeliverySlip;
use App\Services\MockApiService;
use Illuminate\Http\Request;

class DeliverySlipController extends Controller
{
    private $mockApiService;

    public function __construct(MockApiService $mockApiService)
    {
        $this->mockApiService = $mockApiService;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_number' => 'required|string',
            'customer_email' => 'required|string|email',
            'customer_name' => 'required|string',
            'delivery_address' => 'required|array',
            'delivery_address.street' => 'required|string',
            'delivery_address.city' => 'required|string',
            'delivery_address.postcode' => 'required|string',
            'delivery_address.country' => 'required|string',
        ]);

        $deliverySlip = DeliverySlip::create($data);

        $shipmentId = '';
        $trackingCode = '';

        if (config('services.mock_api.label_generation')) {
            $response = $this->mockApiService->sendJsonRequest('/label', $data);
            $shipmentId = $response['shipment_id'];
            $trackingCode = $response['tracking_code'];

            // TODO: Download PDF file and add to delivery slip
            $pdfUrl = config('services.mock_api.base_url') . '/pdf/' . $shipmentId;
            $deliverySlip->pdf_url = $pdfUrl;
        } else {
            $response = $this->mockApiService->sendJsonRequest('/shipment', $data);
            $shipmentId = $response['shipment_id'];
            $trackingCode = $response['tracking_code'];
        }

        $deliverySlip->update([
            'shipment_id' => $shipmentId,
            'tracking_code' => $trackingCode,
        ]);

        return response()->json([
            'message' => 'Delivery slip created successfully',
            'data' => $deliverySlip
        ], 200);
    }

    public function show($id)
    {
        $deliverySlip = DeliverySlip::findOrFail($id);

        return response()->json([
            'data' => $deliverySlip
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $deliverySlip = DeliverySlip::findOrFail($id);

        $data = $request->validate([
            'order_number' => 'sometimes|required|string',
            'customer_email' => 'sometimes|required|string|email',
            'customer_name' => 'sometimes|required|string',
            'delivery_address' => 'sometimes|required|array',
            'delivery_address.street' => 'sometimes|required|string',
            'delivery_address.city' => 'sometimes|required|string',
            'delivery_address.postcode' => 'sometimes|required|string',
            'delivery_address.country' => 'sometimes|required|string',
        ]);

        $deliverySlip->update($data);

        return response()->json([
            'message' => 'Delivery slip updated successfully',
            'data' => $deliverySlip
        ], 200);
    }

    public function destroy($id)
    {
        $deliverySlip = DeliverySlip::findOrFail($id);
        $deliverySlip->delete();

        return response()->json([
            'message' => 'Delivery slip deleted successfully'
        ], 200);
    }
}


<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $request->user()->plenty_token,
            'Accept' => 'application/json',
        ])->get('https://example.plentymarkets-cloud01.com/rest/items/items');

        return view('products.index', [
            'products' => $response->json(),
        ]);
    }

    public function show(Request $request, $id)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $request->user()->plenty_token,
            'Accept' => 'application/json',
        ])->get('https://example.plentymarkets-cloud01.com/rest/items/items/' . $id);

        return view('products.show', [
            'product' => $response->json(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $request->user()->plenty_token,
            'Accept' => 'application/json',
        ])->put('https://example.plentymarkets-cloud01.com/rest/items/items/' . $id, [
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ]);

        return redirect()->route('products.show', ['id' => $id]);
    }
}
