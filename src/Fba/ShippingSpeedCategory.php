<?php

declare(strict_types=1);

namespace App\Fba;

use RuntimeException;

class ShippingSpeedCategory
{
    /**
     * @throws RuntimeException
     */
    public function getByTypeId(int $shippingTypeId): string
    {
        // https://developer-docs.amazon.com/sp-api/docs/fulfillment-outbound-api-v2020-07-01-reference#shippingspeedcategory
        return match ($shippingTypeId) {
            1 => 'Standard',
            2 => 'Expedited',
            3 => 'Priority',
            7 => 'ScheduledDelivery',
            default => throw new RuntimeException('unknown ShippingSpeedCategory ' . $shippingTypeId),
        };
    }
}
