<?php

/**
 * Parser for serialized PHP values
 */
class xKerman_Restricted_ExpressionParser implements xKerman_Restricted_ParserInterface
{
    /** @var array $handlers handlers list to use */
    private $handlers;
    /**
     * constructor
     */
    public function __construct()
    {
        $this->handlers = array('N' => new xKerman_Restricted_NullHandler(), 'b' => new xKerman_Restricted_BooleanHandler(), 'i' => new xKerman_Restricted_IntegerHandler(), 'd' => new xKerman_Restricted_FloatHandler(), 's' => new xKerman_Restricted_StringHandler(), 'S' => new xKerman_Restricted_EscapedStringHandler(), 'a' => new xKerman_Restricted_ArrayHandler($this));
    }
    /**
     * parse given `$source` as PHP serialized value
     *
     * @param Source $source parser input
     * @return array
     * @throws UnserializeFailedException
     */
    public function parse(xKerman_Restricted_Source $source)
    {
        $matches = $source->match('/\\G(?|
            (s):([0-9]+):"
            |(i):([+-]?[0-9]+);
            |(a):([0-9]+):{
            |(d):((?:
                [+-]?(?:[0-9]+\\.[0-9]*|[0-9]*\\.[0-9]+|[0-9]+)(?:[eE][+-]?[0-9]+)?)
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