<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @package CiviCRM_Hook
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id: $
 *
 */
class CRM_Utils_Hook_Drupal6 extends CRM_Utils_Hook {

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

  function invoke($numParams,
    &$arg1, &$arg2, &$arg3, &$arg4, &$arg5,
    $fnSuffix
  ) {

    $this->buildModuleList();
    
    return $this->runHooks($this->allModules, $fnSuffix,
      $numParams, $arg1, $arg2, $arg3, $arg4, $arg5
    );
  }

  /**
   * Build the list of modules to be processed for hooks.
   */
  function buildModuleList() {
    if ($this->isBuilt === FALSE) {
      if ($this->drupalModules === NULL) {
        if (function_exists('module_list')) {
          // copied from user_module_invoke
          $this->drupalModules = module_list();
        }
      }

      if ($this->civiModules === NULL) {
        $this->civiModules = array();
        $this->requireCiviModules($this->civiModules);
      }

      $this->allModules = array_merge((array)$this->drupalModules, (array)$this->civiModules);
      if ($this->drupalModules !== NULL && $this->civiModules !== NULL) {
        // both CRM and CMS have bootstrapped, so this is the final list
        $this->isBuilt = TRUE;
      }
    }
  }
}



