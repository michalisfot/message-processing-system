<?php

// src/Command/PublishMessageCommand.php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Controller\MessageController;

class PublishMessageCommand extends Command
{
    protected static $defaultName = 'publish-message';

    private $messageController;

    public function __construct(MessageController $messageController)
    {
        $this->messageController = $messageController;

        parent::__construct();
    }
    
    protected function configure()
    {
        $this
            ->setDescription('This comand ivokes the MessageController::publishToExchange method, makes requests to an external API and publishes the message(s) to the RabbitMQ exchange.')
            ->addArgument('number_of_messages', InputArgument::REQUIRED, 'Defines the number of requests to be made to the API and messages to be posted to the exchange.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $number_of_messages = $input->getArgument('number_of_messages');

        if ($number_of_messages) {
            $io->note(sprintf('No. of requests made: %s', $number_of_messages));
        }

        $this->messageController->publishToExchange($number_of_messages);

        $io->success('You have successfully published '.$number_of_messages.' message(s).');

        return 0;
    }
}
