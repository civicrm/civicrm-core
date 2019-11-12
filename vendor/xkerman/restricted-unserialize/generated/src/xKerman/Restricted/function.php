<?php

/**
 * parse serialized string and return result
 *
 * @param string $str serialized string
 * @return mixed
 * @throws UnserializeFailedException
 */
function xKerman_Restricted_unserialize($str)
{
    $source = new xKerman_Restricted_Source($str);
    $parser = new xKerman_Restricted_ExpressionParser();
    list($result, ) = $parser->parse($source);
    return $result;
}