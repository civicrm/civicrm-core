<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Email validation rule
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
 * Email validation rule
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @version     Release: 3.2.16
 * @since       3.2
 */
class HTML_QuickForm_Rule_Email extends HTML_QuickForm_Rule
{

    /**
     * Compatibility layer for PHP versions running ICU 4.4, as the constant INTL_IDNA_VARIANT_UTS46
     * is only available as of ICU 4.6.
     *
     * Please note: Once PHP 7.4 is the minimum requirement, this method will vanish without further notice
     * as it is recommended to use the native method instead, when working against a clean environment.
     *
     * @param string $part.
     * @return string|bool
     */
    private static function idn_to_ascii($part)
    {
        if (defined('INTL_IDNA_VARIANT_UTS46')) {
            return idn_to_ascii($part, 0, INTL_IDNA_VARIANT_UTS46);
        }
        return idn_to_ascii($part);
    }

    // switching to a better regex as per CRM-40
    // var $regex = '/^((\"[^\"\f\n\r\t\v\b]+\")|([\w\!\#\$\%\&\'\*\+\-\~\/\^\`\|\{\}]+(\.[\w\!\#\$\%\&\'\*\+\-\~\/\^\`\|\{\}]+)*))@((\[(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))\])|(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))|((([A-Za-z0-9\-])+\.)+[A-Za-z\-]+))$/';
    var $regex = '/^([a-zA-Z0-9&_?\/`!|#*$^%=~{}+\'-]+|"([\x00-\x0C\x0E-\x21\x23-\x5B\x5D-\x7F]|\\[\x00-\x7F])*")(\.([a-zA-Z0-9&_?\/`!|#*$^%=~{}+\'-]+|"([\x00-\x0C\x0E-\x21\x23-\x5B\x5D-\x7F]|\\[\x00-\x7F])*"))*@([a-zA-Z0-9&_?\/`!|#*$^%=~{}+\'-]+|\[([\x00-\x0C\x0E-\x5A\x5E-\x7F]|\\[\x00-\x7F])*\])(\.([a-zA-Z0-9&_?\/`!|#*$^%=~{}+\'-]+|\[([\x00-\x0C\x0E-\x5A\x5E-\x7F]|\\[\x00-\x7F])*\]))*$/';

    /**
     * Validates an email address
     *
     * @param     string    $email          Email address
     * @param     boolean   $checkDomain    True if dns check should be performed
     * @access    public
     * @return    boolean   true if email is valid
     */
    function validate($email, $checkDomain = false)
    {
        if (function_exists('idn_to_ascii')) {
          if ($parts = explode('@', $email)) {
            if (sizeof($parts) == 2) {
              foreach ($parts as &$part) {
                $part = self::idn_to_ascii($part);
              }
              $email = implode('@', $parts);
            }
          }
        }

        // Fix for bug #10799: add 'D' modifier to regex
        if (preg_match($this->regex . 'D', $email)) {
            if ($checkDomain && function_exists('checkdnsrr')) {
                $tokens = explode('@', $email);
                if (checkdnsrr($tokens[1], 'MX') || checkdnsrr($tokens[1], 'A')) {
                    return true;
                }
                return false;
            }
            return true;
        }
        return false;
    } // end func validate


    function getValidationScript($options = null)
    {
        return array("  var regex = " . $this->regex . ";\n", "{jsVar} != '' && !regex.test({jsVar})");
    } // end func getValidationScript

} // end class HTML_QuickForm_Rule_Email
?>
