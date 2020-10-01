<?php
namespace App\Rabbit;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use App\Entity\Metric;
use Doctrine\ORM\EntityManagerInterface;

class MessageService implements ConsumerInterface
{
    public function __construct(EntityManagerInterface $entityManager) 
    {
        // Database service injection
        $this->entityManager = $entityManager;
    }

    /**
     * Consumes messages from a RabbitMQ queue, processes it and stores it in the database.
     * If the number of messages exceeds the queue size an exception will be thrown after 15 seconds.
     * 
     */
    public function execute(AMQPMessage $msg)
    {
        $body = $msg->getBody();
        $routing_key = $msg->getRoutingKey();
        $response = json_decode($body, true);

        // fetch the EntityManager
        $entityManager = $this->entityManager;

        // Split the routing_key into its separate parts
        $routing_key_arr = explode('.', $routing_key);

        // Create a new instance of the Metric entity and populates its fields
        $metric = new Metric();
        $metric->setGatewayEui($routing_key_arr[0]);
        $metric->setAttributeId($routing_key_arr[4]);
        $metric->setValue($response['value']);
        $metric->setTimestamp($response['timestamp']);

        // tell Doctrine you want to (eventually) save the Product (no queries yet)
        $entityManager->persist($metric);

        // actually executes the queries (i.e. the INSERT query)
        $entityManager->flush();
    }
}