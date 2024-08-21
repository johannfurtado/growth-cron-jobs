<?php

namespace App\Jobs;

use App\DTO\AuvoCustomerDTO;
use App\Helpers\ValidationHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class UpdateAuvoCustomerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected readonly string $accessToken,
        protected readonly AuvoCustomerDTO $auvoCustomerDTO,
        protected ?Collection $tasksData = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PendingRequest $rawClient): void
    {
        $client = $rawClient->baseUrl(env('AUVO_API_URL'))
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ]);

        try {
            $response = $client->put(
                'customers',
                [
                    $this->auvoCustomerDTO->toArray(),
                ]
            );

            if (!in_array($response->status(), [200, 201])) {
                Log::error("Error updating customer {$this->auvoCustomerDTO->externalId}:  {$response->body()}");
            }

            if ($this->tasksData) {
                $responseId = $response->json()['result']['id'];
                $latitude = ($response->json()['result']['latitude'] ?? -23.558418) ?: -23.558418;
                $longitude = ($response->json()['result']['longitude'] ?? -46.688081) ?: -46.688081;

                $workshopId = $this->auvoCustomerDTO->workshopId ?? 0;
                dispatch(new CreateTasksAuvoJob(
                    $this->tasksData,
                    $this->auvoCustomerDTO,
                    $workshopId,
                    $this->accessToken,
                    $responseId,
                    $latitude,
                    $longitude
                ));
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }


    private function getCustomerCpfCnpj(): ?string
    {
        return ValidationHelper::cpfCnpj($this->auvoCustomerDTO->cpfCnpj) ? $this->auvoCustomerDTO->cpfCnpj : null;
    }

    private function getCustomerEmail(): ?array
    {
        return $this->auvoCustomerDTO->email ? ['email' => $this->auvoCustomerDTO->email] : null;
    }
}
