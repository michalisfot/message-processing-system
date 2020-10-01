# Message processing system
A micro-service developed in Symfony 4 that consumes data from an external API, processes them, publishes them to a RabbitMQ exchange, consumes them from a RabbitMQ queue and stores them into a MySQL database.

# Installation & Usage
```
composer install
symfony server:start
```
Run the following comand to publish 1 message to the exchange:<br/>
`php bin/console publish-message 1`<br/>

Run the following comand to consume 1 message from the queue and store it into the database:<br/>
`php bin/console consume-message 1`

or use docker-compose (run from the docker folder):
```
docker-compose build
docker-compose up -d
```
Run the following comand to publish 1 message to the exchange:<br/>
`docker-compose run php bin/console publish-message 1`<br/>

Run the following comand to consume 1 message from the queue and store it into the database:<br/>
`docker-compose run php bin/console consume-message 1`

Stop the containers:</br>
`docker-compose down`

# Commands
<b>PublishMessageCommand</b>: A custom console command used to ivoke the <b>PublishController</b> and publish messages to the exchange. It takes one argument that defines the number of messages to publish. Use it in the following way: `php bin/console publish-message 1`, with *1* defining the number of messages.

<b>ConsumeMessageCommand</b>: A custom console command used to ivoke the <b>ConsumeController</b> and consume messages from the queue. It takes one argument that defines the number of messages to consume. Use it in the following way: `php bin/console consume-message 1`, with *1* defining the number of messages.

# Controllers & Services
**MessageController**: Handles all the processing of the messages. This controller implements two methods:
- **publishToExchange(int $num)**: Makes requests to an external API, processes the response and publishes a message to a RabbitMQ exchange with a valid routing key. It takes one argument that defines the number of requests and therefore messages to publish to the RabbitMQ exchange.
- **consumeFromQueue(int $num)**: Consumes messages from a RabbitMQ queue. The callback ivokes the MessageService which processes the message and stores it into the database. It takes one argument that defines the number of messages to consume from the RabbitMQ queue.

<b>MessageService</b>: A service that is ivoked by the callback defined in the RabbitMQ queue. It implements the <b>ConsumerInterface::execute</b> method. This method consumes the message from the queue, processes it and stores it into the database. If the number of messages is bigger that the queue size it will throw a timeout exception after 15 seconds.

<b>HexConverter</b>: A helper service that converts a big integer in hexadeciman format to its decimal format. This service is required as the default <b>hexdec()</b> PHP function can't handle big integers.

# Database
Our database only has one table named **metric**. The structure is presented bellow:
| Collumn | Type | Description |
| --- | --- | --- |
| id | int(11) | Unique ID for every entry. This is our primary key. |
| gateway_eui | varchar(255) | The gateway_eui value we got from consuming the API. Because of its big size, it is stored in the database as a string. |
| profile_id | int(11) | The profile_id value we got from consuming the API. |
| endpoint_id | int(11) | The endpoint_id value we got from consuming the API. |
| cluster_id | int(11) | The cluster_id value we got from consuming the API. |
| attribute_id | int(11) | The attribute_id value we got from consuming the API. |
| value | int(11) | The value field we got from consuming the API. |
| timestamp | bigint(20) | The timestamp value we got from consuming the API. It is stored as a bigint in order be more easy to sort entries by this collumn. |
