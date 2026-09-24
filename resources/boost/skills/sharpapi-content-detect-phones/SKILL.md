---
name: sharpapi-content-detect-phones
description: Detect and extract phone numbers from free text, normalised to E.164, with SharpAPI via `SharpAPI\ContentDetectPhones\ContentDetectPhonesService` (sharpapi/laravel-content-detect-phones). Use when finding, parsing or masking phone numbers in user content, moderating contact details, or touching `detectPhones()`, `fetchResults()` or `config/sharpapi-content-detect-phones.php`.
---

# SharpAPI Content Detect Phones

`sharpapi/laravel-content-detect-phones` wraps one SharpAPI endpoint (`POST /content/detect_phones`) to extract phone numbers from free text and normalise them to E.164. The work is async: `detectPhones()` submits a job and returns a status URL, then `fetchResults()` polls until the job finishes.

## When to use this skill

- Pulling phone numbers out of messages, listings, CVs or other free text, in any local format.
- Normalising detected numbers to E.164 for storage or dialling.
- Moderating user content that must not contain contact details.

## Install / wiring checklist

- `composer require sharpapi/laravel-content-detect-phones`. It pulls in `sharpapi/php-core`; this skill assumes php-core ≥ 1.4.1.
- `.env`: `SHARP_API_KEY=...` is required. If it is missing, constructing the service throws `InvalidArgumentException`.
- Optional env keys, shared by every SharpAPI wrapper:
  - `SHARP_API_BASE_URL` (default `https://sharpapi.com/api/v1`)
  - `SHARP_API_JOB_STATUS_POLLING_WAIT` (default `180`): the maximum seconds `fetchResults()` keeps polling.
  - `SHARP_API_JOB_STATUS_POLLING_INTERVAL` (default `10`): seconds between polls when the API sends no `Retry-After`.
  - `SHARP_API_JOB_STATUS_USE_POLLING_INTERVAL` (default `false`): when `true`, the fixed interval above replaces the server's `Retry-After`.
- The config file is optional. To publish it: `php artisan vendor:publish --tag=sharpapi-content-detect-phones` (creates `config/sharpapi-content-detect-phones.php`).
- The service provider is auto-discovered. There is **no facade and no container binding**. Type-hint `ContentDetectPhonesService` (the container builds it) or call `new ContentDetectPhonesService()`. The constructor takes no arguments and reads the config.

## API & config reference

```php
use SharpAPI\ContentDetectPhones\ContentDetectPhonesService;

public function detectPhones(string $text): string
```

- `$text` — the content to scan. The only parameter.

**Returns the status URL (a string), not the result.** Pass it to the inherited `fetchResults(string $statusUrl): SharpAPI\Core\DTO\SharpApiJob`, which blocks while it polls.

`SharpApiJob` has the public properties `id`, `type` (`"content_detect_phones"`), `status` (a string: `"success"` or `"failed"`) and `result` (`?stdClass`). It also has `getResultJson()`, `getResultArray()` (shallow), `getResultObject()` and `toArray()`.

Example `result` on success (shape from the SharpAPI response template; the values are illustrative):

```json
[
    { "detected_number": "1800-394-7486", "parsed_number": "+18003947486" },
    { "detected_number": "+65 8888 8888", "parsed_number": "+6588888888" }
]
```

A list of objects: `detected_number` is the text as found, `parsed_number` is the E.164 form. php-core hands the list over as a `stdClass` with numeric keys, so decode it as shown below.

Exceptions:
- `SharpAPI\Core\Exceptions\ApiException`: polling ran past `SHARP_API_JOB_STATUS_POLLING_WAIT`, or HTTP 429 retries ran out.
- `GuzzleHttp\Exception\ClientException` (4xx, e.g. 401 bad key, 422 validation) and other `GuzzleHttp\Exception\GuzzleException`s for transport or 5xx errors.

## Recipes

### Queued job (the default pattern)

