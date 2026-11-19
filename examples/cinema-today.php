<?php
/**
 * Today in a city's cinemas, from the command line. A Movies call costs 40
 * credits, so the answer is kept 2 hours in a file cache: run it as often as
 * you like.
 *
 *     SATELIT_API_KEY=sat_live_... php examples/cinema-today.php Chișinău
 */

require __DIR__ . '/../src/autoload.php';

use Satelit\Cache\FileCache;
use Satelit\Client;

$city = isset($argv[1]) ? $argv[1] : 'Chișinău';
try {
    $client = new Client(getenv('SATELIT_API_KEY'), array(
        'cache' => new FileCache(sys_get_temp_dir() . '/satelit-cache'),
    ));
    $today = $client->movies()->today(array('city' => $city));
} catch (\Exception $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

printf("%s, %d cinemas%s\n", $today->data['day'], count($today->data['cinemas']), $today->cached ? ' (from the cache, 0 credits)' : '');
foreach ($today->data['cinemas'] as $c) {
    printf("\n%s (%s)\n", $c['cinema']['name'], $c['cinema']['city']);
    foreach ($c['films'] as $film) {
        $times = array();
        foreach ($film['sessions'] as $s) {
            $times[] = $s['time'] . ($s['formats'] ? ' ' . implode('/', $s['formats']) : '');
        }
        printf("  %s: %s\n", $film['title'], implode(', ', $times));
    }
}
echo "\nData: Satelit (satelit.md)\n";
