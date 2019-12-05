<?php
/**
 * handler for serialized string
 */
namespace xKerman\Restricted;

/**
 * Handler class for parse serialized PHP stirng
 */
class StringHandler implements HandlerInterface
{
    /** @var integer */
    const CLOSE_STRING_LENGTH = 2;

    /**
     * parse give `$source` as PHP serialized string
     *
     * @param Source      $source parser input
     * @param string|null $args   string length
     * @return array parser result
     * @throws UnserializeFailedException
     */
    public function handle(Source $source, $args)
    {
        $length = intval($args, 10);
        $result = $source->read($length);
        $source->consume('";', self::CLOSE_STRING_LENGTH);

        return array($result, $source);
    }
}
