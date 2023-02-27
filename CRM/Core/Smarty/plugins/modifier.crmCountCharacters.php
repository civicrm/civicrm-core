<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty count_characters modifier plugin
 *
 * Type:     modifier<br>
 * Name:     crmCountCharacteres<br>
 * Purpose:  count the number of characters in a text with handling for NULL values
 * @link http://smarty.php.net/manual/en/language.modifier.count.characters.php
 *          count_characters (Smarty online manual)
 * @author   Monte Ohrt <monte at ohrt dot com>
 * @param string $string
 * @param boolean $include_spaces include whitespace in the character count
 * @return integer
 */
function smarty_modifier_crmCountCharacters($string, $include_spaces = FALSE) {
  if (is_null($string)) {
    return 0;
  }

  if ($include_spaces) {
    return(strlen($string));
  }

  return preg_match_all("/[^\s]/", $string, $match);
}

/* vim: set expandtab: */
