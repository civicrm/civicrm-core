<?php
/**
 * provide interface for Handler
 */
namespace xKerman\Restricted;

/**
 * Interface for Handler
 */
interface HandlerInterface
{
    /**
     * parse given `$source`
     *
     * @param Source      $source parser input
     * @param string|null $args   information for parsing
     * @return array parse result
     * @throws UnserializeFailedException
     */
    public function handle(Source $source, $args);
}
