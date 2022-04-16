<?php

declare(strict_types=1);

namespace App\Contracts;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Trait HttpClientAwareTrait
 * @package App\Contracts
 */
trait HttpClientAwareTrait
{
    private HttpClientInterface $httpClient;

    public function setClient(HttpClientInterface $httpClient): void
    {
        $this->httpClient = $httpClient;
    }
}
