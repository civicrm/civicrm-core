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
  if (!isset($params['id']) || !isset($smarty->getTemplateVars()['config'])) {
    return NULL;
  }

  if (empty($params['file']) && isset($smarty->getTemplateVars()['tplFile'])) {
    $params['file'] = $smarty->getTemplateVars()['tplFile'];
  }
  elseif (empty($params['file'])) {
    return NULL;
  }

  $params['file'] = str_replace(['.tpl', '.hlp'], '', $params['file']);
  $fieldID = str_replace('-', '_', preg_replace('/^id-/', '', $params['id']));

  if (empty($params['title'])) {
    $vars = $smarty->getTemplateVars();

    // The way this works is a bit bonkers. All the .hlp files are expecting an
    // assign called $params (which is different from our php var here called
    // $params), and it does get assigned via ajax via
    // CRM_Core_Page_Inline_Help when you click the help bubble (i.e. the link
    // that we return at the bottom below). But right now when we fetch the
    // file on the next line, there is no params. So it gives a notice. So
    // let's assign something.
    // We also need to assign the id for the title we are looking for, which
    // will not be present in Smarty 3 otherwise.
    // It's also awkward since the ONLY reason we're fetching the file
    // now is to get the help section's title and we don't care about the rest
    // of the file, but that is a bit of a separate issue.
    $temporary_vars = ['id' => $params['id'] . '-title'];
    if (!array_key_exists('params', $vars)) {
      // In the unlikely event that params already exists, we don't want to
      // overwrite it, so only do this if not already set.
      $temporary_vars += ['params' => []];
    }
    // Note fetchWith adds the temporary ones to the existing scope but then
    // will reset, unsetting them if not already present before, which is what
    // we want here.
    $name = trim($smarty->fetchWith($params['file'] . '.hlp', $temporary_vars)) ?: $vars['form'][$fieldID]['textLabel'] ?? '';
    $additionalTPLFile = $params['file'] . '.extra.hlp';
    if ($smarty->template_exists($additionalTPLFile)) {
      $extraoutput = trim($smarty->fetch($additionalTPLFile));
      if ($extraoutput) {
        // Allow override param to replace default text e.g. {hlp id='foo' override=1}
        $name = ($smarty->getTemplateVars('override_help_text') || empty($name)) ? $extraoutput : $name . ' ' . $extraoutput;
      }
    }

    // Ensure we didn't change any existing vars CRM-11900
    foreach ($vars as $key => $value) {
      if ($smarty->getTemplateVars($key) !== $value) {
        $smarty->assign($key, $value);
      }
    }
  }
  else {
    $name = trim(strip_tags($params['title'])) ?: $vars['form'][$fieldID]['textLabel'] ?? '';
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
