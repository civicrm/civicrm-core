<?php

/**
 * Interface for Handler
 */
interface xKerman_Restricted_HandlerInterface
{
    /**
     * parse given `$source`
     *
     * @param Source      $source parser input
     * @param string|null $args   information for parsing
     * @return array parse result
     * @throws UnserializeFailedException
     */
    public function handle(xKerman_Restricted_Source $source, $args);
}