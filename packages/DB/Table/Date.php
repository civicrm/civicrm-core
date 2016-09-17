<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Generic date handling class for DB_Table.
 * 
 * Stripped down to two essential methods specially for DB_Table from the
 * PEAR Date package by Paul M. Jones <pmjones@php.net>.
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
 * @author   Baba Buehler <baba@babaz.com>
 * @author   Pierre-Alain Joye <pajoye@php.net>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  CVS: $Id: Date.php,v 1.3 2007/12/13 16:52:14 wiesemann Exp $
 * @link     http://pear.php.net/package/DB_Table
 */

/**
 * Generic date handling class for DB_Table.
 *
 * @category Database
 * @package  DB_Table
 * @author   Baba Buehler <baba@babaz.com>
 * @author   Pierre-Alain Joye <pajoye@php.net>
 * @version  Release: 1.5.6
 * @link     http://pear.php.net/package/DB_Table
 */
class DB_Table_Date {
	
    /**
     * the year
     * @var int
     */
    var $year;
    /**
     * the month
     * @var int
     */
    var $month;
    /**
     * the day
     * @var int
     */
    var $day;
    /**
     * the hour
     * @var int
     */
    var $hour;
    /**
     * the minute
     * @var int
     */
    var $minute;
    /**
     * the second
     * @var int
     */
    var $second;
    /**
     * the parts of a second
     * @var float
     */
    var $partsecond;
    
    /**
     * Constructor
     *
     * Creates a new DB_Table_Date Object. The date should be near to
     * ISO 8601 format.
     *
     * @access public
     * @param string $date A date in ISO 8601 format.
     */
    function __construct($date)
    {
		// This regex is very loose and accepts almost any butchered
		// format you could throw at it.  e.g. 2003-10-07 19:45:15 and
		// 2003-10071945:15 are the same thing in the eyes of this
		// regex, even though the latter is not a valid ISO 8601 date.
		preg_match('/^(\d{4})-?(\d{2})-?(\d{2})([T\s]?(\d{2}):?(\d{2}):?(\d{2})(\.\d+)?(Z|[\+\-]\d{2}:?\d{2})?)?$/i', $date, $regs);
		$this->year       = $regs[1];
		$this->month      = $regs[2];
		$this->day        = $regs[3];
		$this->hour       = isset($regs[5])?$regs[5]:0;
		$this->minute     = isset($regs[6])?$regs[6]:0;
		$this->second     = isset($regs[7])?$regs[7]:0;
		$this->partsecond = isset($regs[8])?(float)$regs[8]:(float)0;

		// if an offset is defined, convert time to UTC
		// Date currently can't set a timezone only by offset,
		// so it has to store it as UTC
		if (isset($regs[9])) {
			$this->toUTCbyOffset($regs[9]);
		}
    }
    
    
    /**
     *  Date pretty printing, similar to strftime()
     *
     *  Formats the date in the given format, much like
     *  strftime().  Most strftime() options are supported.<br><br>
     *
     *  formatting options:<br><br>
     *
     *  <code>%Y  </code>  year as decimal including century (range 0000 to 9999) <br>
     *  <code>%m  </code>  month as decimal number (range 01 to 12) <br>
     *  <code>%d  </code>  day of month (range 00 to 31) <br>
     *  <code>%H  </code>  hour as decimal number (00 to 23) <br>
     *  <code>%M  </code>  minute as a decimal number (00 to 59) <br>
     *  <code>%S  </code>  seconds as a decimal number (00 to 59) <br>
     *  <code>%%  </code>  literal '%' <br>
     * <br>
     *
     * @access public
     * @param string format the format string for returned date/time
     * @return string date/time in given format
     */
    function format($format)
    {
        $output = "";

        for($strpos = 0; $strpos < strlen($format); $strpos++) {
            $char = substr($format,$strpos,1);
            if ($char == "%") {
                $nextchar = substr($format,$strpos + 1,1);
                switch ($nextchar) {
                case "Y":
                    $output .= $this->year;
                    break;
                case "m":
                    $output .= sprintf("%02d",$this->month);
                    break;
                case "d":
                    $output .= sprintf("%02d",$this->day);
                    break;
                case "H":
                    $output .= sprintf("%02d", $this->hour);
                    break;
                case "M":
                    $output .= sprintf("%02d",$this->minute);
                    break;
                case "S":
                    $output .= sprintf("%02d", $this->second);
                    break;
                default:
                    $output .= $char.$nextchar;
                }
                $strpos++;
            } else {
                $output .= $char;
            }
        }
        return $output;

    }
}

?>
