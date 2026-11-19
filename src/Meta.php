<?php

namespace Satelit;

/**
 * The "meta" block of an answer. Show $attribution wherever the data is shown
 * when it is not null, and a "Data: Satelit (satelit.md)" link.
 */
final class Meta
{
    /** @var int */
    public $credits = 0;
    /** @var string|null */
    public $updatedAt;
    /** @var array<int,array{name:string,url?:string,license?:string}> */
    public $sources = array();
    /** @var string|null */
    public $attribution;
    /** @var string|null */
    public $notice;
    /** @var array{page:int,per:int,total:int,pages:int}|null */
    public $page;
    /** @var string|null the feed's cursor */
    public $next;
    /** @var bool */
    public $hasMore = false;
    /** @var array every field as sent (slot, region, coverage, history_cut, ...) */
    public $raw = array();

    /**
     * @return Meta
     */
    public static function fromArray(array $m)
    {
        $meta = new self();
        $meta->raw = $m;
        $meta->credits = isset($m['credits']) ? (int) $m['credits'] : 0;
        $meta->updatedAt = isset($m['updated_at']) ? (string) $m['updated_at'] : null;
        $meta->sources = isset($m['sources']) && is_array($m['sources']) ? $m['sources'] : array();
        $meta->attribution = isset($m['attribution']) ? (string) $m['attribution'] : null;
        $meta->notice = isset($m['notice']) ? (string) $m['notice'] : null;
        $meta->page = isset($m['page']) && is_array($m['page']) ? $m['page'] : null;
        $meta->next = isset($m['next']) ? (string) $m['next'] : null;
        $meta->hasMore = !empty($m['has_more']);
        return $meta;
    }

    /**
     * The lines to show under the data: the source's attribution, its notice,
     * and Satelit's line when the attribution does not already name it.
     *
     * @return string[]
     */
    public function attributionLines()
    {
        $lines = array();
        if ($this->attribution !== null && $this->attribution !== '') {
            $lines[] = $this->attribution;
        }
        if ($this->notice !== null && $this->notice !== '') {
            $lines[] = $this->notice;
        }
        if ($this->attribution === null || strpos($this->attribution, 'Satelit') === false) {
            $lines[] = 'Data: Satelit (satelit.md)';
        }
        return $lines;
    }
}
