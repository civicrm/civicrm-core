<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Required elements validation
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
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @copyright   2001-2011 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 * @version     CVS: $Id$
 * @link        http://pear.php.net/package/HTML_QuickForm
 */

/**
 * Abstract base class for QuickForm validation rules
 */
require_once 'HTML/QuickForm/Rule.php';

/**
 * Required elements validation
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @version     Release: 3.2.16
 * @since       3.2
 */
class HTML_QuickForm_Rule_Required extends HTML_QuickForm_Rule
{
    /**
     * Checks if an element is empty
     *
     * @param     string    $value      Value to check
     * @param     mixed     $options    Not used yet
     * @access    public
     * @return    boolean   true if value is not empty
     */
    function validate($value, $options = null)
    {
        if ( is_array( $value ) ) {
            // check if file type, if so permit empty type
            $fileType =
                array_key_exists( 'name', $value ) &&
                array_key_exists( 'tmp_name', $value );
            // hack to fix required issue with advcheckbox, but in general if any value is present then
            // it should pass required check
            $return = false;
            foreach ( $value as $k => $v ) {
                // dont check type field. Safari3 Beta does not set this
                if ( $fileType && $k == 'type' ) {
                    continue;
                }
                if ( is_array($v) ) {
                    if ( $v ) {
                        $return = true;
                    }
                }
                elseif ( ( string ) $v != '' ) {
                    $return = true;
                }
            }
            return $return;
        } else if ((string)$value == '') {
            return false;
        }
        return true;
    } // end func validate


    function getValidationScript($options = null)
    {
        return array('', "{jsVar} == ''");
    } // end func getValidationScript

} // end class HTML_QuickForm_Rule_Required
?>
