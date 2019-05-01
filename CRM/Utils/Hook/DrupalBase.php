<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Utils_Hook_DrupalBase extends CRM_Utils_Hook {

  /**
   * @var bool
   */
  private $isBuilt = FALSE;

  /**
   * @var array(string)
   */
  private $allModules = NULL;

  /**
   * @var array(string)
   */
  private $civiModules = NULL;

  /**
   * @var array(string)
   */
  private $drupalModules = NULL;

  /**
   * @see CRM_Utils_Hook::invoke()
   *
   * @param int $numParams
   *   Number of parameters to pass to the hook.
   * @param unknown $arg1
   *   Parameter to be passed to the hook.
   * @param unknown $arg2
   *   Parameter to be passed to the hook.
   * @param unknown $arg3
   *   Parameter to be passed to the hook.
   * @param unknown $arg4
   *   Parameter to be passed to the hook.
   * @param unknown $arg5
   *   Parameter to be passed to the hook.
   * @param mixed $arg6
   * @param string $fnSuffix
   *   Function suffix, this is effectively the hook name.
   *
   * @return array|bool
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
