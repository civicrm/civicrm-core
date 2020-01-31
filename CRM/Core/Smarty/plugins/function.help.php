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

  $params['file'] = str_replace(['.tpl', '.hlp'], '', $params['file']);

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
  $title = htmlspecialchars(ts('%1 Help', [1 => $name]));
  // Escape for html and js
  $name = htmlspecialchars(json_encode($name), ENT_QUOTES);

  // Format params to survive being passed through json & the url
  unset($params['text'], $params['title']);
  foreach ($params as &$param) {
    $param = is_bool($param) || is_numeric($param) ? (int) $param : (string) $param;
  }
  return '<a class="' . $class . '" title="' . $title . '" aria-label="' . $title . '" href="#" onclick=\'CRM.help(' . $name . ', ' . json_encode($params) . '); return false;\'>&nbsp;</a>';
}
