<?php

namespace App\Bridge\Mailgun;

use Mailgun\Model\PagingProvider;

/**
 * ArrayHydrator dont support pagination, this is a hacky way to have pagination for events.
 */
class ArrayResponse implements PagingProvider
{
    private array $response;

    public function __construct($response)
    {
        $this->response = $response;
    }

    public function getItems(): array
    {
        return $this->response['items'];
    }

    public function getNextUrl(): ?string
    {
        return $this->response['paging']['next'];
    }

    public function getPreviousUrl(): ?string
    {
        return $this->response['paging']['previous'];
    }

    public function getFirstUrl(): ?string
    {
        return $this->response['paging']['first'];
    }

    public function getLastUrl(): ?string
    {
        return $this->response['paging']['last'];
    }
}
