<?php
/**
 * Today's EUR rate, the cheapest A95 and a price search.
 *
 *     SATELIT_API_KEY=sat_live_... php examples/quickstart.php
 */

require __DIR__ . '/../src/autoload.php'; // or vendor/autoload.php with Composer

use Satelit\Client;
use Satelit\Exception\ApiException;

$satelit = new Client(getenv('SATELIT_API_KEY'), array('lang' => 'ro'));

try {
    $rates = $satelit->rates()->list();
    foreach ($rates as $r) {
        if ($r['code'] === 'EUR') {
            printf("EUR: %.4f lei (%s)\n", $r['value'], $r['date']);
        }
    }
    echo $rates->meta->attribution, "\n";

    $fuel = $satelit->fuel()->board();
    foreach ($fuel->data['networks'] as $n) {
        if ($n['key'] === $fuel->data['cheapest']['a95']) {
            printf("Cheapest A95: %s, %.2f lei/l\n", $n['name'], $n['prices']['a95']);
        }
    }

    $milk = $satelit->prices()->search('lapte');
    printf("%s: from %.2f lei in %d shops\n", $milk[0]['name'], $milk[0]['min_price'], $milk[0]['stores']);
    printf("Data: Satelit (satelit.md). Credits left: %d\n", $milk->rate->creditsRemaining);
} catch (ApiException $e) {
    fprintf(STDERR, "%d %s: %s\n", $e->getStatus(), $e->getErrorCode(), $e->getApiMessage());
    exit(1);
}
