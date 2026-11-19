<?php

namespace Satelit\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Satelit\Tests\Fixtures;

/**
 * Every recorded live answer read through its SDK method.
 */
final class RoutesTest extends TestCase
{
    public function testEverySampledRoute()
    {
        list($c, $t) = Fixtures::client();

        $me = $c->me();
        $this->assertSame('pro', $me['plan']);
        $this->assertSame(600, $me['rate']['per_minute']);

        $this->assertSame('EUR', $c->rates()->history('EUR', array('days' => 30))->data['code']);
        $fuel = $c->fuel()->board();
        $this->assertSame(28.08, $fuel['cap']['a95']);
        $this->assertSame('tlx', $fuel['cheapest']['a95']);
        $st = $c->fuel()->stations(array('network' => 'petrom'));
        $this->assertSame(65, $st->meta->page['total']);
        $this->assertNull($st[0]['prices']['lpg']);
        $this->assertSame('a95', $c->fuel()->history('a95')->data['fuel']);
        $this->assertSame('gas', $c->tariffs()->list()[0]['key']);
        $this->assertNotEmpty($c->indexes()->basket()->data['points']);
        $this->assertSame(3001, $c->indexes()->moves(array('days' => 30))->data['compared']);
        $this->assertSame(110556, $c->law()->acts()[0]['id']);
        $this->assertSame(28, $c->law()->digests()[0]['acts']);
        $news = $c->news()->list(array('per' => 3));
        $this->assertSame('politics', $news[0]['category']['key']);
        $this->assertSame('high', $c->news()->now()[0]['band']);
        $this->assertNotEmpty($c->news()->categories()->data);
        $this->assertNotEmpty($c->news()->regions()->data['slots']);
        $this->assertSame('shop', $c->places()->list(array('kind' => 'shop'))[0]['kind']);
        $this->assertSame('ardei-umpluti', $c->recipes()->list()[0]['slug']);
        $this->assertGreaterThan(0, $c->prices()->stores()[0]['products']);
        $this->assertSame(15.99, $c->prices()->search('lapte')[0]['min_price']);
        $this->assertSame('lapte', $c->prices()->groups()[0]['id']);
        $this->assertSame('Nr1', $c->deals()->list()[0]['store']['name']);
        $this->assertNotEmpty($c->tours()->countries()->data);
        $this->assertSame('EUR', $c->tours()->list(array('country' => 'Turcia'))[0]['currency']);
        $this->assertNotNull($c->flights()->destinations()[0]['fare_from']);
        $this->assertSame('departures', $c->flights()->board(array('kind' => 'departures'))->data['kind']);
        $this->assertNotEmpty($c->rest()->list()[0]['sources']);
        $this->assertNotEmpty($c->estate()->series()->data['series']);
        $this->assertSame(24, $c->power()->day()->data['intervals']);
        $this->assertSame('j_76', $c->jokes()->today()->data['id']);
        $this->assertNotEmpty($c->courts()->courts()[0]['slug']);
        $this->assertNotEmpty($c->citizenship()->oath()->data['as_of']['line']);
        $this->assertSame('1002600048836', $c->companies()->search('moldtelecom')[0]['idno']);
        $this->assertGreaterThan(0, $c->companies()->stats()->data['total']);
        $this->assertNotEmpty($c->delivery()->get()->data['stores']);
        $this->assertNotEmpty($c->insurance()->market()->data['classes']);
        $this->assertSame('street', $c->geo()->search('str. Ismail 81, Chișinău')->data['precision']);
        $this->assertSame('Groceries', $c->catalog()->tree()->data['categories'][0]['label']['en']);
        $this->assertSame('m603', $c->movies()->search('matrix')->data['results'][0]['id']);
        $this->assertSame('Matrix', $c->movies()->title('m603')->data['title']);
        $this->assertSame(4.7, $c->movies()->titleFull('m603')->data['moods']['pace']);
        $this->assertSame('MD', $c->movies()->showtimes('m603')->data['country']);
        $this->assertSame(500, $c->movies()->discover(array('sort' => 'rating'))->data['pages']);
        $this->assertNotEmpty($c->movies()->trending()->data['results']);
        $this->assertNotEmpty($c->movies()->inCinemas()->data['dates']['maximum']);
        $this->assertSame('MD', $c->movies()->cinemas()->data['coverage']['country']);
        $today = $c->movies()->today(array('city' => 'Chișinău'));
        $this->assertNotEmpty($today->data['cinemas'][0]['films'][0]['sessions']);
        $this->assertNotEmpty($c->movies()->genres()->data['genres']);
        $this->assertSame('Keanu Reeves', $c->movies()->person('p6384')->data['name']);
        $this->assertGreaterThan(0, $c->products()->data['shops']['open']);
        $this->assertSame('MDL', $c->plans()->data['currency']);
        $this->assertTrue($c->status()->data['ok']);
        $this->assertNotEmpty($c->stats()->data['counts']);
        $this->assertSame('EUR', $c->sample()->data['rates'][0]['code']);

        // Paths and required parameters reach the URL.
        $urls = array_map(function ($r) {
            return $r['url'];
        }, $t->requests);
        $this->assertContains('https://api.test/v1/rates/history?code=EUR&days=30&lang=ro', $urls);
        $this->assertContains('https://api.test/v1/movies/titles/m603/full?lang=ro', $urls);
    }

