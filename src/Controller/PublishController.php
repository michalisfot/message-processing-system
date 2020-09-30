<?php

// src/Controller/PublishController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\HexConverter;
use \OldSound\RabbitMqBundle\RabbitMq\Producer;

class PublishController extends AbstractController
{
    private $client;
    private $publisher;

    public function __construct(HttpClientInterface $client, Producer $publisher, HexConverter $hexconverter)
    {
        $this->client = $client;
        $this->publisher = $publisher;
        $this->hexconverter = $hexconverter;
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
            $responses[] = $this->client->request(
                'GET',
                $uri
            );
        }

        foreach ($responses as $response) {
            $content = json_decode($response->getContent());
            // $content = '{"gatewayEui":84df0c002d901dfd, "profileId":"0x0104", ...}'        

            $gatewayEui = $this->hexconverter->Hex2Dec($content->{'gatewayEui'});
            $profileId = hexdec($content->{'profileId'});
            $endpointId = hexdec($content->{'endpointId'});
            $clusterId = hexdec($content->{'clusterId'});
            $attributeId = hexdec($content->{'attributeId'});
            $routing_key = $gatewayEui.'.'.$profileId.'.'.$endpointId.'.'.$clusterId.'.'.$attributeId;
            $msg = json_encode(array('routing_key' => $routing_key, 'value' => $content->{'value'}, 'timestamp' => $content->{'timestamp'}));

            $this->publisher->setContentType('application/json');
            $this->publisher->publish($msg, $routing_key);
        }

        $response = $num.' requests to the API were made and published to the RabidMQ exchange.';

        return new Response($response);
    }
}
