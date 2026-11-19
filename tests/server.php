<?php
// Router for PHP's built-in server, used by TransportTest (localhost only).
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
header('Content-Type: application/json');
header('X-Credits-Cost: 1');
header('X-Credits-Limit: 300000');
header('X-Credits-Remaining: 299999');
header('X-RateLimit-Limit: 600');
switch ($path) {
    case '/v1/rates':
        echo json_encode(array(
            'data' => array(array('code' => 'EUR', 'value' => 20.1411, 'auth' => isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : null, 'lang' => isset($_GET['lang']) ? $_GET['lang'] : null, 'ua' => $_SERVER['HTTP_USER_AGENT'])),
            'meta' => array('credits' => 1, 'updated_at' => null, 'sources' => array(), 'attribution' => 'Sursa: Banca Națională a Moldovei, bnm.md, CC BY 4.0'),
        ));
        break;
    case '/v1/redirect':
        header('Location: /v1/rates?lang=ro', true, 302);
        break;
    case '/v1/missing':
        http_response_code(404);
        echo '{"error":{"code":"not_found","message":"Nu am găsit.","docs":"https://developer.satelit.md/en/errors/#not_found"}}';
        break;
    case '/v1/busy':
        http_response_code(429);
        header('Retry-After: 1');
        echo '{"error":{"code":"rate_limited","message":"m","docs":"d"}}';
        break;
    case '/v1/export/rates':
        header('Content-Type: application/x-ndjson');
        echo "{\"_meta\":{\"product\":\"rates\"}}\n";
        for ($i = 0; $i < 5000; $i++) {
            echo json_encode(array('i' => $i, 'pad' => str_repeat('x', 200))), "\n";
            if ($i % 1000 === 0) {
                flush();
            }
        }
        echo "{\"_end\":{\"rows\":5000,\"complete\":true}}\n";
        break;
    case '/v1/slow':
        sleep(3);
        echo '{"data":[],"meta":{}}';
        break;
    default:
        http_response_code(404);
        echo '{"error":{"code":"not_found","message":"no route","docs":"d"}}';
}
