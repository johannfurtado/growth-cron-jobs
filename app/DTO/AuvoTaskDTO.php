<?php

namespace App\DTO;

final class AuvoTaskDTO
{
    public function __construct(
        public readonly ?string $externalId = null,
        public readonly ?int $taskType = null,
        public readonly int $idUserFrom = 163489,
        public readonly ?int $idUserTo = null,
        public readonly ?int $teamId = null,
        public readonly ?string $taskDate = null,
        public readonly float $latitude = -23.558418,
        public readonly float $longitude = -46.688081,
        public readonly string $address,
        public readonly string $orientation,
        public readonly int $priority,
        public readonly ?int $questionnaireId = null,
        public readonly ?int $customerId = null,
        public readonly ?int $checkinType = null,
        public readonly ?bool $sendSatisfactionSurvey = null,
        public readonly ?array $attachments = null,
        public readonly ?array $keyWords = null,
    ) {}

    public function toArray(): array
    {
        return [
            'externalId' => $this->externalId,
            'taskType' => $this->taskType,
            'idUserFrom' => $this->idUserFrom,
            'idUserTo' => $this->idUserTo,
            'teamId' => $this->teamId,
            'taskDate' => $this->taskDate,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
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
