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
 * @author Donald Lobo <lobo@civicrm.org>
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 */

/**
 * Smarty block function providing serialization support
 *
 * See CRM_Core_I18n class documentation for details.
 *
 * @param array $params
 *   Template call's parameters.
 * @param string $text
 *   {serialize} block contents from the template.
 * @param CRM_Core_Smarty $smarty
 *   The Smarty object.
 *
 * @return string
 *   the string, translated by gettext
 */
function smarty_block_serialize($params, $text, &$smarty) {
  return serialize($text);
}
