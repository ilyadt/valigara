<?php

declare(strict_types=1);

namespace App\Fba;

use App\Data\AbstractOrder;
use App\Data\BuyerInterface;
use App\ShippingServiceInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

class ShippingService implements ShippingServiceInterface
{
    public function __construct(
        private ClientInterface $client,
        private CreateFulfillmentOrderBodyBuilder $bodyBuilder)
    {}

    /**
     * @throws RuntimeException
     */
    public function ship(AbstractOrder $order, BuyerInterface $buyer): string
    {
        // https://developer-docs.amazon.com/sp-api/docs/fulfillment-outbound-api-v2020-07-01-reference#createfulfillmentorder
        try {
            $createFulfillmentOrderResp = $this->client->post(
                '/fba/outbound/2020-07-01/fulfillmentOrders',
                [
                    'headers' => ['content-type' => 'application/json'],
                    'json' => $this->bodyBuilder->build($order, $buyer),
                ],
            );
        } catch (GuzzleException $e) {
            throw new RuntimeException('POST fulfillmentOrders request error: ' . $e->getMessage() . '. orderID=' . $order->getOrderId());
        }

        if ($createFulfillmentOrderResp->getStatusCode() !== 200) {
            throw new RuntimeException('POST fulfillmentOrders http status error: ' . $createFulfillmentOrderResp->getStatusCode(). '. orderID=' . $order->getOrderId());
        }

        // https://developer-docs.amazon.com/sp-api/docs/fulfillment-outbound-api-v2020-07-01-reference#getfulfillmentorder
        try {
            $getFulfillmentOrderResp = $this->client->get(sprintf(
                '/fba/outbound/2020-07-01/fulfillmentOrders/%s',
                $order->getOrderId(),
            ));
        } catch (GuzzleException $e) {
            throw new RuntimeException('GET fulfillmentOrders request error: ' . $e->getMessage() . '. orderID=' . $order->getOrderId());
        }

        if ($getFulfillmentOrderResp->getStatusCode() !== 200) {
            throw new RuntimeException('GET fulfillmentOrders http status error: ' . $getFulfillmentOrderResp->getStatusCode(). '. orderID=' . $order->getOrderId());
        }

        $jsonBody = json_decode((string) $getFulfillmentOrderResp->getBody(), true);
        if ($jsonBody === null) {
            throw new RuntimeException('GET fulfillmentOrders invalid response body=' . $jsonBody. '. orderID=' . $order->getOrderId());
        }

        $trackingNumber = $jsonBody['payload']['fulfillmentShipments'][0]['fulfillmentShipmentPackage'][0]['trackingNumber'] ?? null;

        // Although the 'trackingNumber' is optional in documentation
        // ( https://developer-docs.amazon.com/sp-api/docs/fulfillment-outbound-api-v2020-07-01-reference#fulfillmentshipmentpackage ),
        // ship method logic cannot be without this param,
        // so we throw the exception
        if ($trackingNumber === null) {
            throw new RuntimeException('invalid tracking number: body=' . $getFulfillmentOrderResp->getBody() . ', orderID=' . $order->getOrderId());
        }

        return $trackingNumber;
    }
}
