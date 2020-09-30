<?php

// src/Controller/ConsumeController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use \OldSound\RabbitMqBundle\RabbitMq\Consumer;

class ConsumeController extends AbstractController
{
    private $consumer;

    public function __construct(Consumer $consumer)
    {
        $this->consumer = $consumer;
    }

    /**
     * Consumes messages from a RabbitMQ queue. The callback ivokes the MessageService 
     * which processes the message and stores it into the database.
     * 
     * @param int $num   Number of messages to consume from the queue. Default value is 1.
     * @return Response  A message is returned as a Response
     * 
     * @Route("/consume/{num<\d+>?1}", name="consume_message")
     */
    public function consumeMessage(int $num)
    {
        $this->consumer->consume($num);

        return new Response($num.' messages consumed.');
    }
}
