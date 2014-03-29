<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * Adds inline help
 *
 * @param array  $params the function params
 * @param object $smarty reference to the smarty object
 *
 * @return string the help html to be inserted
 * @access public
 */
function smarty_function_help($params, &$smarty) {
  if (!isset($params['id']) || !isset($smarty->_tpl_vars['config'])) {
    return;
  }

  if (empty($params['file']) && isset($smarty->_tpl_vars['tplFile'])) {
    $params['file'] = $smarty->_tpl_vars['tplFile'];
  }
  elseif (empty($params['file'])) {
    return NULL;
  }

  $params['file'] = str_replace(array('.tpl', '.hlp'), '', $params['file']);

  if (empty($params['title'])) {
    // Avod overwriting existing vars CRM-11900
    $oldID = $smarty->get_template_vars('id');
    $smarty->assign('id', $params['id'] . '-title');
    $name = trim($smarty->fetch($params['file'] . '.hlp'));
    $additionalTPLFile = $params['file'] . '.extra.hlp';
    if ($smarty->template_exists($additionalTPLFile)) {
      $name .= trim($smarty->fetch($additionalTPLFile));
    }
    $smarty->assign('id', $oldID);
  }
  else {
    $name = trim(strip_tags($params['title']));
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
  return '<a class="helpicon" title="' . $title . '" href="#" onclick=\'CRM.help(' . $name . ', ' . json_encode($params) . '); return false;\'>&nbsp;</a>';
}
