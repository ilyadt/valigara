<?php

use App\Fba\CreateFulfillmentOrderBodyBuilder;
use App\Fba\ShippingService;
use App\Fba\ShippingSpeedCategory;

require __DIR__ . '/vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 'on');

function main(): void
{
    $order = new Order(16400);
    $order->load();

    $buyerData = json_decode(file_get_contents(__DIR__ . "/mock/buyer.29664.json"), true);
    $buyer = new Buyer($buyerData);

    // Mock http client
    $mock = new \GuzzleHttp\Handler\MockHandler([
        new \GuzzleHttp\Psr7\Response(200, [], ''),
        new \GuzzleHttp\Psr7\Response(
            200,
            [],
            '{"payload":{"fulfillmentShipments":[{"fulfillmentShipmentPackage":[{"trackingNumber":"777"}]}]}}'
        ),
    ]);
    $handlerStack = \GuzzleHttp\HandlerStack::create($mock);
    $client = new \GuzzleHttp\Client(['handler' => $handlerStack]);

    // Create Amazon's fulfillment network (FBA) service
    $fba = new ShippingService($client, new CreateFulfillmentOrderBodyBuilder(new ShippingSpeedCategory()));

    try {
        $trackingNumber = $fba->ship($order, $buyer);
    } catch (Throwable $e) {
        print(sprintf("Error: %s\n", $e->getMessage()));;
        return;
    }

    print('tracking number is "' . $trackingNumber . "\"\n");
}

////////////////////////////

class Order extends \App\Data\AbstractOrder {
    protected function loadOrderData(int $id): array
    {
        return json_decode(
            file_get_contents(
                __DIR__ . "/mock/order.{$id}.json"),
            true
        );
    }
}

class Buyer implements \App\Data\BuyerInterface {
    private array $data;
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->offsetExists($offset) ? $this->data[$offset] : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }

    public function __get($key) {
        return $this->data[$key];
    }
}

main();
