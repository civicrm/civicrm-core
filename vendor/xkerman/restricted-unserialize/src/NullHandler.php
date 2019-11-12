<?php
/**
 * handler for PHP null value
 */
namespace xKerman\Restricted;

/**
 * Handler to parse PHP serialized null value
 */
class NullHandler implements HandlerInterface
{
    /**
     * parse given `$source` as PHP serialized null value
     *
     * @param Source      $source parser input
     * @param string|null $args   null
     * @return array parse result
     * @throws UnserializeFailedException
     */
    public function handle(Source $source, $args)
    {
        return array($args, $source);
    }
}
