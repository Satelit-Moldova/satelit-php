<?php
/**
 * A small exchange-rate block for any PHP page, on old hosting too.
 *
 *     <?php include __DIR__ . '/rates-widget.php'; ?>
 *
 * The key stays on the server (an environment variable or a file outside the
 * web root), the answer is kept 3 hours in a file cache (BNM publishes once a
 * working day), every value is escaped, and a dead API shows the last good
 * copy or nothing: never an error on the page.
 */

require_once __DIR__ . '/../src/autoload.php';

use Satelit\Cache\FileCache;
use Satelit\Client;

function satelit_rates_widget(array $codes = array('EUR', 'USD', 'RON'))
{
    $key = getenv('SATELIT_API_KEY');
    if (!$key) {
        return '';
    }
    try {
        $client = new Client($key, array(
            'lang' => 'ro',
            'cache' => new FileCache(sys_get_temp_dir() . '/satelit-cache'),
        ));
        $rates = $client->rates()->list();
    } catch (\Exception $e) {
        // The API down with no copy kept, or the cache folder not writable:
        // one short line in the log (never the key), nothing on the page.
        error_log('satelit rates widget: ' . $e->getMessage());
        return '';
    }

    $h = function ($s) {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    };
    $rows = '';
    foreach ($rates as $r) {
        if (!in_array($r['code'], $codes, true)) {
            continue;
        }
        $rows .= sprintf(
            '<tr><th scope="row">%s</th><td>%s lei</td></tr>',
            $h($r['code']),
            $h(number_format($r['value'] / max(1, $r['nominal']), 4, ',', ' '))
        );
    }
    if ($rows === '') {
        return '';
    }
    $date = isset($rates[0]['date']) ? $rates[0]['date'] : '';
    $foot = array();
    foreach ($rates->meta->attributionLines() as $line) {
        $foot[] = $h($line);
    }
    return '<table class="satelit-rates"><caption>Cursul oficial BNM, ' . $h($date) . '</caption>' . $rows . '</table>'
        . '<p class="satelit-source"><small>' . implode('<br>', $foot)
        . ' &middot; <a href="https://satelit.md" rel="noopener">satelit.md</a></small></p>';
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    echo satelit_rates_widget(), "\n";
}