```php
namespace App\Jobs;

use App\Models\Message;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable; // Laravel 10: Dispatchable, InteractsWithQueue, Queueable, SerializesModels
use Illuminate\Support\Facades\Log;
use SharpAPI\Core\Enums\SharpApiJobStatusEnum;
use SharpAPI\Core\Exceptions\ApiException;
use SharpAPI\ContentDetectPhones\ContentDetectPhonesService;

class ScanMessageForPhones implements ShouldQueue
{
    use Queueable;

    public int $timeout = 240; // must exceed SHARP_API_JOB_STATUS_POLLING_WAIT (180)

    public int $tries = 1;     // every retry re-submits the text and is billed again

    public function __construct(public Message $message) {}

    public function handle(ContentDetectPhonesService $service): void
    {
        try {
            $statusUrl = $service->detectPhones($this->message->body);
            $job = $service->fetchResults($statusUrl); // blocks and polls; no loop needed
        } catch (ApiException|GuzzleException $e) {
            Log::warning('SharpAPI detectPhones failed: '.$e->getMessage());

            return;
        }

        if ($job->status !== SharpApiJobStatusEnum::SUCCESS->value) {
            Log::warning('SharpAPI detectPhones job did not succeed', $job->toArray());

            return;
        }

        $phones = array_values(json_decode($job->getResultJson(), true) ?? []);
        // $phones[0]['parsed_number'] === '+18003947486'
        $this->message->update(['phone_numbers' => array_column($phones, 'parsed_number')]);
    }
}
```

Resolve the service in `handle()`, as above, and never store it on a job property. It holds a Guzzle client, which cannot be serialized onto the queue.

## Gotchas

php-core is a transitive dependency, so these rules are repeated here:

1. **`fetchResults()` already polls.** It sleeps between polls (honouring `Retry-After` and rate-limit headers) until the job succeeds, fails or `SHARP_API_JOB_STATUS_POLLING_WAIT` runs out. Never write your own `while ($status === 'pending')` loop, and never call `detectPhones()` again to "retry": each call is a new billed job.
2. **A failed job does not throw.** Always compare `$job->status` with `SharpApiJobStatusEnum::SUCCESS->value` (`SharpAPI\Core\Enums\SharpApiJobStatusEnum`). On failure `result` can be an empty `stdClass`, so reading `$job->result->field` without `?? null` raises an "Undefined property" `ErrorException` in Laravel.
3. **Never call `fetchResults()` inside an HTTP request.** It can block for up to 180 s. Use a queued job whose `$timeout` exceeds the polling wait, keep `$tries` low, and make the worker/Horizon supervisor `timeout` at least the job timeout, with the queue connection's `retry_after` above it. Artisan commands are fine to run inline.
4. **For arrays, decode the JSON:** `json_decode($job->getResultJson(), true)`. `getResultArray()` only converts the top level, so nested objects stay `stdClass`, and list results arrive as objects with numeric keys. This package returns a list, so always decode it this way.

## Testing

- Mock the service. It must reach your code through the container (constructor/`handle()` injection or `app(ContentDetectPhonesService::class)`); `new ContentDetectPhonesService()` bypasses the mock.

```php
use SharpAPI\Core\DTO\SharpApiJob;
use SharpAPI\ContentDetectPhones\ContentDetectPhonesService;

$this->mock(ContentDetectPhonesService::class, function ($mock) {
    $mock->shouldReceive('detectPhones')->once()->andReturn('https://sharpapi.com/api/v1/job/status/fake-id');
    $mock->shouldReceive('fetchResults')->once()->andReturn(new SharpApiJob(
        id: 'fake-id',
        type: 'content_detect_phones',
        status: 'success',
        result: (object) [['detected_number' => '555 0100', 'parsed_number' => '+15550100']],
    ));
});
```

- Test the failure path too: return `status: 'failed'` with `result: new \stdClass`.
- `Http::fake()` does **not** intercept these calls, because php-core sends them through its own Guzzle client. Mock the service instead. Without a mock, a test with no `SHARP_API_KEY` throws `InvalidArgumentException` as soon as the service is built.
