<?php
/**
 * Live smoke test against the real API (about 50 credits: one Movies call is 40,
 * the second one comes from the cache).
 *
 *     SATELIT_API_KEY=sat_live_... php tests/live-smoke.php
 *     SATELIT_API_KEY_FILE=/run/key php tests/live-smoke.php
 *
 * Prints one line per check, never the key. Exit code 1 when a check fails.
 */

require __DIR__ . '/../src/autoload.php';

use Satelit\Cache\FileCache;
use Satelit\Client;
use Satelit\Exception\AuthenticationException;
use Satelit\Exception\NotFoundException;
use Satelit\Exception\PlanRequiredException;
use Satelit\Exception\ValidationException;
use Satelit\Http\CurlTransport;
use Satelit\Http\StreamTransport;

$key = getenv('SATELIT_API_KEY');
if (!$key && getenv('SATELIT_API_KEY_FILE')) {
    $raw = (string) @file_get_contents(getenv('SATELIT_API_KEY_FILE'));
    // Accept a bare key or a "SATELIT_API_KEY=..." line.
    if (preg_match('/(sat_live_[A-Za-z0-9_\-]+)/', $raw, $m)) {
        $key = $m[1];
    }
}
if (!$key) {
    fwrite(STDERR, "SATELIT_API_KEY or SATELIT_API_KEY_FILE is not set: skipping.\n");
    exit(0);
}

$failed = 0;
function check($name, $ok, $detail)
{
    global $failed;
    if (!$ok) {
        $failed++;
    }
    printf("%s %s: %s\n", $ok ? 'PASS' : 'FAIL', $name, $detail);
}

$cacheDir = sys_get_temp_dir() . '/satelit-smoke-' . getmypid();
$c = new Client($key, array('cache' => new FileCache($cacheDir), 'user_agent' => 'satelit-php-live-smoke'));
printf("PHP %s, transport %s\n", PHP_VERSION, function_exists('curl_init') ? 'curl ' . curl_version()['version'] : 'streams');

try {
    $me = $c->me();
    $used0 = $me['credits']['used'];
    check('me', $me['plan'] !== null, sprintf('plan %s, used %d of %d', $me['plan'], $used0, $me['credits']['limit']));

    $r = $c->rates()->list();
    $eur = null;
    foreach ($r as $row) {
        if ($row['code'] === 'EUR') {
            $eur = $row;
        }
    }
    check('rates', $eur !== null && $r->meta->attribution !== null, sprintf('EUR %.4f on %s, cost %d, attribution "%s"', $eur['value'], $eur['date'], $r->rate->creditsCost, $r->meta->attribution));

    $f = $c->fuel()->board();
    check('fuel', count($f->data['networks']) > 0, sprintf('%d networks, cap A95 %.2f, cheapest A95 %s', count($f->data['networks']), $f->data['cap']['a95'], $f->data['cheapest']['a95']));

    $p = $c->prices()->search('lapte');
    check('prices', count($p) > 0, sprintf('"%s" from %.2f lei in %d shops, %d found', $p[0]['name'], $p[0]['min_price'], $p[0]['stores'], $p->meta->page['total']));

    $m1 = $c->movies()->today(array('city' => 'Chișinău'));
    $m2 = $c->movies()->today(array('city' => 'Chișinău'));
    check('movies today', !$m1->cached && $m2->cached, sprintf('%d cinemas on %s, cost %d, second call cached=%s', count($m1->data['cinemas']), $m1->data['day'], $m1->rate->creditsCost, $m2->cached ? 'yes' : 'no'));

    $g = $c->geo()->search('str. Ismail 81, Chișinău');
    check('geo', $g->data !== null, sprintf('%.5f,%.5f precision %s', $g->data['lat'], $g->data['lon'], $g->data['precision']));

    $first = 0;
    foreach ($c->fuel()->eachStations(array('network' => 'petrom')) as $s) {
        $first++;
    }
    check('pagination', $first > 0, sprintf('%d petrom stations walked', $first));

    try {
        $c->rates()->history('');
        check('422', false, 'no exception');
    } catch (ValidationException $e) {
        check('422', $e->getFields() === array('code'), sprintf('%d %s fields=%s', $e->getStatus(), $e->getErrorCode(), implode(',', $e->getFields())));
    }
    try {
        $c->recipes()->get('no-such-recipe-x');
        check('404', false, 'no exception');
    } catch (NotFoundException $e) {
        check('404', true, $e->getErrorCode());
    }
    try {
        foreach ($c->feed() as $e) {
            break;
        }
        check('feed', true, 'events answered (the plan has the feed)');
    } catch (PlanRequiredException $e) {
        check('feed', true, 'plan_required on ' . $me['plan']);
    }
    try {
        foreach ($c->export('rates') as $row) {
            break;
        }
        check('export', true, 'rows answered (the plan has exports)');
    } catch (PlanRequiredException $e) {
        check('export', true, 'plan_required on ' . $me['plan']);
    }
    try {
        (new Client('sat_live_not_a_real_key_000000'))->rates()->list();
        check('401', false, 'no exception');
    } catch (AuthenticationException $e) {
        check('401', $e->getErrorCode() === 'key_invalid', $e->getErrorCode());
    }

    // The same call over each transport this PHP has.
    $streams = new Client($key, array('transport' => new StreamTransport()));
    $sr = $streams->rates()->list();
    check('streams transport', $sr[0]['value'] > 0, sprintf('%s %.4f', $sr[0]['code'], $sr[0]['value']));
    if (function_exists('curl_init')) {
        $cr = (new Client($key, array('transport' => new CurlTransport())))->rates()->list();
        check('curl transport', $cr[0]['value'] === $sr[0]['value'], sprintf('%s %.4f', $cr[0]['code'], $cr[0]['value']));
    }
    $sample = (new Client(''))->sample();
    check('keyless sample', count($sample->data['rates']) > 0, sprintf('%d rates', count($sample->data['rates'])));

    $me2 = $c->me();
    printf("credits used by this run: %d (the key may be shared)\n", $me2['credits']['used'] - $used0);
} catch (\Satelit\Exception\SatelitException $e) {
    check('unexpected', false, get_class($e) . ': ' . $e->getMessage());
}

(new FileCache($cacheDir))->clear();
@rmdir($cacheDir);
exit($failed ? 1 : 0);
