<?php

/**
 * Handler for PHP serialized integer
 */
class xKerman_Restricted_IntegerHandler implements xKerman_Restricted_HandlerInterface
{
    /**
     * parse given `$source` as PHP serialized integer
     *
     * @param Source      $source parser input
     * @param string|null $args   integer value
     * @return array
     * @throws UnserializeFailedException
     */
    public function handle(xKerman_Restricted_Source $source, $args)
    {
        return array(intval($args, 10), $source);
    }
}