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
 * Trait for template management.
 *
 * Shared by CRM_Core_Controller, CRM_Core_Form & CRM_Core_Page
 */
trait CRM_Core_SmartyPageTrait {

  /**
   * Global smarty template
   *
   * @var CRM_Core_Smarty
   * @see CRM_Core_Smarty::singleton()
   */
  static protected $_template;

  /**
   * @return CRM_Core_Smarty
   */
  public static function getTemplate() {
    return self::$_template;
  }

  /**
   * Assign value to name in template.
   *
   * @param string $var
   *   Name of variable.
   * @param mixed $value
   *   Value of variable.
   */
  public function assign($var, $value = NULL) {
    self::$_template->assign($var, $value);
  }

  /**
   * Appends values to template variables.
   *
   * @param array|string $tpl_var the template variable name(s)
   * @param mixed $value
   *   The value to append.
   * @param bool $merge
   */
  public function append($tpl_var, $value = NULL, $merge = FALSE) {
    self::$_template->append($tpl_var, $value, $merge);
  }

  /**
   * Get the value/s assigned to the Template Engine (Smarty).
   *
   * @param string|null $name
   */
  public function getTemplateVars($name = NULL) {
    return self::$_template->getTemplateVars($name);
  }

  /**
   * A wrapper for getTemplateFileName.
   *
   * This includes calling the hook to prevent us from having to copy & paste the logic of calling the hook.
   */
  public function getHookedTemplateFileName() {
    $pageTemplateFile = $this->getTemplateFileName();
    CRM_Utils_Hook::alterTemplateFile(get_class($this), $this, 'page', $pageTemplateFile);
    return $pageTemplateFile;
  }

  /**
   * Default extra tpl file basically just replaces .tpl with .extra.tpl.
   *
   * i.e. we do not override.
   *
   * @return string
   */
  public function overrideExtraTemplateFileName() {
    return NULL;
  }

  /**
   * Add an expected smarty variable to the array.
   *
   * @param string $elementName
   */
  public function addExpectedSmartyVariable(string $elementName): void {
    $this->expectedSmartyVariables[] = $elementName;
  }

  /**
   * Add an expected smarty variable to the array.
   *
   * @param array $elementNames
   */
  public function addExpectedSmartyVariables(array $elementNames): void {
    foreach ($elementNames as $elementName) {
      // Duplicates don't actually matter....
      $this->addExpectedSmartyVariable($elementName);
    }
  }

  /**
   * Assign value to name in template by reference.
   *
   * @param string $var
   *   Name of variable.
   * @param mixed $value
   *   Value of variable.
   *
   * @deprecated since 5.72 will be removed around 5.84
   */
  public function assign_by_ref($var, &$value) {
    CRM_Core_Error::deprecatedFunctionWarning('assign');
    self::$_template->assign($var, $value);
  }

  /**
   * Returns an array containing template variables.
   *
   * @deprecated since 5.69 will be removed around 5.93. use getTemplateVars.
   *
   * @param string $name
   *
   * @return array
   */
  public function get_template_vars($name = NULL) {
    CRM_Core_Error::deprecatedFunctionWarning('getTemplateVars');
    return $this->getTemplateVars($name);
  }

}
