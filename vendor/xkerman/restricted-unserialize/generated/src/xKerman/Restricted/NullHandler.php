<?php

/**
 * Handler to parse PHP serialized null value
 */
class xKerman_Restricted_NullHandler implements xKerman_Restricted_HandlerInterface
{
    /**
     * parse given `$source` as PHP serialized null value
     *
     * @param Source      $source parser input
     * @param string|null $args   null
     * @return array parse result
     * @throws UnserializeFailedException
     */
    public function handle(xKerman_Restricted_Source $source, $args)
    {
        return array($args, $source);
    }
}