<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UpdateFieldControlCustomerExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected $customer
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(PendingRequest $client): void
    {
        $client = $client->withHeaders([
            "Content-Type" => "application/json",
            "X-Api-Key" => env('FIELD_CONTROL_API_KEY')
        ]);

        try {
            $response = $client->get('https://carchost.fieldcontrol.com.br/customers?q=code:"' . $this->customer->code . '"');


            if (!in_array($response->status(), [200, 201])) {
                Log::error("Error fetching customer {$this->customer->name}:  {$response->body()}");
            }

            $items = $response->json()['items'];
            $customerId = null;
            foreach ($items as $item) {
                if ($item['code'] == $this->customer->code && $item['archived'] == false) {
                    $customerId = $item['id'];
                    continue;
                }
            }
            if (!$customerId) {
                Log::error("Customer not found: {$this->customer->name}");
                return;
            }

            sleep(1);

            try {
                $response = $client->put(
                    'https://carchost.fieldcontrol.com.br/customers/' . $customerId,
                    [
                        'name' => $this->customer->name,
                        'documentNumber' => $this->customer->cpf,
                        'code' => "87",
                        'external' => [
                            'id' => "87"
                        ],
                        'address' => [
                            'zipCode' => Str::remove('-', $this->customer->cep,),
                            'street' => $this->customer->logradouro,
                            'number' => $this->customer->numero ?? 'S/N',
                            'neighborhood' => $this->customer->bairro,
                            'complement' => $this->customer->complemento,
                            'city' => $this->customer->cidade,
                            'state' => $this->customer->uf,
                            'coords' => [
                                "latitude" => -23.558418,
                                "longitude" => -46.688081
                            ]
                        ]

                    ]
                );


                if (!in_array($response->status(), [200, 201])) {
                    Log::error("Error updating customer {$this->customer->name}:  {$response->body()}");
                }

                Log::info($response);
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
