<?php

/**
 * Interface for Parser
 */
interface xKerman_Restricted_ParserInterface
{
    /**
     * parse given `$source`
     *
     * @param Source $source parser input
     * @return array parse result
     * @throws UnserializeFailedException
     */
    public function parse(xKerman_Restricted_Source $source);
}