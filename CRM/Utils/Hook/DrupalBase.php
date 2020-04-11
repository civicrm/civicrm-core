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
class CRM_Utils_Hook_DrupalBase extends CRM_Utils_Hook {

  /**
   * @var bool
   */
  private $isBuilt = FALSE;

  /**
   * All Modules.
   *
   * @var string[]
   */
  private $allModules = NULL;

  /**
   * CiviCRM Modules.
   *
   * @var string[]
   */
  private $civiModules = NULL;

  /**
   * Drupal modules.
   *
   * @var string[]
   */
  private $drupalModules = NULL;

  /**
   * @param int $numParams
   *   Number of parameters to pass to the hook.
   * @param mixed $arg1
   *   Parameter to be passed to the hook.
   * @param mixed $arg2
   *   Parameter to be passed to the hook.
   * @param mixed $arg3
   *   Parameter to be passed to the hook.
   * @param mixed $arg4
   *   Parameter to be passed to the hook.
   * @param mixed $arg5
   *   Parameter to be passed to the hook.
   * @param mixed $arg6
   * @param string $fnSuffix
   *   Function suffix, this is effectively the hook name.
   *
   * @return array|bool
   * @throws \Exception
   * @see CRM_Utils_Hook::invoke()
   */
  public function invokeViaUF(
    $numParams,
    &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6,
    $fnSuffix) {

    $this->buildModuleList();

    return $this->runHooks($this->allModules, $fnSuffix,
      $numParams, $arg1, $arg2, $arg3, $arg4, $arg5, $arg6
    );
  }

  /**
   * Build the list of modules to be processed for hooks.
   */
  public function buildModuleList() {
    if ($this->isBuilt === FALSE) {
      if ($this->drupalModules === NULL) {
        $this->drupalModules = $this->getDrupalModules();
      }

      if ($this->civiModules === NULL) {
        $this->civiModules = [];
        $this->requireCiviModules($this->civiModules);
      }

      // CRM-12370
      // we should add civicrm's module's just after main civicrm drupal module
      // Note: Assume that drupalModules and civiModules may each be array() or NULL
      if ($this->drupalModules !== NULL) {
        foreach ($this->drupalModules as $moduleName) {
          $this->allModules[$moduleName] = $moduleName;
          if ($moduleName == 'civicrm') {
            if (!empty($this->civiModules)) {
              foreach ($this->civiModules as $civiModuleName) {
                $this->allModules[$civiModuleName] = $civiModuleName;
              }
            }
          }
        }
      }
      else {
        $this->allModules = (array) $this->civiModules;
      }

      if ($this->drupalModules !== NULL && $this->civiModules !== NULL) {
        // both CRM and CMS have bootstrapped, so this is the final list
        $this->isBuilt = TRUE;
      }
    }
  }

  /**
   * Gets modules installed on the Drupal site.
   *
   * @return array|null
   *   The machine names of the modules installed in Drupal, or NULL if unable
   *   to determine the modules.
   */
  protected function getDrupalModules() {
    if (function_exists('module_list')) {
      // copied from user_module_invoke
      return module_list();
    }
  }

}
