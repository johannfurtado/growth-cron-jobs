<?php

namespace App\Jobs;

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
        protected $customer,
        protected readonly ?string $prefixExternalId,
        protected Collection $colaboradores // Alterado para Collection
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PendingRequest $client): void
    {
        $client = $client->baseUrl(env('AUVO_API_URL'))
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ]);

        try {
            $response = $client->put(
                'customers',
                [
                    'externalId' => (string) "{$this->prefixExternalId}{$this->customer->id}",
                    'description' => $this->customer->name,
                    'name' => "{$this->prefixExternalId}{$this->customer->name}",
                    'address' => $this->customer->address,
                    'manager' => 'thais santos',
                    'note' => $this->customer->note,
                    "active" => true,
                    // "cpfCnpj" => $this->getCustomerCpfCnpj(),
                    // "email" => $this->getCustomerEmail(),
                    // "phoneNumber" => $this->getCustomerPhoneNumber(),
                ]
            );

            if (!in_array($response->status(), [200, 201])) {
                Log::error("Error updating customer {$this->customer->id}:  {$response->body()}");
            }

            $responseId = $response->json()['result']['id'];
            $latitude = ($response->json()['result']['latitude'] ?? -23.558418) ?: -23.558418;
            $longitude = ($response->json()['result']['longitude'] ?? -46.688081) ?: -46.688081;

            Log::info($responseId);
            Log::info($latitude);
            Log::info($longitude);


            $idOficina = $this->customer->id_oficina ?? 0;
            dispatch(new CreateTasksAuvoJob($this->colaboradores, $this->customer, $idOficina, $this->accessToken, $responseId, $latitude, $longitude));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }


    private function getCustomerCpfCnpj(): ?string
    {
        return ValidationHelper::cpfCnpj($this->customer->cpfCnpj) ? $this->customer->cpfCnpj : null;
    }

    private function getCustomerEmail(): ?array
    {
        return $this->customer->email ? ['email' => $this->customer->email] : null;
    }

    private function getCustomerPhoneNumber(): ?array
    {
        return $this->customer->phone ? ['phoneNumber' => $this->customer->phone] : null;
    }
}
