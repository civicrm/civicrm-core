<?php
/**
 * handler for PHP serialized integer
 */
namespace xKerman\Restricted;

/**
 * Handler for PHP serialized integer
 */
class IntegerHandler implements HandlerInterface
{
    /**
     * parse given `$source` as PHP serialized integer
     *
     * @param Source      $source parser input
     * @param string|null $args   integer value
     * @return array
     * @throws UnserializeFailedException
     */
    public function handle(Source $source, $args)
    {
        return array(intval($args, 10), $source);
    }
}
