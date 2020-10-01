<?php

// src/Command/ConsumeMessageCommand.php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Controller\MessageController;

class ConsumeMessageCommand extends Command
{
    protected static $defaultName = 'consume-message';

    private $publishController;

    public function __construct(MessageController $publishController)
    {
        $this->publishController = $publishController;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('This comand ivokes the ConsumeController, consumes messages from the RabbitMQ queue and stores them in the database.')
            ->addArgument('number_of_messages', InputArgument::OPTIONAL, 'Defines the number of messaages to be consumed from the queue.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $number_of_messages = $input->getArgument('number_of_messages');

        if ($number_of_messages) {
            $io->note(sprintf('No. of messages to be consumed: %s', $number_of_messages));
        }

        $this->publishController->consumeFromQueue($number_of_messages);

        $io->success('You have successfully consumed '.$number_of_messages.' message(s).');

        return 0;
    }
}
