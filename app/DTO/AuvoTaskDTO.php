<?php

namespace App\DTO;

// "{
//     \"externalId\": \"123\",
//     \"taskType\": 1,
//     \"idUserFrom\": 99,
//     \"idUserTo\": 69,
//     \"teamId\": 6,
//     \"taskDate\": \"2016-04-23T18:00:00\",
//     \"latitude\": -16.6872086111083,
//     \"longitude\": -49.2995542287827,
//     \"address\": \"avenue Y\",
//     \"orientation\": \"Gotta Catch \'Em All\",
//     \"priority\": 1,
//     \"questionnaireId\": 3,
//     \"customerId\": 1,
//     \"checkinType\": 1,
//     \"sendSatisfactionSurvey\": false,
//     \"attachments\": [
//         {
//             \"name\": \"my_file.pdf\",
//             \"file\": \"base64 encoded file\"
//         }
//     ],
//     \"keyWords\": [
//         1
//     ]
// }"

final class AuvoTaskDTO
{
    public function __construct(
        public readonly string $externalId,
        public readonly int $taskType,
        public readonly int $idUserFrom,
        public readonly int $idUserTo,
        public readonly int $teamId,
        public readonly string $taskDate,
        public readonly float $latitude = -23.558418,
        public readonly float $longitude = -46.688081,
        public readonly string $address,
        public readonly string $orientation,
        public readonly int $priority,
        public readonly int $questionnaireId,
        public readonly int $customerId,
        public readonly int $checkinType,
        public readonly bool $sendSatisfactionSurvey,
        public readonly array $attachments,
        public readonly array $keyWords,
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
