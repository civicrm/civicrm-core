<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * The action for a 'back' button of wizard-type multipage form.
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
 * @package     HTML_QuickForm_Controller
 * @author      Alexey Borzov <avb@php.net>
 * @copyright   2003-2007 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 * @version     CVS: $Id: Back.php,v 1.6 2007/05/18 09:34:18 avb Exp $
 * @link        http://pear.php.net/package/HTML_QuickForm_Controller
 */

/**
 * Class representing an action to perform on HTTP request.
 */
require_once 'HTML/QuickForm/Action.php';

/**
 * The action for a 'back' button of wizard-type multipage form.
 *
 * @category    HTML
 * @package     HTML_QuickForm_Controller
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 1.0.9
 */
class HTML_QuickForm_Action_Back extends HTML_QuickForm_Action
{
    function perform(&$page, $actionName)
    {
        // save the form values and validation status to the session
        $page->isFormBuilt() or $page->buildForm();
        $pageName =  $page->getAttribute('id');
        $data     =& $page->controller->container();
        $data['values'][$pageName] = $page->exportValues();
        if (!$page->controller->isModal()) {
            if (PEAR::isError($valid = $page->validate())) {
                return $valid;
            }
            $data['valid'][$pageName] = $valid;
        }

        // get the previous page and go to it
        // we don't check validation status here, 'jump' handler should
        if (null === ($prevName = $page->controller->getPrevName($pageName))) {
            return $page->handle('jump');
        } else {
            $prev =& $page->controller->getPage($prevName);
            return $prev->handle('jump');
        }
    }
}

?>
