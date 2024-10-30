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
 * CiviCRM's Smarty gettext plugin
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Smarty block function providing support for
 * CiviCRM's helptext mechanism
 *
 * @param array $params
 *   Template call's parameters.
 * @param string $text
 *   {ts} block contents from the template.
 * @param CRM_Core_Smarty $smarty
 *   The Smarty object.
 * @param bool $repeat
 *   Repeat is true for the opening tag, false for the closing tag
 *
 * @return string|null
 *   the string, translated by gettext
 */
function smarty_block_htxt($params, $text, $smarty, &$repeat) {
  if (!$repeat && $params['id'] == $smarty->getTemplateVars('id')) {
    $smarty->assign('override_help_text', !empty($params['override']));
    return $text;
  }
  return NULL;
}
