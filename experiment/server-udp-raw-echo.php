<?php
    require __DIR__ . '/init.php';

    /**
     * This server doesn't use phpreact for creating a server, but uses its Parser & Binarydumper
     * for echo dns server
     */

    $parser = new React\Dns\Protocol\Parser();
    $dumper = new React\Dns\Protocol\BinaryDumper();

    echo "Raw Echo DNS running at port 555\n";
    $socket = stream_socket_server("udp://0.0.0.0:555", $errno, $errstr, STREAM_SERVER_BIND);
    do
    {
        $pkt = stream_socket_recvfrom($socket, 1500, 0, $peer);
        $reply = $pkt;

        $message = new React\Dns\Model\Message();
        $request = $parser->parseChunk($pkt, $message);

        $response = new React\Dns\Model\Message();
        $response->transport = $request->transport;
        $response->header->set('id', $request->header->attributes['id']);
        $response->header->set('qr', 1);                                         // 0 = Query, 1 = Response
        $response->header->set('aa', 1);                                         // 1 = Authoritative response
        $response->header->set('rd', $request->header->attributes['rd']);        // Recursion desired, copied from request
        $response->header->set('ra', 0);                                         // 0 = Server is non-recursive
        $response->header->set('opcode', $request->header->attributes['opcode']);
        $response->header->set('rcode', React\Dns\Model\Message::RCODE_OK);

        $question = $request->questions[0];
        $response->questions[] = $question;
        $response->prepare();
        $reply = $dumper->toBinary($response);

        stream_socket_sendto($socket, $reply,0, $peer);
    }while ($pkt !== false);

