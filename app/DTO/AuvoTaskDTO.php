<?php

namespace App\DTO;

final class AuvoTaskDTO
{

    const LATITUDE = -23.558418;
    const LONGITUDE = -46.688081;

    public function __construct(
        public readonly ?string $externalId = null,
        public readonly ?int $taskType = 153103,
        public readonly int $idUserFrom = 163489,
        public readonly ?int $idUserTo = null,
        public readonly ?string $taskDate = null,
        public readonly string $address,
        public readonly string $orientation,
        public readonly int $priority = 3,
        public readonly ?int $questionnaireId = 173499,
        public readonly ?int $customerId = null,
        public readonly ?int $checkinType = 1,
        public readonly ?bool $sendSatisfactionSurvey = null,
        public ?array $attachments = null,
        public readonly ?array $keyWords = null,
    ) {}

    public function toArray(): array
    {
        return [
            'externalId' => $this->externalId,
            'taskType' => $this->taskType,
            'idUserFrom' => $this->idUserFrom,
            'idUserTo' => $this->idUserTo,
            'taskDate' => $this->taskDate,
            'latitude' => self::LATITUDE,
            'longitude' => self::LONGITUDE,
            'address' => $this->address,
            'orientation' => $this->orientation,
            'priority' => $this->priority,
            'questionnaireId' => $this->questionnaireId,
            'customerId' => $this->customerId,
            'checkinType' => $this->checkinType,
            'sendSatisfactionSurvey' => $this->sendSatisfactionSurvey,
            'attachments' => $this->attachments,
            'keyWords' => $this->keyWords,
        ];
    }
}
