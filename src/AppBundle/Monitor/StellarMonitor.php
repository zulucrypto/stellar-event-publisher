<?php


namespace AppBundle\Monitor;


use AppBundle\Config\DeliveryDestination;
use AppBundle\Config\MonitorConfig;
use AppBundle\Entity\OutgoingWebhook;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use ZuluCrypto\StellarSdk\Horizon\ApiClient;
use ZuluCrypto\StellarSdk\Model\Effect;
use ZuluCrypto\StellarSdk\Model\Ledger;
use ZuluCrypto\StellarSdk\Model\Operation;
use ZuluCrypto\StellarSdk\Model\Payment;
use ZuluCrypto\StellarSdk\Model\Transaction;

class StellarMonitor
{
    /**
     * @var ApiClient
     */
    protected $apiClient;

    /**
     * @var MonitorConfig
     */
    protected $config;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * If true, the monitor should be processing events
     *
     * @var bool
     */
    protected $isEnabled;

    /**
     * Whether or not monitoring has been started
     *
     * @var bool
     */
    protected $isStarted;

    public function __construct(
        ApiClient $apiClient,
        MonitorConfig $config,
        EntityManagerInterface $em,
        Logger $logger
    ) {
        $this->apiClient = $apiClient;
        $this->config = $config;
        $this->em = $em;
        $this->logger = $logger;

        $this->isEnabled = true;
        $this->isStarted = false;
    }

    public function start()
    {
        if ($this->isStarted) return;

        if ($this->config->hasDestinationForSource(DeliveryDestination::SOURCE_EFFECTS)) {
            $this->logger->notice('Monitoring Effects');
            $this->startEffectMonitor();
        }
        if ($this->config->hasDestinationForSource(DeliveryDestination::SOURCE_LEDGERS)) {
            $this->logger->notice('Monitoring Ledgers');
            $this->startLedgerMonitor();
        }
        if ($this->config->hasDestinationForSource(DeliveryDestination::SOURCE_OPERATIONS)) {
            $this->logger->notice('Monitoring Operations');
            $this->startOperationMonitor();
        }
        if ($this->config->hasDestinationForSource(DeliveryDestination::SOURCE_PAYMENTS)) {
            $this->logger->notice('Monitoring Payments');
            $this->startPaymentMonitor();
        }
        if ($this->config->hasDestinationForSource(DeliveryDestination::SOURCE_TRANSACTIONS)) {
            $this->logger->notice('Monitoring Transactions');
            $this->startTransactionMonitor();
        }

        $this->isStarted = true;
    }

    protected function startEffectMonitor()
    {
        $destinations = $this->config->getDestinationsForSource(DeliveryDestination::SOURCE_EFFECTS);

        $this->apiClient->streamEffects('now', function(Effect $effect) use ($destinations) {
            // Check each destination for delivery information
            foreach ($destinations as $destination) {
                // Skip if the destination's filter doesn't match
                if (!$destination->shouldFireForRawData($effect->getRawData())) continue;

                $this->dispatchWebhook($destination, $effect->getRawData());
            }
        });
    }

    protected function startLedgerMonitor()
    {
        $destinations = $this->config->getDestinationsForSource(DeliveryDestination::SOURCE_LEDGERS);

        $this->apiClient->streamLedgers('now', function(Ledger $ledger) use ($destinations) {
            // Check each destination for delivery information
            foreach ($destinations as $destination) {
                // Skip if the destination's filter doesn't match
                if (!$destination->shouldFireForRawData($ledger->getRawData())) continue;

                $this->dispatchWebhook($destination, $ledger->getRawData());
            }
        });
    }

    protected function startOperationMonitor()
    {
        $destinations = $this->config->getDestinationsForSource(DeliveryDestination::SOURCE_OPERATIONS);

        $this->apiClient->streamOperations('now', function(Operation $operation) use ($destinations) {
            // Check each destination for delivery information
            foreach ($destinations as $destination) {
                // Skip if the destination's filter doesn't match
                if (!$destination->shouldFireForRawData($operation->getRawData())) continue;

                $this->dispatchWebhook($destination, $operation->getRawData());
            }
        });
    }

    protected function startPaymentMonitor()
    {
        $destinations = $this->config->getDestinationsForSource(DeliveryDestination::SOURCE_PAYMENTS);

        $this->apiClient->streamPayments('now', function(Payment $payment) use ($destinations) {
            // Check each destination for delivery information
            foreach ($destinations as $destination) {
                // Skip if the destination's filter doesn't match
                if (!$destination->shouldFireForRawData($payment->getRawData())) continue;

                $this->dispatchWebhook($destination, $payment->getRawData());
            }
        });
    }

    protected function startTransactionMonitor()
    {
        $destinations = $this->config->getDestinationsForSource(DeliveryDestination::SOURCE_TRANSACTIONS);

        $this->apiClient->streamTransactions('now', function(Transaction $transaction) use ($destinations) {
            // Check each destination for delivery information
            foreach ($destinations as $destination) {
                // Skip if the destination's filter doesn't match
                if (!$destination->shouldFireForRawData($transaction->getRawData())) continue;

                $this->dispatchWebhook($destination, $transaction->getRawData());
            }
        });
    }

    protected function dispatchWebhook(DeliveryDestination $destination, $payload)
    {
        $outgoingWebhook = new OutgoingWebhook($destination->getSource(), $destination->getTargetUrl(), $payload);
        $this->em->persist($outgoingWebhook);
        $this->em->flush($outgoingWebhook);

        $this->logger->debug(sprintf("%s -> %s\n", $destination->getSource(), $destination->getTargetUrl()));
    }
}