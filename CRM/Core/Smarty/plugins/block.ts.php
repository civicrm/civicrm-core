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
 * @author Piotr Szotkowski <shot@caltha.pl>
 * @author Michal Mach <mover@artnet.org>
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Smarty block function providing gettext support
 *
 * See CRM_Core_I18n class documentation for details.
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
function smarty_block_ts($params, $text, &$smarty, &$repeat) {
  if (!$repeat) {
    $extensionKey = $smarty->getTemplateVars('extensionKey');
    if (!isset($params['domain']) && $extensionKey) {
      $params['domain'] = is_array($extensionKey) ? $extensionKey : [$extensionKey, NULL];
    }
    return _ts($text, $params);
  }
}
