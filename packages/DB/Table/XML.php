<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * A few simple static methods for writing XML
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
 * @author   David C. Morse <morse@php.net>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  CVS: $Id: XML.php,v 1.2 2007/12/13 16:52:15 wiesemann Exp $
 * @link     http://pear.php.net/package/DB_Table
 */

/**
 * Class DB_Table_XML contains a few simple static methods for writing XML
 * 
 * @category Database
 * @package  DB_Table
 * @author   David C. Morse <morse@php.net>
 * @version  Release: 1.5.6
 * @link     http://pear.php.net/package/DB_Table
 */

class DB_Table_XML
{
    
    /**
     * Returns XML closing tag <tag>, increases $indent by 3 spaces
     *
     * @static
     * @param string $tag    XML element tag name
     * @param string $indent current indentation, string of spaces
     * @return string XML opening tag
     * @access public
     */
    function openTag($tag, &$indent)
    {
        $old_indent = $indent;
        $indent = $indent . '   ';
        return $old_indent . "<$tag>";
    }


    /**
     * Returns XML closing tag </tag>, decreases $indent by 3 spaces
     *
     * @static
     * @param string $tag    XML element tag name
     * @param string $indent current indentation, string of spaces
     * @return string XML closing tag
     * @access public
     */
    function closeTag($tag, &$indent)
    {
        $indent = substr($indent, 0, -3);
        return $indent . "</$tag>";
    }


    /**
     * Returns string single line XML element <tag>text</tag>
     *
     * @static
     * @param string $tag    XML element tag name
     * @param string $text   element contents
     * @param string $indent current indentation, string of spaces
     * @return string single-line XML element
     * @access public
     */
    function lineElement($tag, $text, $indent)
    {
        return $indent . "<$tag>$text</$tag>";
    }

}
?>
