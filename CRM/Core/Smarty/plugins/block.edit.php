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
 * CiviCRM's Smarty edit-block plugin
 *
 * Template elements tagged {edit}...{/edit} are hidden unless action is create
 * or update (this facilitates using form templates for read-only display).
 *
 * @package CRM
 * @author Piotr Szotkowski <shot@caltha.pl>
 * @author Michal Mach <mover@artnet.org>
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 */

/**
 * Smarty block function providing edit-only display support
 *
 * @param array $params
 *   Template call's parameters.
 * @param string $text
 *   {edit} block contents from the template.
 * @param CRM_Core_Smarty $smarty
 *   The Smarty object.
 *
 * @return string
 *   the string, translated by gettext
 */
function smarty_block_edit($params, $text, &$smarty) {
  $action = $smarty->_tpl_vars['action'];
  return ($action & 3) ? $text : NULL;
}
