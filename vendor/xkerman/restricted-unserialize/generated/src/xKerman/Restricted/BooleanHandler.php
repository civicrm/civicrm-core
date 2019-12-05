<?php

/**
 * Handler for PHP serialized boolean
 */
class xKerman_Restricted_BooleanHandler implements xKerman_Restricted_HandlerInterface
{
    /**
     * parse given `$source` as PHP serialized boolean
     *
     * @param Source      $source parser input
     * @param string|null $args   boolean information
     * @return array
     * @throws UnserializeFailedException
     */
    public function handle(xKerman_Restricted_Source $source, $args)
    {
        return array((bool) $args, $source);
    }
}