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
 * Adds inline help.
 *
 * This function adds a call to the js function which loads the help text in a pop-up.
 *
 * It does a lot of work to get the title which it passes into the crmHelp function
 * but the main reason it gets that title is because it adds that to the css as
 * title & aria-label. Since it's loaded it somewhat makes sense to pass it into
 * CRM.help but .. it's confusing.
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

  if (!empty($params['title'])) {
    // Passing in title is preferable..... ideally we would always pass in title & remove from the .hlp files....
    $helpTextTitle = trim(strip_tags($params['title'])) ?: $vars['form'][$fieldID]['textLabel'] ?? '';
  }
  else {
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

    $helpFile = $params['file'] . '.hlp';
    $additionalFile = $params['file'] . '.extra.hlp';
    $coreSmarty = CRM_Core_Smarty::singleton();
    $directories = $coreSmarty->getTemplateDir();
    $helpText = '';
    foreach ($directories as $directory) {
      if (CRM_Utils_File::isIncludable($directory . $helpFile)) {
        $helpText = file_get_contents($directory . $helpFile);
        break;
      }
    }
    $additionalTexts = [];
    foreach ($directories as $directory) {
      if (CRM_Utils_File::isIncludable($directory . $additionalFile)) {
        $additionalTexts[] = file_get_contents($directory . $additionalFile);
        break;
      }
    }
    try {
      $coreSmarty->pushScope($temporary_vars);
      $helpTextTitle = trim(CRM_Utils_String::parseOneOffStringThroughSmarty($helpText) ?: $vars['form'][$fieldID]['textLabel'] ?? '');
      foreach ($additionalTexts as $additionalText) {
        $additionalTextTitle = trim(CRM_Utils_String::parseOneOffStringThroughSmarty($additionalText));
        $helpTextTitle = ($smarty->getTemplateVars('override_help_text') || empty($helpTextTitle)) ? $additionalTextTitle : $helpTextTitle . ' ' . $additionalTextTitle;
      }
    }
    finally {
      $coreSmarty->popScope();
    }
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
  unset($params['text'], $params['title']);
  foreach ($params as &$param) {
    $param = is_bool($param) || is_numeric($param) ? (int) $param : (string) $param;
  }
  return '<a class="' . $class . '" title="' . $title . '" aria-label="' . $title . '" href="#" onclick=\'CRM.help(' . $helpTextTitle . ', ' . json_encode($params) . '); return false;\'>&nbsp;</a>';
}
