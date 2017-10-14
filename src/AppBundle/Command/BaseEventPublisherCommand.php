<?php


namespace AppBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseEventPublisherCommand extends ContainerAwareCommand
{
    /**
     * Updates the verbosity of $output to match the environment variable's setting
     *
     * @param OutputInterface $output
     */
    protected function applyVerbosityFromEnvironment(OutputInterface $output)
    {
        $externalVerbosity = strtolower(getenv('SEP_LOG_VERBOSITY'));

        if ('quiet'   == $externalVerbosity) $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        if ('normal'  == $externalVerbosity) $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        if ('verbose' == $externalVerbosity) $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        if ('debug'   == $externalVerbosity) $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
    }
}