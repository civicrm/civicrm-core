<?php

require_once 'orgtaskskr.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function orgtaskskr_civicrm_config(&$config) {
  _orgtaskskr_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param array $files
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function orgtaskskr_civicrm_xmlMenu(&$files) {
  _orgtaskskr_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function orgtaskskr_civicrm_install() {
  _orgtaskskr_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function orgtaskskr_civicrm_uninstall() {
  _orgtaskskr_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function orgtaskskr_civicrm_enable() {
  _orgtaskskr_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function orgtaskskr_civicrm_disable() {
  _orgtaskskr_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function orgtaskskr_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _orgtaskskr_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function orgtaskskr_civicrm_managed(&$entities) {
  _orgtaskskr_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * @param array $caseTypes
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function orgtaskskr_civicrm_caseTypes(&$caseTypes) {
  _orgtaskskr_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function orgtaskskr_civicrm_angularModules(&$angularModules) {
  $angularModule['crmProfileUtils'] = array(
    'ext' => 'org.compucorp.orgtaskskr',
    'js' => array('js/*.js'),
    'partials' => array('partials'),
  );
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function orgtaskskr_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _orgtaskskr_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function orgtaskskr_civicrm_tabset($tabsetName, &$tabs, $context) {
  if ($tabsetName == 'civicrm/contact/view') {
    //var_dump($tabsetName, $tabs, $context);
	  //$context['contact_id']
    
    $contact = civicrm_api3('Contact', 'get', array(
      'sequential' => 1,
      'return' => array("contact_type"),
      'id' => $context['contact_id'],
    ));
    
    if ($contact['values'][0]['contact_type'] == 'Organization') {
      $tabs[] = array( 
        'id' => 'orgTasksTab',
        'url' => CRM_Utils_System::url('civicrm/tasklist', "contact=" . $context['contact_id']),
        'title' => ts('Org. Activities'),
        'weight' => 300,
      );
    }
  }
}

/**
 * Functions below this ship commented out. Uncomment as required.
 *

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function orgtaskskr_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function orgtaskskr_civicrm_navigationMenu(&$menu) {
  _orgtaskskr_civix_insert_navigation_menu($menu, 'Administer/System Settings', array(
    'label' => ts('Organization Activity Lists', array('domain' => 'com.compucorp.orgtaskskr')),
    'name' => 'tasklist_admin',
    'url' => 'civicrm/tasklist/admin',
    'permission' => 'access CiviReport,access CiviContribute',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _orgtaskskr_civix_navigationMenu($menu);
} // */
