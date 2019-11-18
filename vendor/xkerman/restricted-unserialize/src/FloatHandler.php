<?php
/**
 * handler for PHP serialized float number
 */
namespace xKerman\Restricted;

/**
 * Handler for PHP serialized float number
 */
class FloatHandler implements HandlerInterface
{
    /** @var array $mapping parser result mapping */
    private $mapping;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->mapping = array(
            'INF'  => INF,
            '-INF' => -INF,
            'NAN'  => NAN,
        );
    }

    /**
     * parse given `$source` as PHP serialized float number
     *
     * @param Source      $source parser input
     * @param string|null $args   float value
     * @return array
     * @throws UnserializeFailedException
     */
    public function handle(Source $source, $args)
    {
        if (array_key_exists($args, $this->mapping)) {
            return array($this->mapping[$args], $source);
        }
        return array(floatval($args), $source);
    }
}
