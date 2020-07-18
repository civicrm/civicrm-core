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
 * Grab the button type from a passed button element 'name' by checking for reserved QF button type strings
 *
 * @param string $btnName
 *
 * @return string
 *   button type, one of: 'upload', 'next', 'back', 'cancel', 'refresh'
 *                                      'submit', 'done', 'display', 'jump' 'process'
 */
function smarty_modifier_crmBtnType($btnName) {
  // split the string into 5 or more
  // button name are typically: '_qf_Contact_refresh' OR '_qf_Contact_refresh_dedupe'
  // button type is always the 3rd element
  // note the first _
  $substr = CRM_Utils_System::explode('_', $btnName, 5);

  return $substr[3];
}
