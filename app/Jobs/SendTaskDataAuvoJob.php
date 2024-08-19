<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Task;

class SendTaskDataAuvoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected array $taskData,
        protected $customer,
        protected string $pdfPath,
        protected string $accessToken
    ) {}

    public function handle(): void
    {
        $client = $this->configureHttpClient();

        try {
            $response = $client->put('tasks', $this->taskData);

            if (in_array($response->status(), [200, 201])) {
                $this->processSuccessfulResponse($response);
            } else {
                Log::error("Error creating task for customer {$this->customer->id}: {$response->body()}");
            }
        } catch (\Exception $e) {
            Log::error("Exception creating task for customer {$this->customer->id}: " . $e->getMessage());
        }
    }

    private function configureHttpClient()
    {
        return Http::baseUrl(env('AUVO_API_URL'))
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->retry(3, 100);
    }

    private function processSuccessfulResponse($response)
    {
        $responseData = $response->json();
        if (isset($responseData['result']['taskID'])) {
            // Log::info("Task ID: {$responseData['result']['taskID']}");
            Task::create([
                'auvo_id_task' => $responseData['result']['taskID'],
            ]);
        } else {
            Log::error("Task ID not found in response: " . json_encode($responseData));
        }
    }
}
