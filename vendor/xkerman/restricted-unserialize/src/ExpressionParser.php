<?php
/**
 * parser for serialized expression
 */
namespace xKerman\Restricted;

/**
 * Parser for serialized PHP values
 */
class ExpressionParser implements ParserInterface
{
    /** @var array $handlers handlers list to use */
    private $handlers;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->handlers = array(
            'N' => new NullHandler(),
            'b' => new BooleanHandler(),
            'i' => new IntegerHandler(),
            'd' => new FloatHandler(),
            's' => new StringHandler(),
            'S' => new EscapedStringHandler(),
            'a' => new ArrayHandler($this),
        );
    }

    /**
     * parse given `$source` as PHP serialized value
     *
     * @param Source $source parser input
     * @return array
     * @throws UnserializeFailedException
     */
    public function parse(Source $source)
    {
        $matches = $source->match('/\G(?|
            (s):([0-9]+):"
            |(i):([+-]?[0-9]+);
            |(a):([0-9]+):{
            |(d):((?:
                [+-]?(?:[0-9]+\.[0-9]*|[0-9]*\.[0-9]+|[0-9]+)(?:[eE][+-]?[0-9]+)?)
                |-?INF
                |NAN);
            |(b):([01]);
            |(N);
            |(S):([0-9]+):"
        )/x');
        $tag = $matches[0];
        $args = isset($matches[1]) ? $matches[1] : null;
        return $this->handlers[$tag]->handle($source, $args);
    }
}
