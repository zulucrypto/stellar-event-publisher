<?php


namespace AppBundle\Command;


use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class SupervisorCommand extends BaseEventPublisherCommand
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var bool
     */
    protected $doShutdown;

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('sep:supervisor')
            ->setDescription("Starts the stellar monitoring command and the webhook publishing command")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->applyVerbosityFromEnvironment($output);

        $this->logger = $this->getContainer()->get('monolog.logger.supervisor');
        $watcherProcess = null;
        $deliverProcess = null;

        // Trap signals
        $this->registerSignalHandlers();

        // Ensure processes stay running
        while (true) {
            if (!$watcherProcess || !$watcherProcess->isRunning()) {
                // There was one and it crashed
                if ($watcherProcess) {
                    $this->logger->error('Watcher process has exited, restarting');
                }
                // first run
                else {
                    $this->logger->notice('Starting watcher process');
                }

                $watcherProcess = $this->startBackgroundCommand($input, $output, 'sep:watch-stellar');
            }

            if (!$deliverProcess || !$deliverProcess->isRunning()) {
                // There was one and it crashed
                if ($deliverProcess) {
                    $this->logger->error('Delivery process has exited, restarting');
                }
                // first run
                else {
                    $this->logger->notice('Starting delivery process');
                }

                $deliverProcess = $this->startBackgroundCommand($input, $output, 'sep:deliver-webhooks');
            }

            pcntl_signal_dispatch();
            usleep(1000);

            if ($this->doShutdown) {
                $watcherProcess->stop();
                $deliverProcess->stop();

                break;
            }
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return Process
     */
    protected function startBackgroundCommand(InputInterface $input, OutputInterface $output, $commandName)
    {
        $command = sprintf('php console -v %s', $commandName);

        $process = new Process($command, __DIR__ . '/../../../bin/', [], null, null);
        $process->inheritEnvironmentVariables(true);
        try {
            $process->setTty(true);
        }
        catch (RuntimeException $e) {
            // Don't care, just means no color support
        }

        $process->start(function ($type, $buffer) use ($output) {
            $output->write($buffer);
        });

        return $process;
    }

    protected function registerSignalHandlers()
    {
        // Interrupt (ctrl+c)
        pcntl_signal(SIGINT, function($signal) {
            $this->logger->warning('Got SIGINT, shutting down');
            $this->doShutdown = true;
        });

        pcntl_signal(SIGTERM, function($signal) {
            $this->logger->warning('Got SIGTERM, shutting down');
            $this->doShutdown = true;
        });
    }
}