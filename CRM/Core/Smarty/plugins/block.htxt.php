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
 * $Id$
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
 *
 * @return string
 *   the string, translated by gettext
 */
function smarty_block_htxt($params, $text, &$smarty) {
  if ($params['id'] == $smarty->_tpl_vars['id']) {
    $smarty->assign('override_help_text', !empty($params['override']));
    return $text;
  }
  else {
    return NULL;
  }
}
