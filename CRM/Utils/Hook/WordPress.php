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
class CRM_Utils_Hook_WordPress extends CRM_Utils_Hook {

  /**
   * @var bool
   */
  private $isBuilt = FALSE;

  /**
   * @var string[]
   */
  private $allModules = NULL;

  /**
   * @var string[]
   */
  private $civiModules = NULL;

  /**
   * @var string[]
   */
  private $wordpressModules = NULL;

  /**
   * @var string[]
   */
  private $hooksThatReturn = [
    'civicrm_upgrade',
    'civicrm_caseSummary',
    'civicrm_dashboard',
  ];

  /**
   * Invoke hooks.
   *
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
   *   Parameter to be passed to the hook.
   * @param string $fnSuffix
   *   Function suffix, this is effectively the hook name.
   *
   * @return mixed
   */
  public function invokeViaUF(
    $numParams,
    &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6,
    $fnSuffix
  ) {

    // do_action_ref_array is the default way of calling WordPress hooks
    // because for the most part no return value is wanted. However, this is
    // only generally true, so using do_action_ref_array() is only called for those
    // hooks which do not require a return value. We exclude the following, which
    // are incompatible with the WordPress Plugin API:
    //
    // civicrm_upgrade
    // https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade/
    //
    // civicrm_caseSummary
    // https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseSummary/
    //
    // civicrm_dashboard
    // https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_dashboard/

    // distinguish between types of hook
    if (!in_array($fnSuffix, $this->hooksThatReturn)) {

      // only pass the arguments that have values
      $args = array_slice(
        [&$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6],
        0,
        $numParams
      );

      // Use WordPress Plugins API to modify $args
      //
      // Because $args are passed as references to the WordPress callbacks,
      // runHooks subsequently receives appropriately modified parameters.

      // protect from REST calls
      if (function_exists('do_action_ref_array')) {
        do_action_ref_array($fnSuffix, $args);
      }

    }

    // The following is based on the logic of the Joomla hook file by allowing
    // WordPress callbacks to do their stuff before runHooks gets called.

    // It also follows the logic of the Drupal hook file by building the "module"
    // (read "plugin") list and then calling runHooks directly. This should avoid
    // the need for the post-processing that the Joomla hook file does.

    // Note that hooks which require a return value are incompatible with the
    // signature of apply_filters_ref_array and must therefore be called in
    // global scope, like in Drupal. It's not ideal, but plugins can always route
    // these calls to methods in their classes.

    // At some point, those hooks could be pre-processed and called via the WordPress
    // Plugin API, but it would change their signature and require the CiviCRM docs
    // to be rewritten for those calls in WordPress. So it's been done this way for
    // now. Ideally these hooks will be deprecated in favour of hooks that do not
    // require return values.

    // build list of registered plugin codes
    $this->buildModuleList();

    // Call runHooks the same way Drupal does
    $moduleResult = $this->runHooks(
      $this->allModules,
      $fnSuffix,
      $numParams,
      $arg1, $arg2, $arg3, $arg4, $arg5, $arg6
    );

    // finally, return
    return empty($moduleResult) ? TRUE : $moduleResult;

  }

  /**
   * Build the list of plugins ("modules" in CiviCRM terminology) to be processed for hooks.
   *
   * We need to do this to preserve the CiviCRM hook signatures for hooks that require
   * a return value, since the WordPress Plugin API seems to be incompatible with them.
   */
  public function buildModuleList() {
    if ($this->isBuilt === FALSE) {

      if ($this->wordpressModules === NULL) {

        // include custom PHP file - copied from parent->commonBuildModuleList()
        $config = CRM_Core_Config::singleton();
        if (!empty($config->customPHPPathDir) &&
          file_exists("{$config->customPHPPathDir}/civicrmHooks.php")
        ) {
          @include_once "{$config->customPHPPathDir}/civicrmHooks.php";
        }

        // initialise with the pre-existing 'wordpress' prefix
        $this->wordpressModules = ['wordpress'];

        // Use WordPress Plugin API to build list
        // a plugin simply needs to declare its "unique_plugin_code" thus:
        // add_filter('civicrm_wp_plugin_codes', 'function_that_returns_my_unique_plugin_code');

        // protect from REST calls
        if (function_exists('apply_filters')) {
          $this->wordpressModules = apply_filters('civicrm_wp_plugin_codes', $this->wordpressModules);
        }

      }

      if ($this->civiModules === NULL) {
        $this->civiModules = [];
        $this->requireCiviModules($this->civiModules);
      }

      $this->allModules = array_merge((array) $this->wordpressModules, (array) $this->civiModules);
      if ($this->wordpressModules !== NULL && $this->civiModules !== NULL) {
        // both CRM and CMS have bootstrapped, so this is the final list
        $this->isBuilt = TRUE;
      }

    }
  }

}
