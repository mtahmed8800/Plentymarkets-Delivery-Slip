const configFields = [
  {
    key: 'label_generation_enabled',
    type: 'checkbox',
    label: 'Label generation',
    default: false
  },
  {
    key: 'debug_url',
    type: 'text',
    label: 'Debug URL',
    default: ''
  }
];

PluginManager.register('DeliverySlip', 'Plugin for generating delivery slips', configFields);

class ApiService {
  constructor(debugUrl) {
    this.debugUrl = debugUrl;
  }

  async createLabel(orderData) {
    const response = await fetch(this.debugUrl);
    const data = await response.json();

    const { shipmentId, trackingCode, pdfUrl } = data;

    const pdfResponse = await fetch(pdfUrl);
    const pdfBuffer = await pdfResponse.buffer();
    return { shipmentId, trackingCode, pdfBuffer };
  }

  async createShipment(orderData) {
    const response = await fetch(this.debugUrl);
    const data = await response.json();

    const { shipmentId, trackingCode } = data;

    return { shipmentId, trackingCode };
  }
}

const apiService = new ApiService(PluginManager.getPlugin('DeliverySlip').getConfig('debug_url'));

EventManager.subscribe('after_create_delivery', async ({ data }) => {
  const { deliveryOrder } = data;

  const orderData = await plentymarkets.get('orders/' + deliveryOrder.orderId);

  const isLabelGenerationEnabled = PluginManager.getPlugin('DeliverySlip').getConfig('label_generation_enabled');

  if (isLabelGenerationEnabled) {
    const { shipmentId, trackingCode, pdfBuffer } = await apiService.createLabel(orderData);

    await plentymarkets.put(`orders/${deliveryOrder.orderId}/attributes`, [
      {
        attributeId: 1, 
        value: shipmentId
      },
      {
        attributeId: 2,
        value: trackingCode
      }
    ]);

    await handleAfterRegisterShipment(deliveryOrder.orderId, pdfBuffer);
  } else {
    await plentymarkets.put(`orders/${deliveryOrder.orderId}/attributes`, [
      {
        attributeId: 1,
        value: ''
      },
      {
        attributeId: 2,
        value: ''
      }
    ]);
  }
});

const pluginApiClient = require('@plentymarkets/plugin-api-client');
const request = require('request-promise-native');

async function createDeliverySlip(event) {
  const { order } = event.data;

  const labelGenerationEnabled = await pluginApiClient.getConfig('Label generation');

  if (labelGenerationEnabled) {
    const debugUrl = await pluginApiClient.getConfig('Debug URL');

    const labelResponse = await request.post(`${debugUrl}/label`, {
      json: order,
    });

    const shipmentId = labelResponse.shipmentId;
    const trackingCode = labelResponse.trackingCode;
    const pdfUrl = labelResponse.pdfUrl;

    const pdfFile = await request.get({ url: pdfUrl, encoding: null });
    const deliverySlipFilename = `delivery_slip_${order.orderId}.pdf`;
    // save pdf file

    await pluginApiClient.addOrderAttribute(order.orderId, 'Shipment ID', shipmentId);
    await pluginApiClient.addOrderAttribute(order.orderId, 'Tracking Code', trackingCode);
  }
  else {
    await pluginApiClient.addOrderAttribute(order.orderId, 'Shipment ID', '');
    await pluginApiClient.addOrderAttribute(order.orderId, 'Tracking Code', '');
  }
}

function handleApiResponse(response) {
  if (response.status !== 200) {
    console.error('API request failed with status code ' + response.status);
    return;
  }

  let responseData = JSON.parse(response.responseText);

  // Check if response contains error message
  if (responseData.hasOwnProperty('error')) {
    console.error('API request returned an error: ' + responseData.error);
    return;
  }

  // Check if response contains shipment ID and tracking code
  if (responseData.hasOwnProperty('shipment_id') && responseData.hasOwnProperty('tracking_code')) {
    let shipmentId = responseData.shipment_id;
    let trackingCode = responseData.tracking_code;

    // Save shipment ID and tracking code to delivery order attributes
    saveShipmentInfoToOrder(shipmentId, trackingCode);

    // If label generation is enabled, download the PDF file
    if (isLabelGenerationEnabled()) {
      let pdfUrl = responseData.pdf_url;
      downloadAndAttachPdf(pdfUrl);
    }
  } else {
    console.error('API response did not contain shipment ID and/or tracking code');
    return;
  }
}
