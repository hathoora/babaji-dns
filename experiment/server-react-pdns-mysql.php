<?php

    require __DIR__ . '/init.php';

    /**
     * PDNS-MYSQL like DNS server supports both TCP & UDP
     */

    $loop = React\EventLoop\Factory::create();

    echo "React Pdns-Mysql DNS running at port 555\n";

    /**
     * Fill in database credentials for pdns database
     */
    $db = new React\MySQL\Connection($loop, [
        'dbname' => 'k',
        'user'   => '',
        'passwd' => '',
        'host' => ''
    ]);

    $db->connect(function ($err, $conn) {
        if ($err instanceof \Exception) {
            die($err->getMessage());
        }
    });

    $server = new React\Dns\Server\Server($loop);
    $server->listen(555, '0.0.0.0');
    $server->ready();

    $server->on('query', function($question, $clientIP, $response, $deferred) use($db)
    {
        /**
            @var $question  React\Dns\Query\Query
            @var $request   React\Dns\Model\Message
            @var $deferred  React\Promise\Deferred
        */

        $db->query('SELECT * FROM pdns_records WHERE name = ? AND type = ?', $question->name, $question->getCode(),
                    function ($err, $command, $conn) use($deferred, $response)
                    {
                        if ($command->hasError())
                        {
                            $arr = $command->resultRows;
                            print_r($arr);
                            $deferred->resolve($response);
                        }
                        else
                            $deferred->reject($response);
                    });
    });


    $loop->run();