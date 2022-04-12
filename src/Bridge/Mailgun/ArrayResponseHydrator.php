<?php

namespace App\Bridge\Mailgun;

use Mailgun\Exception\HydrationException;
use Mailgun\Hydrator\Hydrator;
use Psr\Http\Message\ResponseInterface;

final class ArrayResponseHydrator implements Hydrator
{
    public function hydrate(ResponseInterface $response, string $class)
    {
        $body = $response->getBody()->__toString();
        if (0 !== strpos($response->getHeaderLine('Content-Type'), 'application/json')) {
            throw new HydrationException('The ArrayHydrator cannot hydrate response with Content-Type:' . $response->getHeaderLine('Content-Type'));
        }

        $content = json_decode($body, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new HydrationException(sprintf('Error (%d) when trying to json_decode response', json_last_error()));
        }

        return new ArrayResponse($content);
    }
}
