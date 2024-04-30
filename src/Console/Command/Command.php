<?php

namespace Oak\Console\Command;

use Oak\Contracts\Console\InputInterface;
use Oak\Contracts\Console\OutputInterface;
use Oak\Contracts\Container\ContainerInterface;

abstract class Command
{
    /**
     * @var ContainerInterface $app
     */
    protected $app;

    /**
     * The signature of the command
     *
     * @var Signature
     */
    private $signature;

    /**
     * Stores if the command was created
     *
     * @var bool
     */
    private $created = false;

    /**
     * Command constructor.
     * @param ContainerInterface $app
     */
    public function __construct(ContainerInterface $app)
    {
        $this->app = $app;
    }

    /**
     * Creates the signature of the command
     *
     * @param Signature $signature
     * @return Signature
     */
    abstract protected function createSignature(Signature $signature): Signature;

    /**
     * @return string
     * @throws \Exception
     */
    final public function getName(): string
    {
        $this->make();

        if (! $this->signature->hasName()) {
            throw new \Exception('Command should have a name');
        }

        return $this->signature->getName();
    }

    /**
     * @return string
     * @throws \Exception
     */
    final public function getDescription(): string
    {
        $this->make();

        return $this->signature->getDescription();
    }

    /**
     * @return Signature
     * @throws \Exception
     */
    final public function getSignature(): Signature
    {
        $this->make();
        return $this->signature;
    }

    /**
     * Creates the signature of the command
     *
     * @throws \Exception
     */
    final protected function make()
    {
        if ($this->created) {
            return;
        }

        $signature = $this->app->get(Signature::class);

        $signature
            ->addOption(
                Option::create('help', 'h')
                    ->setDescription('Display the help message')
            )
        ;

        $this->signature = $this->createSignature($signature);

        if (! $this->signature->hasName()) {
            throw new \Exception('Command should have a name');
        }

        $this->created = true;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->outputHelpMessage($output);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Exception
     */
    final public function run(InputInterface $input, OutputInterface $output)
    {
        $input->setSignature($this->getSignature());

        $showHelpMessage = (bool) $input->getOption('help');

        if ($showHelpMessage && ! $input->hasSubCommand()) {
            $this->outputHelpMessage($output);
            return;
        }

        if ($input->hasSubCommand()) {
            $command = $this->signature->getSubCommand($input->getSubCommand());
            $command->run($input, $output);
            return;
        }

        $input->validate();

        $this->execute($input, $output);
    }

    /**
     * @param OutputInterface $output
     * @throws \Exception
     */
    public function outputHelpMessage(OutputInterface $output)
    {
        if ($this->getDescription()) {
            $output->writeLine(ucfirst($this->getName()).':', OutputInterface::TYPE_WARNING);
            $output->writeLine($this->getDescription());
        }

        $output->newline();

        $commands = $this->signature->getSubCommands();
        ksort($commands);
        $arguments = $this->signature->getArguments();
        $options = $this->signature->getOptions();

        $output->writeLine('Usage:', OutputInterface::TYPE_WARNING);

        $output->write($this->getName());
        if (count($commands)) {
            $output->write(' [command]');
        }

        if (count($arguments)) {
            foreach ($arguments as $argument) {
                $output->write(' <'.$argument->getName().'>');
            }
        }

        if (count($options)) {
            $output->write(' [options]');
        }

        $output->newline();
        $output->newline();

        // Output available commands
        if (count($commands)) {
            $output->writeLine('Available commands:', OutputInterface::TYPE_WARNING);

            foreach ($commands as $command) {
                $output->write(str_pad($command->getName(), 20), OutputInterface::TYPE_INFO);
                $output->write($command->getDescription(), OutputInterface::TYPE_INFO);
                $output->newline();

                $subCommands = $command->getSignature()->getSubCommands();
                ksort($subCommands);

                foreach ($subCommands as $subCommand) {
                    $output->write(' '.str_pad($subCommand->getName(), 19), OutputInterface::TYPE_PLAIN);
                    $output->write($subCommand->getDescription());
                    $output->newline();
                }
            }

            $output->newline();
        }

        // Output available arguments
        if (count($arguments)) {
            $output->writeLine('Arguments:', OutputInterface::TYPE_WARNING);

            foreach ($arguments as $argument) {
                $output->write(str_pad($argument->getName(), 20), OutputInterface::TYPE_INFO);
                $output->write($argument->getDescription());
                $output->newline();
            }

            $output->newline();
        }

        // Output available options
        if (count($options)) {
            $output->writeLine('Options:', OutputInterface::TYPE_WARNING);

            foreach ($options as $option) {
                $output->write(str_pad('-'.$option->getAlias().', --'.$option->getName(), 20), OutputInterface::TYPE_INFO);
                $output->write($option->getDescription());
                $output->newline();
            }

            $output->newline();
        }
    }
}