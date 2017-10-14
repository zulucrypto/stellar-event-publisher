<?php


namespace AppBundle\Command;


use AppBundle\Entity\OutgoingWebhook;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeliverWebhooksCommand extends BaseEventPublisherCommand
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var int
     */
    protected $maxNumRetries;

    protected function configure()
    {
        // todo: configurable
        $this->maxNumRetries = 5;

        parent::configure();

        $this
            ->setName('sep:deliver-webhooks')
            ->setDescription("This command monitors the database for new webhook entries and delivers them")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->applyVerbosityFromEnvironment($output);

        $this->logger = $this->getContainer()->get('monolog.logger.deliver_webhooks');
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $this->em->getRepository('AppBundle:OutgoingWebhook');

        $this->logger->notice('Delivery process started');

        while (true) {
            $toDeliver = $repo->findAwaitingDelivery($this->maxNumRetries);
            $this->logger->debug('Found ' . count($toDeliver) . ' messgaes to deliver');

            foreach ($toDeliver as $outgoing) {
                try {
                    $this->deliver($outgoing);
                }
                catch (\Exception $e) {
                    $this->logger->critical('Unhandled exception when delivering: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                }

                $this->em->persist($outgoing);
                $this->em->flush($outgoing);
            }

            sleep(1);
        }
    }

    protected function deliver(OutgoingWebhook $outgoing)
    {
        $httpClient = new Client([]);
        $outgoing->setLastAttemptedAt(new \DateTime());

        /*
         * Eventually the payload will be optimized for batching requests, but
         * for now only send one. use an array for events to maintain forward
         * compatibility
         *
         * source: 'ledgers',
         * events: [
         *  { payload 1 },
         *  { payload 2},
         * ]
         */
        $postPayload = [
            'source' => $outgoing->getSourceType(),
            'events' => [
                $outgoing->getPayload(),
            ]
        ];

        try {
            $response = $httpClient->post(
                $outgoing->getDestination(),
                [
                    'json' => $postPayload,
                ]
            );
        }
        // Failed due to an exception
        catch (\Exception $e) {
            $this->markDeliveryFailed($outgoing, sprintf('Exception: %s', $e->getMessage()));
            return;
        }

        // Success!
        if (200 == $response->getStatusCode()) {
            $this->markDeliverySuccessful($outgoing);
            $this->logger->info(sprintf("delivered %s event(s) to %s\n", count($postPayload['events']), $outgoing->getDestination()));
        }
        // Failed due to an unexpected status code
        else {
            $this->markDeliveryFailed($outgoing, sprintf('HTTP error %s: %s', $response->getStatusCode(), $response->getBody()));
        }
    }

    /**
     * @param OutgoingWebhook $outgoing
     * @param null            $message
     */
    protected function markDeliveryFailed(OutgoingWebhook $outgoing, $message = null)
    {
        $outgoing->setNumFailures($outgoing->getNumFailures() + 1);
        $outgoing->setLastFailureReason($message);

        // Delivery failed
        if ($outgoing->getNumFailures() >= $this->maxNumRetries) {
            $this->logger->error(sprintf('Failed to deliver to %s (%s). Delivery will not be retried.',
                $outgoing->getDestination(),
                $message
            ));
        }
        // Delivery will be retried
        else {
            $this->logger->warning(sprintf('Failed to deliver to %s (%s). Delivery has failed %s time(s)',
                $outgoing->getDestination(),
                $message,
                $outgoing->getNumFailures()
            ));
        }
    }

    /**
     * @param OutgoingWebhook $outgoing
     */
    protected function markDeliverySuccessful(OutgoingWebhook $outgoing)
    {
        $outgoing->setDeliveredAt(new \DateTime());
    }
}