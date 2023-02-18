<?php

declare(strict_types=1);

namespace App\Fba;

use App\Data\AbstractOrder;
use App\Data\BuyerInterface;

class CreateFulfillmentOrderBodyBuilder
{
    public function __construct(
        private ShippingSpeedCategory $shippingSpeedCategory,
    ) {
    }

    /**
     * @throws \RuntimeException
     */
    public function build(AbstractOrder $order, BuyerInterface $buyer): array
    {
        $destAddress = new AddressParser($order->data['shipping_adress']);

        $items = [];
        foreach ($order->data['products'] as $product) {
            $items[] = [
                'sellerSku' => $product['sku'],
                'sellerFulfillmentOrderItemId' => $product['sku'],
                'quantity' => (int) $product['ammount'],
            ];
        }

        return [
            'sellerFulfillmentOrderId' => $order->data['order_unique'],
            'displayableOrderId' => (string) $order->getOrderId(),
            'displayableOrderDate' => $order->data['order_date'],
            'displayableOrderComment' => $order->data['comments'],
            'shippingSpeedCategory' => $this->shippingSpeedCategory->getByTypeId((int) $order->data['shipping_type_id']),
            'destinationAddress' => [
                'name' => $destAddress->getName(),
                'addressLine1' => $destAddress->getAddressLine1(),
                'city' => $destAddress->getCity(),
                'districtOrCounty' => $destAddress->getCountry(),
                'stateOrRegion' => $destAddress->getState(),
                'postalCode' => $destAddress->getPostalCode(),
                'countryCode' => $order->data['shipping_country'],
                'phone' => $buyer->phone,
            ],
            'fulfillmentAction' => 'Ship',
            'notificationEmails' => [
                $buyer->email,
            ],
            'items' => $items,
        ];
    }
}
