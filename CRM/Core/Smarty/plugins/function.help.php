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
 * Adds markup for inline help link.
 *
 * Help-icon link calls js function which loads the help text in ajax pop-up.
 *
 * @param array $params
 *   The function params.
 * @param Smarty $smarty
 *   Smarty object.
 *
 * @return string
 *   the help html to be inserted
 */
function smarty_function_help($params, $smarty) {
  if (isset($params['values']) && is_array($params['values'])) {
    // Passing in values is way easier at the smarty level as it likely already
    // has a field spec. Use/ prefer values.
    $params = array_merge($params, $params['values']);
    unset($params['values']);
  }

  $tplVars = $smarty->getTemplateVars();

  if (!isset($params['id']) || !isset($tplVars['config'])) {
    return NULL;
  }

  if (empty($params['file']) && isset($tplVars['tplFile'])) {
    $params['file'] = $tplVars['tplFile'];
  }
  elseif (empty($params['file'])) {
    return NULL;
  }

  $params['file'] = str_replace(['.tpl', '.hlp'], '', $params['file']);

  // Title passed in explicitly
  if (!empty($params['title'])) {
    // Remove possible `<label>` markup
    $helpTextTitle = trim(strip_tags($params['title']));
  }
  // Infer title from form field label
  if (empty($helpTextTitle)) {
    $fieldID = $params['id'];
    // Support legacy naming convention in older `.hlp` files (convert e.g. `id-frontend-title` to `frontend_title`)
    if (str_contains($fieldID, '-')) {
      $fieldID = str_replace('-', '_', preg_replace('/^id-/', '', $fieldID));
    }
    // Use fieldTitle... fall back to pageTitle if nothing else
    $helpTextTitle = $tplVars['form'][$fieldID]['textLabel'] ?? $tplVars['docTitle'] ?? $tplVars['pageTitle'] ?? '';
  }

  $class = "helpicon";
  if (!empty($params['class'])) {
    $class .= " {$params['class']}";
  }

  // Escape for html
  $title = htmlspecialchars(ts('%1 Help', [1 => $helpTextTitle]));
  // Escape for html and js
  $helpTextTitle = htmlspecialchars(json_encode($helpTextTitle), ENT_QUOTES);

  // Format params to survive being passed through json & the url
  unset($params['text'], $params['title'], $params['class']);
  foreach ($params as &$param) {
    $param = is_bool($param) || is_numeric($param) ? (int) $param : (string) $param;
  }
  $helpTextParams = htmlspecialchars(json_encode($params), ENT_QUOTES);
  return '<a class="' . $class . '" title="' . $title . '" aria-label="' . $title . '" href="#" onclick=\'CRM.help(' . $helpTextTitle . ', ' . $helpTextParams . '); return false;\'>&nbsp;</a>';
}
