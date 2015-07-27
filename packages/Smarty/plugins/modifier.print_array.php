<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty debug_print_var modifier plugin
 *
 * Type:     modifier<br>
 * Name:     debug_print_var<br>
 * Purpose:  formats variable contents for display in the console
 * @link http://smarty.php.net/manual/en/language.modifier.debug.print.var.php
 *          debug_print_var (Smarty online manual)
 * @author   Monte Ohrt <monte at ohrt dot com>
 * @param array|object
 * @param integer
 * @param integer
 * @return string
 */
function smarty_modifier_print_array($var, $depth = 0, $length = 40)
{

    require_once 'modifier.debug_print_var.php';
    switch (gettype($var)) {
        case 'array' :
            $results = "array(\n";
            foreach ($var as $curr_key => $curr_val) {
                $depth++;
                $results .= str_repeat('  ', ($depth + 1))
                    . "'" . $curr_key . "' => "
                    . smarty_modifier_print_array($curr_val, $depth, $length). ",\n";
                $depth--;
            }
            $results .= str_repeat('  ', ($depth + 1)) . ")";
            break;

        case 'object' :
            $object_vars = get_object_vars($var);
            $results =  get_class($var) . ' Object (' . count($object_vars) . ')';
            foreach ($object_vars as $curr_key => $curr_val) {
                $depth++;
                $results .=  str_repeat('', $depth + 1)
                    . '->' . $curr_key . ' = '
                    . smarty_modifier_debug_print_var($curr_val, $depth, $length);
                $depth--;
            }
            break;
        case 'boolean' :
        case 'NULL' :
        case 'resource' :

            if (true === $var) {
                $results .= 'TRUE';
            } elseif (false === $var) {
                $results .= 'FALSE';
            } elseif (null === $var) {
                $results .= '';
            } else {
                $results =  $var;
            }
            $results =  $results ;
            break;
        case 'integer' :
        case 'float' :
            $results = $var;
            break;
        case 'string' :
             if (strlen($var) > $length ) {
                $results = substr($var, 0, $length - 3) . '...';
            }
            $results =  "'" . $var  . "'";
            break;
        case 'unknown type' :
        default :

            if (strlen($results) > $length ) {
                $results = substr($results, 0, $length - 3) . '...';
            }
            $results = "'" . $var  . "'";
    }
    if (empty($var)){
      if(is_array($var)){
        $results = "array()";

      }elseif ($var === '0' || $var === 0){
        $results = 0;
      }else{
        $results = "''";
      }
    }
    return $results;
}

/* vim: set expandtab: */

?>
