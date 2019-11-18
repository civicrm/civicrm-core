<?php
/**
 * provide interface for Parser
 */
namespace xKerman\Restricted;

/**
 * Interface for Parser
 */
interface ParserInterface
{
    /**
     * parse given `$source`
     *
     * @param Source $source parser input
     * @return array parse result
     * @throws UnserializeFailedException
     */
    public function parse(Source $source);
}
