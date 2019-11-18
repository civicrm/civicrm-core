<?php
/**
 * handler for PHP serialized boolean
 */
namespace xKerman\Restricted;

/**
 * Handler for PHP serialized boolean
 */
class BooleanHandler implements HandlerInterface
{
    /**
     * parse given `$source` as PHP serialized boolean
     *
     * @param Source      $source parser input
     * @param string|null $args   boolean information
     * @return array
     * @throws UnserializeFailedException
     */
    public function handle(Source $source, $args)
    {
        return array((boolean)$args, $source);
    }
}
