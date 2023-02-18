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
        $line5 = explode(' ', $parts[4]);

        $this->postalCode = $line5[0];
        $this->country = implode(' ', array_slice($line5, 1, count($line5)));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddressLine1(): string
    {
        return $this->addressLine1;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCountry(): string
    {
        return $this->country;
    }
}
