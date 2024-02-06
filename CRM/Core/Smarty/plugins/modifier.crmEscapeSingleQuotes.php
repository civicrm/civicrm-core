<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty escape single quotes.
 *
 * This is hopefully a transitional plugin to get us past conflicts while
 * we migrate from Smarty2 to Smarty 3. Potentially after that we can use
 * {$field.comment|replace:"'":"\'"} again...
 *
 * @internal - may disappear again!!!
 * @param string $string
 *
 * @return string
 */
function smarty_modifier_crmEscapeSingleQuotes($string) {
  return str_replace("'", "\'", $string);
}
