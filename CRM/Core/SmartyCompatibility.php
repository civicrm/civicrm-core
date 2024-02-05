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
 * Fix for bug CRM-392. Not sure if this is the best fix or it will impact
 * other similar PEAR packages. doubt it
 */
if (!class_exists('Smarty')) {
  if (defined('CIVICRM_SMARTY_AUTOLOAD_PATH')) {
    // Specify the smarty version to load.
    require_once CIVICRM_SMARTY_AUTOLOAD_PATH;
  }
  elseif (defined('CIVICRM_SMARTY3_AUTOLOAD_PATH')) {
    // older version of the above constant.
    require_once CIVICRM_SMARTY3_AUTOLOAD_PATH;
  }
  else {
    require_once 'Smarty/Smarty.class.php';
  }
}

/**
 *
 */
class CRM_Core_SmartyCompatibility extends Smarty {

  public function loadFilter($type, $name) {
    if (method_exists(get_parent_class(), 'load_filter')) {
      parent::load_filter($type, $name);
      return;
    }
    parent::loadFilter($type, $name);
  }

  /**
   * @deprecated
   *
   * @param string $type
   * @param string $name
   *
   * @throws \SmartyException
   */
  public function load_filter($type, $name) {
    if (method_exists(get_parent_class(), 'load_filter')) {
      parent::load_filter($type, $name);
      return;
    }
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
    if (method_exists(get_parent_class(), 'register_modifier')) {
      parent::register_modifier($modifier, $modifier_impl);
      return;
    }
    parent::registerPlugin('modifier', $modifier, $modifier_impl);
  }

  public function registerPlugin($type, $name, $callback, $cacheable = TRUE, $cache_attr = NULL) {
    if (method_exists(get_parent_class(), 'registerPlugin')) {
      parent::registerPlugin($type, $name, $callback, $cacheable = TRUE, $cache_attr = NULL);
      return;
    }
    if ($type === 'modifier') {
      parent::register_modifier($name, $callback);
    }
  }

  /**
   * Registers a resource to fetch a template
   *
   * @param string $type name of resource
   * @param array $functions array of functions to handle resource
   */
  public function register_resource($type, $functions) {
    if (method_exists(get_parent_class(), 'register_resource')) {
      parent::register_resource($type, $functions);
      return;
    }
    if ($type === 'string') {
      // Not valid / required for Smarty3+
      return;
    }
    parent::registerResource($type, $functions);
  }

  /**
   * Registers custom function to be used in templates
   *
   * @param string $function the name of the template function
   * @param string $function_impl the name of the PHP function to register
   * @param bool $cacheable
   * @param null $cache_attrs
   *
   * @throws \SmartyException
   */
  public function register_function($function, $function_impl, $cacheable = TRUE, $cache_attrs = NULL) {
    if (method_exists(get_parent_class(), 'register_function')) {
      parent::register_function($function, $function_impl, $cacheable = TRUE, $cache_attrs = NULL);
      return;
    }
    parent::registerPlugin('function', $function, $function, $cacheable, $cache_attrs);
  }

  /**
   * Returns an array containing template variables
   *
   * @param string $name
   *
   * @return array
   */
  public function &get_template_vars($name = NULL) {
    if (method_exists(get_parent_class(), 'get_template_vars')) {
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
   *
   * @return mixed|null|void
   */
  public function assign_by_ref($tpl_var, &$value) {
    if (method_exists(get_parent_class(), 'assign_by_ref')) {
      parent::assign_by_ref($tpl_var, $value);
      return;
    }
    return parent::assignByRef($tpl_var, $value);
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
    if (method_exists(get_parent_class(), 'clear_assign')) {
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
    if (method_exists(get_parent_class(), 'template_exists')) {
      return parent::template_exists($tpl_file);
    }
    return parent::templateExists($tpl_file);
  }

  /**
   * Check if a template resource exists
   *
   * @param string $resource_name template name
   *
   * @return bool status
   * @throws \SmartyException
   */
  public function templateExists($resource_name) {
    if (method_exists(get_parent_class(), 'templateExists')) {
      return parent::templateExists($resource_name);
    }
    return parent::template_exists($resource_name);
  }

}
