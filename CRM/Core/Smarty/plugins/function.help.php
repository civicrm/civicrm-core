<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 * $Id$
 *
 */

/**
 * Adds inline help
 *
 * @param array $params
 *   The function params.
 * @param CRM_Core_Smarty $smarty
 *   Reference to the smarty object.
 *
 * @return string
 *   the help html to be inserted
 */
function smarty_function_help($params, &$smarty) {
  if (!isset($params['id']) || !isset($smarty->_tpl_vars['config'])) {
    return NULL;
  }

  if (empty($params['file']) && isset($smarty->_tpl_vars['tplFile'])) {
    $params['file'] = $smarty->_tpl_vars['tplFile'];
  }
  elseif (empty($params['file'])) {
    return NULL;
  }

  $params['file'] = str_replace(array('.tpl', '.hlp'), '', $params['file']);

  if (empty($params['title'])) {
    $vars = $smarty->get_template_vars();
    $smarty->assign('id', $params['id'] . '-title');
    $name = trim($smarty->fetch($params['file'] . '.hlp'));
    $additionalTPLFile = $params['file'] . '.extra.hlp';
    if ($smarty->template_exists($additionalTPLFile)) {
      $name .= trim($smarty->fetch($additionalTPLFile));
    }
    // Ensure we didn't change any existing vars CRM-11900
    foreach ($vars as $key => $value) {
      if ($smarty->get_template_vars($key) !== $value) {
        $smarty->assign($key, $value);
      }
    }
  }
  else {
    $name = trim(strip_tags($params['title']));
  }

  $class = "helpicon";
  if (!empty($params['class'])) {
    $class .= " {$params['class']}";
  }

  // Escape for html
  $title = htmlspecialchars(ts('%1 Help', array(1 => $name)));
  // Escape for html and js
  $name = htmlspecialchars(json_encode($name), ENT_QUOTES);

  // Format params to survive being passed through json & the url
  unset($params['text'], $params['title']);
  foreach ($params as &$param) {
    $param = is_bool($param) || is_numeric($param) ? (int) $param : (string) $param;
  }
  return '<a class="' . $class . '" title="' . $title . '" aria-label="' . $title . '" href="#" onclick=\'CRM.help(' . $name . ', ' . json_encode($params) . '); return false;\'>&nbsp;</a>';
}
