<?php

declare(strict_types=1);

namespace App\Fba;

class AddressParser
{
    private string $name;
    private string $addressLine1;
    private string $city;

    private string $state;
    private string $postalCode;
    private string $country;

    public function __construct(string $addressString)
    {
        // TODO: validation
        $parts = explode("\n", $addressString);

        $this->name = $parts[0];
        $this->addressLine1 = $parts[1];
        $this->city = $parts[2];
        $this->state = $parts[3];

        // TODO: validation
        [$this->postalCode, $this->country] = explode(' ', $parts[4]);

    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getAddressLine1(): string
    {
        return $this->addressLine1;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @return string
     */
    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }
}