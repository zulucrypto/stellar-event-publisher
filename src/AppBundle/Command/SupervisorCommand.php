<?php


namespace AppBundle\Command;


use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class SupervisorCommand extends BaseEventPublisherCommand
{
    /**
     * @var Logger
     */
    protected $logger;

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

            sleep(5);
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
        $process->setTty(true);

        $process->start(function ($type, $buffer) use ($output) {
            $output->write($buffer);
        });

        return $process;
    }
}