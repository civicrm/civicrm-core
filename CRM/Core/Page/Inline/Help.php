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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This loads a smarty help file via ajax and returns as html
 */
class CRM_Core_Page_Inline_Help {

  public function run() {
    $args = $_REQUEST;
    $file = (string) ($args['file'] ?? '');
    // windows - just replace so the regex can match
    $file = str_replace('\\', '/', $file);
    if (preg_match('@^[a-zA-Z0-9_-]+(/[a-zA-Z0-9_-]+)*$@', $file)) {
      $additionalTPLFile = "$file.extra.hlp";
      $file .= '.hlp';
      $smarty = CRM_Core_Smarty::singleton();
      $smarty->assign('id', $args['id']);
      CRM_Utils_Array::remove($args, 'file', 'class_name', 'type', 'q', 'id');
      foreach ($args as &$arg) {
        $arg = strip_tags($arg);
      }
      $smarty->assign('params', $args);
      $smarty->assign('accessKey', $this->getAccessKey());

      $output = $smarty->fetch($file);
      $extraoutput = '';
      if ($smarty->template_exists($additionalTPLFile)) {
        $extraoutput .= trim($smarty->fetch($additionalTPLFile));
        // Allow override param to replace default text e.g. {hlp id='foo' override=1}
        if ($smarty->getTemplateVars('override_help_text')) {
          $output = '';
        }
      }
      echo trim($output . $extraoutput);
      CRM_Utils_System::civiExit();
    }
    else {
      throw new CRM_Core_Exception('File name is not valid');
    }
  }

  private function getAccessKey(): string {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (str_contains($ua, 'mac')) {
      return '<span>CTRL</span>+<span>OPTION</span>';
    }
    return str_contains($ua, 'firefox') ? '<span>ALT</span>+<span>SHIFT</span>' : '<span>ALT</span>';
  }

}
