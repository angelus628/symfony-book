<?php

declare(strict_types=1);

namespace App\Contracts;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Interface HttpClientAwareInterface
 * @package App\Contracts
 */
interface HttpClientAwareInterface
{
    public function setClient(HttpClientInterface $httpClient): void;
}
