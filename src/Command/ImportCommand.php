<?php

namespace App\Command;

use App\Bridge\Mailgun\ArrayResponse;
use Mailgun\Mailgun;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportCommand extends Command
{
    protected static $defaultName = 'app:import';

    private Mailgun $mailgun;

    private string $mailgunDomain;

    private LoggerInterface $logger;

    private static array $logLevelMapping = [
        'info' => Logger::INFO,
        'warn' => Logger::WARNING,
        'error' => Logger::ERROR,
    ];

    public function __construct(Mailgun $mailgun, string $mailgunDomain, LoggerInterface $mailgunLogger)
    {
        parent::__construct();

        $this->mailgun = $mailgun;
        $this->mailgunDomain = $mailgunDomain;
        $this->logger = $mailgunLogger;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Import Mailgun logs to Graylog')
            ->addOption('date', null, InputOption::VALUE_REQUIRED, 'Specific day to import', 'yesterday')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Specific number of events to import')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import Mailgun logs to Graylog');

        /** @var string $dateValue */
        $dateValue = $input->getOption('date');
        $date = new \DateTimeImmutable($dateValue);
        $io->note(sprintf('Date: %s', $date->format('Y-m-d')));

        $limit = (int) $input->getOption('limit');
        if (0 !== $limit) {
            $io->note(sprintf('Limit: %d', $limit));
        }

        if (false === $io->confirm('Do you want to continue?')) {
            return Command::SUCCESS;
        }

        /** @var ArrayResponse $eventsResponse */
        $eventsResponse = $this->mailgun->events()->get($this->mailgunDomain, [
            'begin' => $date->setTime(0, 0, 0)->getTimestamp(),
            'end' => $date->setTime(23, 59, 59)->getTimestamp(),
        ]);
        $events = $eventsResponse->getItems();

        $count = 0;

        while (!empty($events)) {
            foreach ($events as $event) {
                $this->logEvent($event);
                ++$count;

                if ($count === $limit) {
                    return self::SUCCESS;
                }
            }

            /** @var ArrayResponse $eventsResponse */
            $eventsResponse = $this->mailgun->events()->nextPage($eventsResponse);
            $events = $eventsResponse->getItems();
        }

        return self::SUCCESS;
    }

    /**
     * Flatten the event data and convert keys to snake case.
     */
    private static function transformEventData(array $array, string $prepend = ''): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value) && !self::hasOnlyNumericKeys($value)) {
                $results = array_merge($results, self::transformEventData($value, $prepend . $key . '_'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    private function logEvent(array $event): void
    {
        if (true === in_array($event['event'], ['opened', 'clicked', 'unsubscribed'], true)) {
            return;
        }

        $logContext = self::transformEventData($event);

        $logLevel = $event['log-level'] ?? 'info';

        $this->logger->log(
            self::$logLevelMapping[$logLevel],
            sprintf('Mailgun "%s" event', $event['event']),
            $logContext
        );
    }

    private static function hasOnlyNumericKeys(array $array): bool
    {
        return false === empty(array_filter($array, 'is_numeric', ARRAY_FILTER_USE_KEY));
    }
}
