<?php

namespace App\DTO;

class AuvoCustomerDTO
{
    public function __construct(
        public readonly string $externalId,
        public readonly string $description,
        public readonly string $name,
        public readonly string $address,
        public readonly string $manager,
        public readonly string $note,
        public readonly ?string $cpfCnpj = null,
        public readonly ?string $email = null,
        public readonly ?string $phoneNumber = null,
        public readonly bool $active = true,
    ) {}

    public function toArray(): array
    {
        return [
            'externalId' => $this->externalId,
            'description' => $this->description,
            'name' => $this->name,
            'address' => $this->address,
            'manager' => $this->manager,
            'note' => $this->note,
            'active' => $this->active,
        ];
    }
}
