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
        private CreateFulfillmentOrderBodyBuilder $bodyBuilder,
    ) {
    }

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
            throw new RuntimeException(sprintf(
                'POST fulfillmentOrders orderID=%s, request error: %s',
                $order->getOrderId(),
                $e->getMessage()
            ));
        }

        if ($createFulfillmentOrderResp->getStatusCode() !== 200) {
            throw new RuntimeException(sprintf(
                'POST fulfillmentOrders orderID=%s, http status: %s, body: %s',
                $order->getOrderId(),
                $createFulfillmentOrderResp->getStatusCode(),
                $createFulfillmentOrderResp->getBody()
            ));
        }

        // https://developer-docs.amazon.com/sp-api/docs/fulfillment-outbound-api-v2020-07-01-reference#getfulfillmentorder
        try {
            $getFulfillmentOrderResp = $this->client->get(sprintf(
                '/fba/outbound/2020-07-01/fulfillmentOrders/%s',
                $order->getOrderId(),
            ));
        } catch (GuzzleException $e) {
            throw new RuntimeException(sprintf(
                'GET fulfillmentOrders orderID=%s, request error: %s',
                $order->getOrderId(),
                $e->getMessage()
            ));
        }

        if ($getFulfillmentOrderResp->getStatusCode() !== 200) {
            throw new RuntimeException(sprintf(
                'GET fulfillmentOrders orderID=%s, http status: %s, body: %s',
                $order->getOrderId(),
                $getFulfillmentOrderResp->getStatusCode(),
                $getFulfillmentOrderResp->getBody()
            ));
        }

        $jsonBody = json_decode((string) $getFulfillmentOrderResp->getBody(), true);
        if ($jsonBody === null) {
            throw new RuntimeException(sprintf(
                'GET fulfillmentOrders orderID=%s invalid response body : %s',
                $order->getOrderId(),
                $jsonBody
            ));
        }

        $trackingNumber =
            $jsonBody['payload']['fulfillmentShipments'][0]['fulfillmentShipmentPackage'][0]['trackingNumber']
            ?? null;

        // Although the 'trackingNumber' is optional in documentation
        // ( https://developer-docs.amazon.com/sp-api/docs/fulfillment-outbound-api-v2020-07-01-reference#fulfillmentshipmentpackage ),
        // ship method logic cannot be without this param,
        // so we throw the exception
        if ($trackingNumber === null) {
            throw new RuntimeException(sprintf(
                'invalid tracking number orderID=%s body=%s',
                $order->getOrderId(),
                $getFulfillmentOrderResp->getBody()
            ));
        }

        return $trackingNumber;
    }
}
