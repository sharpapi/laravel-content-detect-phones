<?php

declare(strict_types=1);

namespace SharpAPI\ContentDetectPhones;

use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use SharpAPI\Core\Client\SharpApiClient;

/**
 * @api
 */
class ContentDetectPhonesService extends SharpApiClient
{
    /**
     * Initializes a new instance of the class.
     *
     * @throws InvalidArgumentException if the API key is empty.
     */
    public function __construct()
    {
        parent::__construct(config('sharpapi-content-detect-phones.api_key'));
        $this->setApiBaseUrl(
            config(
                'sharpapi-content-detect-phones.base_url',
                'https://sharpapi.com/api/v1'
            )
        );
        $this->setApiJobStatusPollingInterval(
            (int) config(
                'sharpapi-content-detect-phones.api_job_status_polling_interval',
                5)
        );
        $this->setApiJobStatusPollingWait(
            (int) config(
                'sharpapi-content-detect-phones.api_job_status_polling_wait',
                180)
        );
        $this->setUserAgent('SharpAPILaravelContentDetectPhones/1.0.0');
    }

    /**
     * Parses the provided text for any phone numbers and returns the original detected version and its E.164 format.
     * Might come in handy in the case of processing and validating big chunks of data against phone numbers
     * or f.e. if you want to detect phone numbers in places where they're not supposed to be.
     *
     * @throws GuzzleException
     *
     * @api
     */
    public function detectPhones(string $text): string
    {
        $response = $this->makeRequest(
            'POST',
            '/content/detect_phones',
            ['content' => $text]
        );

        return $this->parseStatusUrl($response);
    }
}