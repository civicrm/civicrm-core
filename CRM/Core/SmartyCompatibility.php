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
 * Smarty Compatibility class.
 *
 * This class implements both Smarty v2 & Smarty v3+ functions so that
 *
 * 1) we can start to transition functions like `$smarty->assign_var` to
 * `$smarty->assignVar()`
 * 2) if someone defines CIVICRM_SMARTY_AUTOLOAD_PATH then Smarty will load from that
 * location.
 *
 * Note that experimenting with `CIVICRM_SMARTY_AUTOLOAD_PATH` will not
 * go well if extensions are installed that have not run civix upgrade
 * somewhat recently (ie have the old version of the hook_civicrm_config
 * with reference to `$template =& CRM_Core_Smarty::singleton();`
 */

/**
 * Get the path to load Smarty.
 *
 * @return string|null
 */
function crm_smarty_compatibility_get_path() {
  $path = CRM_Utils_Constant::value('CIVICRM_SMARTY_AUTOLOAD_PATH') ?: CRM_Utils_Constant::value('CIVICRM_SMARTY3_AUTOLOAD_PATH');
  if ($path) {
    $path = str_replace('smarty3', 'smarty4', $path);
  }
  else {
    $path = \Civi::paths()->getPath('[civicrm.packages]/smarty5/Smarty.php');
  }
  return $path;
}

/**
 * Fix for bug CRM-392. Not sure if this is the best fix or it will impact
 * other similar PEAR packages. doubt it
 */
if (!class_exists('Smarty')) {
  $path = crm_smarty_compatibility_get_path();
  if ($path) {
    // Specify the smarty version to load.
    require_once $path;
  }
  else {
    require_once 'Smarty/Smarty.class.php';
  }
}

/**
 *
 */
class CRM_Core_SmartyCompatibility extends Smarty {

  /**
   * @deprecated
   *
   * @param string $type
   * @param string $name
   *
   * @throws \SmartyException
   */
  public function load_filter($type, $name) {
    if (method_exists(parent::class, 'load_filter')) {
      parent::load_filter($type, $name);
      return;
    }
    CRM_Core_Error::deprecatedWarning('loadFilter');
    parent::loadFilter($type, $name);
  }

  /**
   * Registers modifier to be used in templates
   *
   * @deprecated
   *
   * @param string $modifier name of template modifier
   * @param string $modifier_impl name of PHP function to register
   */
  public function register_modifier($modifier, $modifier_impl) {
    if (method_exists(parent::class, 'register_modifier')) {
      parent::register_modifier($modifier, $modifier_impl);
      return;
    }
    CRM_Core_Error::deprecatedWarning('registerPlugin');
    parent::registerPlugin('modifier', $modifier, $modifier_impl);
  }

  /**
   * Registers a resource to fetch a template
   *
   * @deprecated
   *
   * @param string $type name of resource
   * @param array $functions array of functions to handle resource
   */
  public function register_resource($type, $functions) {
    if (method_exists(parent::class, 'register_resource')) {
      parent::register_resource($type, $functions);
      return;
    }
    if ($type === 'string') {
      // Not valid / required for Smarty3+
      return;
    }
    CRM_Core_Error::deprecatedWarning('registerResource');
    parent::registerResource($type, $functions);
  }

  /**
   * Registers custom function to be used in templates
   *
   * @deprecated
   *
   * @param string $function the name of the template function
   * @param string $function_impl the name of the PHP function to register
   * @param bool $cacheable
   * @param null $cache_attrs
   *
   * @throws \SmartyException
   */
  public function register_function($function, $function_impl, $cacheable = TRUE, $cache_attrs = NULL) {
    if (method_exists(parent::class, 'register_function')) {
      parent::register_function($function, $function_impl, $cacheable = TRUE, $cache_attrs = NULL);
      return;
    }
    CRM_Core_Error::deprecatedWarning('registerPlugin');
    parent::registerPlugin('function', $function, $function, $cacheable, $cache_attrs);
  }

  /**
   * Returns an array containing template variables
   *
   * @deprecated since 5.69 will be removed around 5.79
   *
   * @param string $name
   *
   * @return array
   */
  public function &get_template_vars($name = NULL) {
    if (method_exists(parent::class, 'get_template_vars')) {
      return parent::get_template_vars($name);
    }
    $var = parent::getTemplateVars($name);
    return $var;
  }

  /**
   * Generally Civi mis-uses this for perceived php4 conformance, avoid.
   *
   * @deprecated
   * @param string $tpl_var
   * @param mixed $value
   */
  public function assign_by_ref($tpl_var, &$value) {
    if (method_exists(parent::class, 'assign_by_ref')) {
      parent::assign_by_ref($tpl_var, $value);
      return;
    }
    $this->assign($tpl_var, $value);
  }

  /**
   * Generally Civi mis-uses this for perceived php4 conformance, avoid.
   *
   * @deprecated
   * @param string $tpl_var
   *
   * @return mixed|null|void
   */
  public function clear_assign($tpl_var) {
    if (method_exists(parent::class, 'clear_assign')) {
      parent::clear_assign($tpl_var);
      return;
    }
    return parent::clearAssign($tpl_var);
  }

  /**
   * Checks whether requested template exists.
   *
   * @param string $tpl_file
   *
   * @return bool
   * @throws \SmartyException
   */
  public function template_exists($tpl_file) {
    if (method_exists(parent::class, 'template_exists')) {
      return parent::template_exists($tpl_file);
    }
    return parent::templateExists($tpl_file);
  }

}
