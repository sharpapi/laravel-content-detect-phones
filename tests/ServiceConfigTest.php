<?php

declare(strict_types=1);

use SharpAPI\ContentDetectPhones\ContentDetectPhonesService;

beforeEach(function () {
    config()->set('sharpapi-content-detect-phones.api_key', 'test-key');
});

it('keeps the server Retry-After polling by default', function () {
    expect((new ContentDetectPhonesService)->isUseCustomInterval())->toBeFalse();
});

it('applies the use-polling-interval flag from config', function () {
    config()->set('sharpapi-content-detect-phones.api_job_status_use_polling_interval', true);
    config()->set('sharpapi-content-detect-phones.api_job_status_polling_interval', 7);

    $service = new ContentDetectPhonesService;

    expect($service->isUseCustomInterval())->toBeTrue()
        ->and($service->getApiJobStatusPollingInterval())->toBe(7);
});

it('reads the polling wait from config', function () {
    config()->set('sharpapi-content-detect-phones.api_job_status_polling_wait', 60);

    expect((new ContentDetectPhonesService)->getApiJobStatusPollingWait())->toBe(60);
});
