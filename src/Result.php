<?php

namespace Satelit;

/**
 * One answer: the decoded data (arrays, as json_decode(..., true) gives them),
 * its Meta and the RateInfo of the headers. It reads like its data too:
 * $result[0]['code'], foreach ($result as $row), count($result).
 */
final class Result implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /** @var mixed */
    public $data;
    /** @var Meta */
    public $meta;
    /** @var RateInfo */
    public $rate;
    /** @var bool true when the answer came from the cache */
    public $cached;
    /** @var bool true when the API failed and this is the last good copy */
    public $stale = false;

    public function __construct($data, Meta $meta, RateInfo $rate, $cached = false)
    {
        $this->data = $data;
        $this->meta = $meta;
        $this->rate = $rate;
        $this->cached = (bool) $cached;
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return is_array($this->data) && array_key_exists($offset, $this->data);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return is_array($this->data) && array_key_exists($offset, $this->data) ? $this->data[$offset] : null;
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        throw new \LogicException('A Satelit result is read only.');
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new \LogicException('A Satelit result is read only.');
    }

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator(is_array($this->data) ? $this->data : array());
    }

    #[\ReturnTypeWillChange]
    public function count()
    {
        return is_array($this->data) ? count($this->data) : 0;
    }
}
