<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\Http\Transport\Guzzle;

use GuzzleHttp\ClientInterface;
use TG\Infrastructure\Http\Transport\Guzzle\Guzzle;

class GuzzleWithPublicOptions extends Guzzle
{
    private $guzzle;

    public function __construct(Guzzle $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    protected function client(): ClientInterface
    {
        return $this->guzzle->client();
    }

    public function options(): array
    {
        return $this->guzzle->options();
    }
}