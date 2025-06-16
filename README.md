![SharpAPI GitHub cover](https://sharpapi.com/sharpapi-github-laravel-bg.jpg "SharpAPI Laravel Client")

# AI Phone Number Detection for Laravel

## 🚀 Leverage AI API to detect and parse phone numbers in text content.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sharpapi/laravel-content-detect-phones.svg?style=flat-square)](https://packagist.org/packages/sharpapi/laravel-content-detect-phones)
[![Total Downloads](https://img.shields.io/packagist/dt/sharpapi/laravel-content-detect-phones.svg?style=flat-square)](https://packagist.org/packages/sharpapi/laravel-content-detect-phones)

Check the details at SharpAPI's [Content API](https://sharpapi.com/en/catalog/ai/content) page.

---

## Requirements

- PHP >= 8.1
- Laravel >= 9.0

---

## Installation

Follow these steps to install and set up the SharpAPI Laravel Phone Number Detection package.

1. Install the package via `composer`:

```bash
composer require sharpapi/laravel-content-detect-phones
```

2. Register at [SharpAPI.com](https://sharpapi.com/) to obtain your API key.

3. Set the API key in your `.env` file:

```bash
SHARP_API_KEY=your_api_key_here
```

4. **[OPTIONAL]** Publish the configuration file:

```bash
php artisan vendor:publish --tag=sharpapi-content-detect-phones
```

---
## Key Features

- **AI-Powered Phone Number Detection**: Efficiently detect phone numbers in any text content.
- **E.164 Format Conversion**: Automatically converts detected phone numbers to standardized E.164 format.
- **Multiple Number Detection**: Identifies all phone numbers present in the provided text.
- **International Number Support**: Recognizes phone numbers from different countries and formats.
- **Robust Polling for Results**: Polling-based API response handling with customizable intervals.
- **API Availability and Quota Check**: Check API availability and current usage quotas with SharpAPI's endpoints.

---

## Usage

You can inject the `ContentDetectPhonesService` class to access phone number detection functionality. For best results, especially with batch processing, use Laravel's queuing system to optimize job dispatch and result polling.

### Basic Workflow

1. **Dispatch Job**: Send text content to the API using `detectPhones`, which returns a status URL.
2. **Poll for Results**: Use `fetchResults($statusUrl)` to poll until the job completes or fails.
3. **Process Result**: After completion, retrieve the results from the `SharpApiJob` object returned.

> **Note**: Each job typically takes a few seconds to complete. Once completed successfully, the status will update to `success`, and you can process the results as JSON, array, or object format.

---

### Controller Example

Here is an example of how to use `ContentDetectPhonesService` within a Laravel controller:

```php
<?php

namespace App\Http\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use SharpAPI\ContentDetectPhones\ContentDetectPhonesService;

class ContentController extends Controller
{
    protected ContentDetectPhonesService $phoneDetectionService;

    public function __construct(ContentDetectPhonesService $phoneDetectionService)
    {
        $this->phoneDetectionService = $phoneDetectionService;
    }

    /**
     * @throws GuzzleException
     */
    public function detectPhoneNumbers(string $text)
    {
        $statusUrl = $this->phoneDetectionService->detectPhones($text);
        
        $result = $this->phoneDetectionService->fetchResults($statusUrl);

        return response()->json($result->getResultJson());
    }
}
```

### Handling Guzzle Exceptions

All requests are managed by Guzzle, so it's helpful to be familiar with [Guzzle Exceptions](https://docs.guzzlephp.org/en/stable/quickstart.html#exceptions).

Example:

```php
use GuzzleHttp\Exception\ClientException;

try {
    $statusUrl = $this->phoneDetectionService->detectPhones('Contact us at +1 (800) 555-1234 or 555-6789');
} catch (ClientException $e) {
    echo $e->getMessage();
}
```

---

## Optional Configuration

You can customize the configuration by setting the following environment variables in your `.env` file:

```bash
SHARP_API_KEY=your_api_key_here
SHARP_API_JOB_STATUS_POLLING_WAIT=180
SHARP_API_JOB_STATUS_USE_POLLING_INTERVAL=true
SHARP_API_JOB_STATUS_POLLING_INTERVAL=10
SHARP_API_BASE_URL=https://sharpapi.com/api/v1
```

---

## Phone Number Detection Data Format Example

```json
{
  "data": {
    "type": "api_job_result",
    "id": "d43b36dc-3d1d-4ba7-9a17-36a438d91f09",
    "attributes": {
      "status": "success",
      "type": "content_detect_phones",
      "result": [
        {
          "parsed_number": "+18003947486",
          "detected_number": "1800-394-7486"
        },
        {
          "parsed_number": "+6588888888",
          "detected_number": "+65 8888 8888"
        }
      ]
    }
  }
}
```

---

## Support & Feedback

For issues or suggestions, please:

- [Open an issue on GitHub](https://github.com/sharpapi/laravel-content-detect-phones/issues)
- Join our [Telegram community](https://t.me/sharpapi_community)

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for a detailed list of changes.

---

## Credits

- [A2Z WEB LTD](https://github.com/a2zwebltd)
- [Dawid Makowski](https://github.com/makowskid)
- Enhance your [Laravel AI](https://sharpapi.com/) capabilities!

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

## Follow Us

Stay updated with news, tutorials, and case studies:

- [SharpAPI on X (Twitter)](https://x.com/SharpAPI)
- [SharpAPI on YouTube](https://www.youtube.com/@SharpAPI)
- [SharpAPI on Vimeo](https://vimeo.com/SharpAPI)
- [SharpAPI on LinkedIn](https://www.linkedin.com/products/a2z-web-ltd-sharpapicom-automate-with-aipowered-api/)
- [SharpAPI on Facebook](https://www.facebook.com/profile.php?id=61554115896974)