<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * A pseudo-element used for adding headers to form
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Alexey Borzov <avb@php.net>
 * @copyright   2001-2011 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 * @version     CVS: $Id$
 * @link        http://pear.php.net/package/HTML_QuickForm
 */

/**
 * HTML class for static data
 */
require_once 'HTML/QuickForm/static.php';

/**
 * A pseudo-element used for adding headers to form
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 3.2.16
 * @since       3.0
 */
class HTML_QuickForm_header extends HTML_QuickForm_static
{
    // {{{ constructor

   /**
    * Class constructor
    *
    * @param string $elementName    Header name
    * @param string $text           Header text
    * @access public
    * @return void
    */
    function __construct($elementName = null, $text = null)
    {
        parent::__construct($elementName, null, $text);
        $this->_type = 'header';
    }

    // }}}
    // {{{ accept()

   /**
    * Accepts a renderer
    *
    * @param HTML_QuickForm_Renderer    renderer object
    * @param bool $sc1                  unused, for signature compatibility
    * @param bool $sc2                  unused, for signature compatibility
    * @access public
    * @return void
    */
    function accept(&$renderer, $sc1 = false, $sc2 = null)
    {
        $renderer->renderHeader($this);
    } // end func accept

    // }}}

} //end class HTML_QuickForm_header
?>
