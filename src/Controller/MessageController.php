<?php

// src/Controller/MessageController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\HexConverter;
use \OldSound\RabbitMqBundle\RabbitMq\Producer;
use \OldSound\RabbitMqBundle\RabbitMq\Consumer;

class MessageController extends AbstractController
{
    private $client;
    private $publisher;
    private $consumer;

    public function __construct(HttpClientInterface $client, Producer $publisher, Consumer $consumer, HexConverter $hexconverter)
    {
        $this->client = $client;
        $this->publisher = $publisher;
        $this->hexconverter = $hexconverter;
        $this->consumer = $consumer;
    }

    /**
     * Makes requests to an external API, processes the response and publishes 
     * a message to a RabbitMQ exchange with a valid routing key
     * 
     * @param int $num   Number of requests to make to the API. Default value is 1.
     * @return Response  A message is returned as a Response
     * 
     * @Route("/publish/{num<\d+>?1}", name="publish_to_exchange")
     */
    public function publishToExchange(int $num)
    {
        $responses = [];
        for ($i = 0; $i < $num; ++$i) {
            $uri = "https://a831bqiv1d.execute-api.eu-west-1.amazonaws.com/dev/results";
            // Make a request
            $responses[] = $this->client->request(
                'GET',
                $uri
            );
        }

        $published = 0;
        // Loop over the responses
        foreach ($responses as $response) {
            // Get the status code
            $statusCode = $response->getStatusCode();
            
            // Check whether thee status code is equal to 200-->OK
            if ($statusCode == 200) {
                $content = json_decode($response->getContent());
                // $content = '{"gatewayEui":84df0c002d901dfd, "profileId":"0x0104", ...}'        

                // Convert the hexadecimal values to their decimal equivalents
                $gatewayEui = $this->hexconverter->Hex2Dec($content->{'gatewayEui'});
                $profileId = hexdec($content->{'profileId'});
                $endpointId = hexdec($content->{'endpointId'});
                $clusterId = hexdec($content->{'clusterId'});
                $attributeId = hexdec($content->{'attributeId'});
                
                // Create the routing key
                $routing_key = $gatewayEui.'.'.$profileId.'.'.$endpointId.'.'.$clusterId.'.'.$attributeId;
                
                // Structure our message in JSON format
                $msg = json_encode(array('value' => $content->{'value'}, 'timestamp' => $content->{'timestamp'}));

                // Set the message content type
                $this->publisher->setContentType('application/json');
                // Publish the message
                $this->publisher->publish($msg, $routing_key);
                $published =+ 1;
            }
        }

        $response = $num.' requests to the API were made and '.$published.' messages were published to the RabidMQ exchange.';

        return new Response($response);
    }

    /**
     * Consumes messages from a RabbitMQ queue. The callback ivokes the MessageService 
     * which processes the message and stores it in the database.
     * 
     * @param int $num   Number of messages to consume from the queue. Default value is 1.
     * @return Response  A message is returned as a Response
     * 
     * @Route("/consume/{num<\d+>?1}", name="consume_from_queue")
     */
    public function consumeFromQueue(int $num)
    {
        // Consume $num messages
        $this->consumer->consume($num);

        return new Response($num.' messages consumed.');
    }
}
