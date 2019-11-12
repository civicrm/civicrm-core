<?php
/**
 * handler for PHP serialized array
 */
namespace xKerman\Restricted;

/**
 * Handler for PHP serialiezed array
 */
class ArrayHandler implements HandlerInterface
{
    /** @var ParserInterface $expressionParser parser for unserialize expression */
    private $expressionParser;

    /** @var integer */
    const CLOSE_BRACE_LENGTH = 1;

    /**
     * constructor
     *
     * @param ParserInterface $expressionParser parser for unserialize expression
     */
    public function __construct(ParserInterface $expressionParser)
    {
        $this->expressionParser = $expressionParser;
    }

    /**
     * parse given `$source` as PHP serialized array
     *
     * @param Source      $source parser input
     * @param string|null $args   array length
     * @return array
     * @throws UnserializeFailedException
     */
    public function handle(Source $source, $args)
    {
        $length = intval($args, 10);

        $result = array();
        for ($i = 0; $i < $length; ++$i) {
            list($key, $source) = $this->parseKey($source);
            list($value, $source) = $this->expressionParser->parse($source);
            $result[$key] = $value;
        }

        $source->consume('}', self::CLOSE_BRACE_LENGTH);
        return array($result, $source);
    }

    /**
     * parse given `$source` as array key (s.t. integer|string)
     *
     * @param Source $source input
     * @return array
     * @throws UnserializeFailedException
     */
    private function parseKey($source)
    {
        list($key, $source) = $this->expressionParser->parse($source);
        if (!is_integer($key) && !is_string($key)) {
            return $source->triggerError();
        }
        return array($key, $source);
    }
}
