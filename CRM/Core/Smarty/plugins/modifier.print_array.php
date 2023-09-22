<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Smarty print_array modifier plugin
 *
 * Type:     modifier<br>
 * Name:     print_array<br>
 * Purpose:  formats array for output in DAO files and in APIv3 Examples
 * To find where this is used do a grep in Smarty templates for |@print_array
 * @param array|object $var
 * @param int $depth
 * @param int $length
 * @return string
 */
function smarty_modifier_print_array($var, $depth = 0, $length = 40) {

  switch (gettype($var)) {
    case 'array':
      $results = "array(\n";
      foreach ($var as $curr_key => $curr_val) {
        $depth++;
        $results .= str_repeat('  ', ($depth + 1))
        . "'" . $curr_key . "' => "
        . smarty_modifier_print_array($curr_val, $depth, $length) . ",\n";
        $depth--;
      }
      $results .= str_repeat('  ', ($depth + 1)) . ")";
      break;

    case 'object':
      $object_vars = get_object_vars($var);
      $results = get_class($var) . ' Object (' . count($object_vars) . ')';
      foreach ($object_vars as $curr_key => $curr_val) {
        $depth++;
        $results .= str_repeat('', $depth + 1)
        . '->' . $curr_key . ' = '
        . smarty_modifier_debug_print_var($curr_val, $depth, $length);
        $depth--;
      }
      break;

    case 'boolean':
    case 'NULL':
    case 'resource':
      if (TRUE === $var) {
        $results .= 'TRUE';
      }
      elseif (FALSE === $var) {
        $results .= 'FALSE';
      }
      elseif (NULL === $var) {
        $results .= '';
      }
      else {
        $results = $var;
      }
      $results = $results;
      break;

    case 'integer':
    case 'float':
      $results = $var;
      break;

    case 'string':
      if (strlen($var) > $length) {
        $results = substr($var, 0, $length - 3) . '...';
      }
      $results = "'" . $var . "'";
      break;

    case 'unknown type':
    default:
      if (strlen($results) > $length) {
        $results = substr($results, 0, $length - 3) . '...';
      }
      $results = "'" . $var . "'";
  }
  if (empty($var)) {
    if (is_array($var)) {
      $results = "array()";
    }
    elseif ($var === '0' || $var === 0) {
      $results = 0;
    }
    else {
      $results = "''";
    }
  }
  return $results;
}
