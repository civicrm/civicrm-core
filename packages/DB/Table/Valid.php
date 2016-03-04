<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * DB_Table_Valid validates values against DB_Table column types.
 * 
 * PHP versions 4 and 5
 *
 * LICENSE:
 * 
 * Copyright (c) 1997-2007, Paul M. Jones <pmjones@php.net>
 *                          David C. Morse <morse@php.net>
 *                          Mark Wiesemann <wiesemann@php.net>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the 
 *      documentation and/or other materials provided with the distribution.
 *    * The names of the authors may not be used to endorse or promote products 
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category Database
 * @package  DB_Table
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   David C. Morse <morse@php.net>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  CVS: $Id: Valid.php,v 1.10 2007/12/13 16:52:15 wiesemann Exp $
 * @link     http://pear.php.net/package/DB_Table
 */

/**
* DB_Table class for constants and other globals.
*/
require_once 'DB/Table.php';


/**
* validation ranges for integers
*/
if (! isset($GLOBALS['_DB_TABLE']['valid'])) {
    $GLOBALS['_DB_TABLE']['valid'] = array(
        'smallint' => array(pow(-2, 15), pow(+2, 15) - 1),
        'integer' => array(pow(-2, 31), pow(+2, 31) - 1),
        'bigint' => array(pow(-2, 63), pow(+2, 63) - 1)
    );
}


/**
 * DB_Table_Valid validates values against DB_Table column types.
 * 
 * @category Database
 * @package  DB_Table
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   David C. Morse <morse@php.net>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @version  Release: 1.5.6
 * @link     http://pear.php.net/package/DB_Table
 */

class DB_Table_Valid {
    
    /**
    * 
    * Check if a value validates against the 'boolean' data type.
    * 
    * @static
    * 
    * @access public
    * 
    * @param mixed $value The value to validate.
    * 
    * @return boolean True if the value is valid for the data type, false
    * if not.
    * 
    */
    
    function isBoolean($value)
    {
        if ($value === true || $value === false) {
            return true;
        } elseif (is_numeric($value) && ($value == 0 || $value == 1)) {
            return true;
        } else {
            return false;
        }
    }
    
    
    /**
    * 
    * Check if a value validates against the 'char' and 'varchar' data type.
    * 
    * We allow most anything here, only checking that the length is in range.
    * 
    * @static
    * 
    * @access public
    * 
    * @param mixed $value The value to validate.
    * 
    * @return boolean True if the value is valid for the data type, false
    * if not.
    * 
    */
    
    function isChar($value, $colsize)
    {
    	$is_scalar = (! is_array($value) && ! is_object($value));
        $in_range = (strlen($value) <= $colsize);
        return $is_scalar && $in_range;
    }
    
    
    /**
    * 
    * Check if a value validates against the 'smallint' data type.
    * 
    * @static
    * 
    * @access public
    * 
    * @param mixed $value The value to validate.
    * 
    * @return boolean True if the value is valid for the data type, false
    * if not.
    * 
    */
    
    function isSmallint($value)
    {
        return is_integer($value) &&
            ($value >= $GLOBALS['_DB_TABLE']['valid']['smallint'][0]) &&
            ($value <= $GLOBALS['_DB_TABLE']['valid']['smallint'][1]);
    }
    
    
    /**
    * 
    * Check if a value validates against the 'integer' data type.
    * 
    * @static
    * 
    * @access public
    * 
    * @param mixed $value The value to validate.
    * 
    * @return boolean True if the value is valid for the data type, false
    * if not.
    * 
    */
    
    function isInteger($value)
    {
        return is_integer($value) &&
            ($value >= $GLOBALS['_DB_TABLE']['valid']['integer'][0]) &&
            ($value <= $GLOBALS['_DB_TABLE']['valid']['integer'][1]);
    }
    
    
    /**
    * 
    * Check if a value validates against the 'bigint' data type.
    * 
    * @static
    * 
    * @access public
    * 
    * @param mixed $value The value to validate.
    * 
    * @return boolean True if the value is valid for the data type, false
    * if not.
    * 
    */
    
    function isBigint($value)
    {
        return is_integer($value) &&
            ($value >= $GLOBALS['_DB_TABLE']['valid']['bigint'][0]) &&
            ($value <= $GLOBALS['_DB_TABLE']['valid']['bigint'][1]);
    }
    
    
    /**
    * 
    * Check if a value validates against the 'decimal' data type.
    * 
    * For the column defined "DECIMAL(5,2)" standard SQL requires that
    * the column be able to store any value with 5 digits and 2
    * decimals. In this case, therefore, the range of values that can be
    * stored in the column is from -999.99 to 999.99.  DB_Table attempts
    * to enforce this behavior regardless of the RDBMS backend behavior.
    * 
    * @static
    * 
    * @access public
    * 
    * @param mixed $value The value to validate.
    * 
    * @param string $colsize The 'size' to use for validation (to make
    * sure of min/max and decimal places).
    * 
    * @param string $colscope The 'scope' to use for validation (to make
    * sure of min/max and decimal places).
    * 
    * @return boolean True if the value is valid for the data type, false
    * if not.
    * 
    */
    
