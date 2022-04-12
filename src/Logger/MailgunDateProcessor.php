<?php

namespace App\Logger;

use Monolog\Processor\ProcessorInterface;

class MailgunDateProcessor implements ProcessorInterface
{
    public function __invoke(array $record)
    {
        $context = $record['context'];
        if (false === array_key_exists('event_date', $context)) {
            return $record;
        }

        $record['datetime'] = $context['event_date'];

        return $record;
    }
}
