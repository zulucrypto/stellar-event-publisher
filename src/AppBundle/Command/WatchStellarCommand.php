<?php


namespace AppBundle\Command;


use AppBundle\Config\MonitorConfig;
use AppBundle\Monitor\StellarMonitor;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZuluCrypto\StellarSdk\Horizon\ApiClient;

/**
 * Monitors stellar network and queues up webhooks for delivery when an event
 * matches
 */
class WatchStellarCommand extends BaseEventPublisherCommand
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     *
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('sep:watch-stellar')
            ->setDescription("This command monitors the stellar network and stores events to be published")
            ->addOption('config-source', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Location to look for configuration data')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->applyVerbosityFromEnvironment($output);

        $this->input = $input;
        $this->output = $output;
        $this->logger = $this->getContainer()->get('monolog.logger.watch_stellar');
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');

        $this->logger->notice('Watcher starting...');

        $configuration = $this->buildConfiguration();

        if ($configuration->isEmpty()) {
            $this->logger->warning('Empty configuration, no events will be processed');
        }

        $apiClient = new ApiClient($this->getContainer()->getParameter('horizon_url'));

        $monitor = new StellarMonitor($apiClient, $configuration, $em, $this->logger);

        $monitor->start();

        // Monitor will now process events asynchronously
        while (true) sleep(10);
    }

    /**
     * @return MonitorConfig
     * @throws \ErrorException
     */
    protected function buildConfiguration()
    {
        $rawSources = $this->input->getOption('config-source');

        // See if there's a default configured in parameters.yml
        if ($this->getContainer()->hasParameter('default_config_dir')) {
            $rawSources[] = $this->getContainer()->getParameter('default_config_dir');
        }

        // Check for configuration environment variables
        $configFromEnv = getenv('SEP_CONFIG_FILE');
        if ($configFromEnv) {
            $rawSources[] = $configFromEnv;
        }

        if (!$rawSources) {
            throw new \ErrorException('At least one config-source must be specified');
        }

        $config = new MonitorConfig();

        $this->logger->notice('Loading configuration');
        foreach ($rawSources as $rawSource) {
            $sourceType = null;
            $path = $rawSource;

            // First, try as an absolute path
            if (file_exists($path)) $sourceType = 'localFileOrDir';

            // Then, as a relative path to the current directory
            if (!$sourceType) {
                $path = getcwd() . $path;
                if (file_exists($path)) $sourceType = 'localFileOrDir';
            }

            // If it starts with http:// or https:// consider it a url
            if (strpos($rawSource, 'http://') === 0) $sourceType = 'url';
            if (strpos($rawSource, 'https://') === 0) $sourceType = 'url';

            // If it doesn't appear valid, skip it
            if (!$sourceType) {
                $this->logger->error(sprintf('%s is not a valid configuration location', $rawSource));
                continue;
            }

            if ($sourceType == 'localFileOrDir') {
                $path = realpath($path);

                if (is_dir($path)) {
                    $this->logger->notice(sprintf('[DIR] %s', $path));
                    $config->addSource('directory', $path);
                }
                else {
                    $this->logger->notice(sprintf('[FIL] %s', $path));
                    $config->addSource('file', $path);
                }
            }
            if ($sourceType == 'url') {
                $this->logger->notice(sprintf('[URL] %s', $rawSource));
                $config->addSource('url', $rawSource);
            }
        }
        
        $config->load();

        return $config;
    }
}