    function isDecimal($value, $colsize, $colscope)
    {
        if (! is_numeric($value)) {
            return false;
        }
        
        // maximum number of digits allowed to the left
        // and right of the decimal point.
        $right_max = $colscope;
        $left_max = $colsize - $colscope;
        
        // ignore negative signs in all validation
        $value = str_replace('-', '', $value);
        
        // find the decimal point, then get the left
        // and right portions.
        $pos = strpos($value, '.');
        if ($pos === false) {
            $left = $value;
            $right = '';
        } else {
            $left = substr($value, 0, $pos);
            $right = substr($value, $pos+1);
        }
        
        // how long are the left and right portions?
        $left_len = strlen($left);
        $right_len = strlen($right);
        
        // do the portions exceed their maxes?
        if ($left_len > $left_max ||
            $right_len > $right_max) {
            // one or the other exceeds the max lengths
            return false;
        } else {
            // both are within parameters
            return true;
        }
    }
    
    
    /**
    * 
    * Check if a value validates against the 'single' data type.
    * 
    * @static
    * 
    * @access public
    * 
    * @param mixed $value The value to validate.
    * 
    * @return boolean True if the value is valid for the data type, false
    * if not.
    * 
    */
    
    function isSingle($value)
    {
        return is_float($value);
    }
    
    
    /**
    * 
    * Check if a value validates against the 'double' data type.
    * 
    * @static
    * 
    * @access public
    * 
    * @param mixed $value The value to validate.
    * 
    * @return boolean True if the value is valid for the data type, false
    * if not.
    * 
    */
    
    function isDouble($value)
    {
        return is_float($value);
    }
    
    
    /**
    * 
    * Check if a value validates against the 'time' data type.
    * 
    * @static
    * 
    * @access public
    * 
    * @param mixed $value The value to validate.
    * 
    * @return boolean True if the value is valid for the data type, false
    * if not.
    * 
    */
    
    function isTime($value)
    {
        // hh:ii:ss
        // 01234567
        $h  = substr($value, 0, 2);
        $s1 = substr($value, 2, 1);
        $i  = substr($value, 3, 2);
        $s2 = substr($value, 5, 1);
        $s  = substr($value, 6, 2);
        
        // time check
        if (strlen($value) != 8 ||
            ! is_numeric($h) || $h < 0 || $h > 23  ||
            $s1 != ':' ||
            ! is_numeric($i) || $i < 0 || $i > 59 ||
            $s2 != ':' ||
            ! is_numeric($s) || $s < 0 || $s > 59) {
            
            return false;
            
        } else {
        
            return true;
            
        }
    }
    
    
    /**
    * 
    * Check if a value validates against the 'date' data type.
    * 
    * @static
    * 
    * @access public
    * 
    * @param mixed $value The value to validate.
    * 
    * @return boolean True if the value is valid for the data type, false
    * if not.
    * 
    */
    
    function isDate($value)
    {
        // yyyy-mm-dd
        // 0123456789
        $y  = substr($value, 0, 4);
        $s1 = substr($value, 4, 1);
        $m  = substr($value, 5, 2);
        $s2 = substr($value, 7, 1);
        $d  = substr($value, 8, 2);
        
        // date check
        if (strlen($value) != 10 || $s1 != '-' || $s2 != '-' ||
            ! checkdate($m, $d, $y)) {
            
            return false;
            
        } else {
        
            return true;
            
        }
    }
    
    
    /**
    * 
    * Check if a value validates against the 'timestamp' data type.
    * 
    * @static
    * 
    * @access public
    * 
    * @param mixed $value The value to validate.
    * 
    * @return boolean True if the value is valid for the data type, false
    * if not.
    * 
    */
    
    function isTimestamp($value)
    {
        // yyyy-mm-dd hh:ii:ss
        // 0123456789012345678
        $date = substr($value, 0, 10);
        $sep = substr($value, 10, 1);
        $time = substr($value, 11, 8);
        
        if (strlen($value) != 19 || $sep != ' ' ||
            ! DB_Table_Valid::isDate($date) ||
            ! DB_Table_Valid::isTime($time)) {
            
            return false;
            
        } else {
        
            return true;
            
        }
    }
    
    
    /**
    * 
    * Check if a value validates against the 'clob' data type.
    * 
    * @static
    * 
    * @access public
    * 
    * @param mixed $value The value to validate.
    * 
    * @return boolean True if the value is valid for the data type, false
    * if not.
    * 
    */
    
    function isClob($value)
    {
        return is_string($value);
    }
}

?>
