<?php

    require __DIR__ . '/init.php';

    /**
     * Simple echo back server in TCP & UDP using phpreact
     */

    echo "React Echo DNS running at port 553\n";

    $loop = React\EventLoop\Factory::create();

    $server = new React\Dns\Server\Server($loop);
    $server->listen(553, '0.0.0.0');
    $server->ready();

    $server->on('query', function($question, $clientIP, $response, $deferred)
    {
        /**
            @var $question  React\Dns\Query\Query
            @var $request   React\Dns\Model\Message
            @var $deferred  React\Promise\Deferred
        */

        $deferred->resolve($response);
    });

    $loop->run();