    public function testRatesMetaAndRateInfo()
    {
        list($c) = Fixtures::client();
        $r = $c->rates()->list();
        $this->assertSame(20.1411, $r[0]['value']);
        $this->assertCount(3, $r); // the fixture keeps 3 of 5 rows
        $this->assertStringContainsString('Banca Națională a Moldovei', $r->meta->attribution);
        $this->assertNotNull($r->meta->notice);
        $this->assertSame(1, $r->meta->credits);
        $this->assertSame(1, $r->rate->creditsCost);
        $this->assertSame(300000, $r->rate->creditsLimit);
        $this->assertSame(600, $r->rate->limit);
        $this->assertNotNull($r->rate->creditsReset);
        $this->assertFalse($r->cached);
        $lines = $r->meta->attributionLines();
        $this->assertSame('Data: Satelit (satelit.md)', end($lines));
        $codes = array();
        foreach ($r as $row) {
            $codes[] = $row['code'];
        }
        $this->assertSame('EUR', $codes[0]);
    }

    public function testEveryServiceMethodBuildsItsPath()
    {
        // Each generated method must call its own route: probe them all
        // against a transport that only records.
        $t = new \Satelit\Http\MockTransport(function () {
            return new \Satelit\Http\HttpResponse(200, array(), '{"data":[],"meta":{"credits":0,"updated_at":null,"sources":[],"attribution":null}}');
        });
        $c = new \Satelit\Client('k', array('transport' => $t, 'base_url' => 'https://api.test/v1'));
        $calls = 0;
        foreach (array('rates', 'fuel', 'tariffs', 'indexes', 'law', 'news', 'places', 'recipes', 'prices', 'deals', 'tours',
            'flights', 'rest', 'estate', 'power', 'market', 'jokes', 'courts', 'citizenship', 'companies', 'delivery',
            'insurance', 'geo', 'catalog', 'movies') as $group) {
            $svc = $c->$group();
            $ref = new \ReflectionClass($svc);
            foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $m) {
                if ($m->isConstructor() || $m->getDeclaringClass()->getName() !== $ref->getName()) {
                    continue;
                }
                $args = array();
                foreach ($m->getParameters() as $p) {
                    if (!$p->isOptional()) {
                        $args[] = 'x1';
                    }
                }
                $res = $m->invokeArgs($svc, $args);
                if ($res instanceof \Generator) {
                    foreach ($res as $unused) {
                    }
                }
                $calls++;
                $last = end($t->requests);
                $this->assertStringStartsWith('https://api.test/v1/', $last['url'], $ref->getShortName() . '::' . $m->getName());
                $this->assertStringContainsString('lang=ro', $last['url']);
            }
        }
        $this->assertGreaterThan(100, $calls);
    }
}
