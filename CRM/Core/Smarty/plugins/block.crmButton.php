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
 * $Id$
 *
 */

/**
 * Generate the html for a button-style link
 *
 * @param array $params
 *   Params of the {crmButton} call.
 * @param string $text
 *   Contents of block.
 * @param CRM_Core_Smarty $smarty
 *   The Smarty object.
 *
 * @return string
 *   The generated html.
 */
function smarty_block_crmButton($params, $text, &$smarty) {
  // Generate url (pass 'html' param as false to avoid double-encode by htmlAttributes)
  if (empty($params['href'])) {
    $params['href'] = CRM_Utils_System::crmURL($params + ['h' => FALSE]);
  }
  // Always add class 'button' - fixme probably should be crm-button
  $params['class'] = empty($params['class']) ? 'button' : 'button ' . $params['class'];
  // Any FA icon works
  $icon = CRM_Utils_Array::value('icon', $params, 'pencil');
  // All other params are treated as html attributes
  CRM_Utils_Array::remove($params, 'icon', 'p', 'q', 'a', 'f', 'h', 'fb', 'fe');
  $attributes = CRM_Utils_String::htmlAttributes($params);
  return "<a $attributes><span><i class='crm-i fa-$icon'></i>&nbsp; $text</span></a>";
}
