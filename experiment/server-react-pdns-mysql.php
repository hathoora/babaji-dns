<?php

    require __DIR__ . '/init.php';

    /**
     * PDNS-MYSQL like DNS server supports both TCP & UDP
     */

    $loop = React\EventLoop\Factory::create();

    echo "React Pdns-Mysql DNS running at port 554\n";

    /**
     * Fill in database credentials for pdns database
     */
    $db = new React\MySQL\Connection($loop, [
        'dbname' => '',
        'user'   => '',
        'passwd' => '',
        'host'   => '127.0.0.1'
    ]);

    $db->connect(function ($err, $conn) {
        if ($err instanceof \Exception) {
            die($err->getMessage());
        }
    });

    $server = new React\Dns\Server\Server($loop);
    $server->listen(554, '0.0.0.0');
    $server->ready();

    $server->on('query', function($question, $clientIP, $response, $deferred) use($db)
    {
        /**
            @var $question  React\Dns\Query\Query
            @var $request   React\Dns\Model\Message
            @var $deferred  React\Promise\Deferred
        */

        $db->query('SELECT * FROM records WHERE name = ? AND type = ?', $question->name, $question->getCode(),
                    function ($command, $conn) use($deferred, $response)
                    {
                        if (!$command->hasError())
                        {
                            $arr = $command->resultRows;
                            if (count($arr))
                            {
                                foreach($arr as $_arr)
                                {
                                    $response->answers[] = new React\Dns\Model\Record(
                                                                $_arr['name'],
                                                                $_arr['type'],
                                                                React\Dns\Model\Message::CLASS_IN,
                                                                $_arr['ttl'],
                                                                $_arr['content']);
                                }
                            }
                            
                            $deferred->resolve($response);
                        }
                        else
                            $deferred->reject($response);
                    });
    });


    $loop->run();
