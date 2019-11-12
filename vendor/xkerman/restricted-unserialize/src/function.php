<?php
/**
 * provide `unserialize` function that is safe for PHP Object Injection
 */
namespace xKerman\Restricted;

/**
 * parse serialized string and return result
 *
 * @param string $str serialized string
 * @return mixed
 * @throws UnserializeFailedException
 */
function unserialize($str)
{
    $source = new Source($str);
    $parser = new ExpressionParser();
    list($result,) = $parser->parse($source);
    return $result;
}
