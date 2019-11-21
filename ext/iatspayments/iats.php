<?php

/**
 * @file Copyright iATS Payments (c) 2014
 * Author: Alan Dixon.
 *
 * This file is a part of CiviCRM published extension.
 *
 * This extension is free software; you can copy, modify, and distribute it
 * under the terms of the GNU Affero General Public License
 * Version 3, 19 November 2007.
 *
 * It is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License with this program; if not, see http://www.gnu.org/licenses/
 */

//opcache_reset();

require_once 'iats.civix.php';
use CRM_Iats_ExtensionUtil as E;

/* First American requires a "category" for ACH transaction requests */

define('FAPS_DEFAULT_ACH_CATEGORY_TEXT', 'CiviCRM ACH');

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function iats_civicrm_config(&$config) {
  _iats_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function iats_civicrm_xmlMenu(&$files) {
  _iats_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 * TODO: don't require iATS if we're not installing the old processors.
 */
function iats_civicrm_install() {
  if (!class_exists('SoapClient')) {
    $session = CRM_Core_Session::singleton();
    $session->setStatus(ts('The PHP SOAP extension is not installed on this server, but is required for this extension'), ts('iATS Payments Installation'), 'error');
  }
  _iats_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function iats_civicrm_postInstall() {
  _iats_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function iats_civicrm_uninstall() {
  _iats_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function iats_civicrm_enable() {
  if (!class_exists('SoapClient')) {
    $session = CRM_Core_Session::singleton();
    $session->setStatus(ts('The PHP SOAP extension is not installed on this server, but is required for this extension'), ts('iATS Payments Installation'), 'error');
  }
  _iats_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function iats_civicrm_disable() {
  _iats_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function iats_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _iats_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function iats_civicrm_managed(&$entities) {
  _iats_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function iats_civicrm_caseTypes(&$caseTypes) {
  _iats_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function iats_civicrm_angularModules(&$angularModules) {
  _iats_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function iats_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _iats_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function iats_civicrm_entityTypes(&$entityTypes) {
  _iats_civix_civicrm_entityTypes($entityTypes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function iats_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function iats_civicrm_navigationMenu(&$menu) {
  _iats_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('New subliminal message'),
    'name' => 'mailing_subliminal_message',
    'url' => 'civicrm/mailing/subliminal',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _iats_civix_navigationMenu($menu);
} // */

/**
 * Implements hook_civicrm_check().
 */
function iats_civicrm_check(&$messages) {
  if (!class_exists('SoapClient')) {
    $messages[] = new CRM_Utils_Check_Message(
      'iats_soap',
      ts('The SOAP extension for PHP %1 is not installed on this server, but is required for this extension.', array(1 => phpversion())),
      ts('iATS Payments Installation'),
      \Psr\Log\LogLevel::CRITICAL,
      'fa-flag'
    );
  }
}

/**
 * Utility function to get domain info.
 *
 * Get values from the civicrm_domain table, or a domain setting.
 * May be called multiple times, so be efficient.
 */
function _iats_civicrm_domain_info($key) {
  static $domain, $settings;
  if (empty($domain)) {
    $domain = civicrm_api3('Domain', 'getsingle', array('current_domain' => TRUE));
  }
  if (!isset($settings)) {
    $settings = array();
  }
  switch ($key) {
    case 'version':
      return explode('.', $domain['version']);

    default:
      if (isset($domain[$key])) {
        return $domain[$key];
      }
      elseif (isset($settings[$key])) {
        return $settings[$key];
      }
      else {
        try{
          $setting = civicrm_api3('Setting', 'getvalue', array('name' => $key));
          if (is_string($setting)) {
            $settings[$key] = $setting;
            return $setting;
          }
        }
        catch (CiviCRM_API3_Exception $e) {
          // ignore errors
        }
        // This remaining code is now very legacy, from earlier Civi versions and should soon be retired.
        if (!empty($domain['config_backend'])) {
          $config_backend = unserialize($domain['config_backend']);
          if (!empty($config_backend[$key])) {
            $settings[$key] = $config_backend[$key];
            return $config_backend[$key];
          }
        }
      }
      // Uncomment one or more of these lines to find out what it was we were looking for and didn't find.
      // CRM_Core_Error::debug_var('domain', $domain);
      // CRM_Core_Error::debug_var($key, $settings);
      // CRM_Core_Error::debug_var($key, $setting);
  }
}

/* START utility functions to allow this extension to work with different civicrm version */

// removed, 1.7 release

/* END functions to allow this extension to work with different civicrm version */

/**
 * Utility to get the next available menu key.
 */
function _iats_getMenuKeyMax($menuArray) {
  $max = array(max(array_keys($menuArray)));
  foreach ($menuArray as $v) {
    if (!empty($v['child'])) {
      $max[] = _iats_getMenuKeyMax($v['child']);
    }
  }
  return max($max);
}

/**
 *
 */
function iats_civicrm_navigationMenu(&$navMenu) {
  $pages = array(
    'admin_page' => array(
      'label'      => 'iATS Payments Admin',
      'name'       => 'iATS Payments Admin',
      'url'        => 'civicrm/iATSAdmin',
      'parent' => array('Contributions'),
      'permission' => 'access CiviContribute,administer CiviCRM',
      'operator'   => 'AND',
      'separator'  => NULL,
      'active'     => 1,
    ),
    'settings_page' => array(
      'label'      => 'iATS Payments Settings',
      'name'       => 'iATS Payments Settings',
      'url'        => 'civicrm/admin/contribute/iatssettings',
      'parent'    => array('Administer', 'CiviContribute'),
      'permission' => 'access CiviContribute,administer CiviCRM',
      'operator'   => 'AND',
      'separator'  => NULL,
      'active'     => 1,
    ),
  );
  foreach ($pages as $item) {
    // Check that our item doesn't already exist.
    $menu_item_search = array('url' => $item['url']);
    $menu_items = array();
    CRM_Core_BAO_Navigation::retrieve($menu_item_search, $menu_items);
    if (empty($menu_items)) {
      $path = implode('/', $item['parent']);
      unset($item['parent']);
      _iats_civix_insert_navigation_menu($navMenu, $path, $item);
    }
  }
}

/**
 * Hook_civicrm_buildForm.
 * Do a Drupal 7 style thing so we can write smaller functions.
 */
function iats_civicrm_buildForm($formName, &$form) {
  $fname = 'iats_civicrm_buildForm_' . $formName;
  if (function_exists($fname)) {
    // CRM_Core_Error::debug_var('overridden formName',$formName);
    $fname($form);
  }
  // Else echo $fname;.
}


/**
 * Modifications to a (public/frontend) contribution financial forms for iATS
 * procesors.
 * 1. enable public selection of future recurring contribution start date.
 * 
 * We're only handling financial payment class forms here. Note that we can no
 * longer test for whether the page has/is recurring or not. 
 */

function iats_civicrm_buildForm_CRM_Financial_Form_Payment(&$form) {
  // We're on CRM_Financial_Form_Payment, we've got just one payment processor
  // Skip this if it's not an iATS-type of processor
  $type = _iats_civicrm_is_iats($form->_paymentProcessor['id']);
  if (empty($type)) {
    return;
  }

  // If enabled provide a way to set future contribution dates. 
  // Uses javascript to hide/reset unless they have recurring contributions checked.
  $settings = CRM_Core_BAO_Setting::getItem('iATS Payments Extension', 'iats_settings');
  if (!empty($settings['enable_public_future_recurring_start'])
    && $form->_paymentObject->supportsFutureRecurStartDate()
  ) {
    $allow_days = empty($settings['days']) ? array('-1') : $settings['days'];
    $start_dates = CRM_Iats_Transaction::get_future_monthly_start_dates(time(), $allow_days);
    $form->addElement('select', 'receive_date', ts('Date of first contribution'), $start_dates);
    CRM_Core_Region::instance('billing-block')->add(array(
      'template' => 'CRM/Iats/BillingBlockRecurringExtra.tpl',
    ));
    $recurStartJs = CRM_Core_Resources::singleton()->getUrl('com.iatspayments.civicrm', 'js/recur_start.js');
    $script = 'var recurStartJs = "' . $recurStartJs . '";';
    $script .= 'CRM.$(function ($) { $.getScript(recurStartJs); });';
    CRM_Core_Region::instance('billing-block')->add(array(
      'script' => $script,
    ));
  }
}

/**
 * The main civicrm contribution form is the public one, there are some
 * edge cases where we need to do the same as the Financial form above.
 */
function iats_civicrm_buildForm_CRM_Contribute_Form_Contribution_Main(&$form) {
  return iats_civicrm_buildForm_CRM_Financial_Form_Payment($form);
}

/**
 *
 */
function iats_civicrm_pageRun(&$page) {
  $fname = 'iats_civicrm_pageRun_' . $page->getVar('_name');
  if (function_exists($fname)) {
    $fname($page);
  }
}

/**
 * Modify the recurring contribution (subscription) page.
 * Display extra information about recurring contributions using Legacy iATS, and
 * link to iATS CustomerLink display and editing pages.
 */
function iats_civicrm_pageRun_CRM_Contribute_Page_ContributionRecur($page) {
  // Get the corresponding (most recently created) iATS customer code record 
  // we'll also get the expiry date and last four digits (at least, our best information about that).
  $extra = array();
  $crid = CRM_Utils_Request::retrieve('id', 'Integer', $page, FALSE);
  try {
    $recur = civicrm_api3('ContributionRecur', 'getsingle', array('id' => $crid));
  }
  catch (CiviCRM_API3_Exception $e) {
    return;
  }
  $type = _iats_civicrm_is_iats($recur['payment_processor_id']);
  if ((0 !== strpos($type,'iATSService')) || empty($recur['payment_token_id'])) {
    return;
  }
  try {
    $payment_token = civicrm_api3('PaymentToken', 'getsingle', [
          'id' => $recur['payment_token_id'],
    ]);
    $customer_code = $payment_token['token'];
    $extra['iATS Customer Code'] = $customer_code;
    $customerLinkView = CRM_Utils_System::url('civicrm/contact/view/iatscustomerlink',
      'reset=1&cid=' . $recur['contact_id'] . '&customerCode=' . $customer_code . '&paymentProcessorId=' . $recur['payment_processor_id'] . '&is_test=' . $recur['is_test']);
    $extra['customerLink'] = "<a href='$customerLinkView'>View</a>";
    if ($type == 'iATSService' || $type == 'iATSServiceSWIPE') {
      $customerLinkEdit = CRM_Utils_System::url('civicrm/contact/edit/iatscustomerlink',
        'reset=1&cid=' . $recur['contact_id'] . '&customerCode=' . $customer_code . '&paymentProcessorId=' . $recur['payment_processor_id'] . '&is_test=' . $recur['is_test']);
      $extra['customerLink'] .= " | <a href='$customerLinkEdit'>Edit</a>";
      $processLink = CRM_Utils_System::url('civicrm/contact/iatsprocesslink',
        'reset=1&cid=' . $recur['contact_id'] . '&customerCode=' . $customer_code . '&paymentProcessorId=' . $recur['payment_processor_id'] . '&crid=' . $crid . '&is_test=' . $recur['is_test']);
      $extra['customerLink'] .= " | <a href='$processLink'>Process</a>";
      $expiry = $payment_token['expiry_date'];
      $extra['expiry'] = date('Y-m', strtotime($expiry));
    }
  }
  catch (CiviCRM_API3_Exception $e) {
    return;
  }
  if (!count($extra)) {
    return;
  }
  $template = CRM_Core_Smarty::singleton();
  foreach ($extra as $key => $value) {
    $template->assign($key, $value);
  }
  CRM_Core_Region::instance('page-body')->add(array(
    'template' => 'CRM/Iats/ContributionRecur.tpl',
  ));
  CRM_Core_Resources::singleton()->addScriptFile('com.iatspayments.civicrm', 'js/subscription_view.js');
}

/**
 * Hook_civicrm_merge
 * Deal with contact merges - our custom iats customer code table contains contact id's as a check, it might need to be updated.
 */
function iats_civicrm_merge($type, &$data, $mainId = NULL, $otherId = NULL, $tables = NULL) {
  if ('cidRefs' == $type) {
    $data['civicrm_iats_verify'] = array('cid');
  }
}

/**
 * Hook_civicrm_pre.
 *
 * Handle special cases of creating contribution (regular and recurring) records when using IATS Payments.
 *
 * 1. CiviCRM assumes all recurring contributions need to be confirmed using the IPN mechanism. This is not true for iATS recurring contributions.
 * So when creating a contribution that is part of a recurring series, test for status = 2, and set to status = 1 instead, unless we're using the fixed day feature
 * Do this only for the initial contribution record.
 * The (subsequent) recurring contributions' status id is set explicitly in the job that creates it, this modification breaks that process.
 *
 * 2. For ACH/EFT, we also have the opposite problem - all contributions will need to verified by iATS and only later set to status success or
 * failed via the acheft verify job. We also want to modify the payment instrument from CC to ACH/EFT
 *
 * TODO: update this code with constants for the various id values of 1 and 2.
 * TODO: CiviCRM should have nicer ways to handle this.
 */
function iats_civicrm_pre($op, $objectName, $objectId, &$params) {
  // If I've set fixed monthly recurring dates, force any iats recurring contribution schedule records to comply.
  if (('ContributionRecur' == $objectName) && ('create' == $op || 'edit' == $op) && !empty($params['payment_processor_id'])) {
    if ($type = _iats_civicrm_is_iats($params['payment_processor_id'])) {
      if (!empty($params['next_sched_contribution_date'])) {
        $settings = CRM_Core_BAO_Setting::getItem('iATS Payments Extension', 'iats_settings');
        $allow_days = empty($settings['days']) ? array('-1') : $settings['days'];
        // Force one of the fixed days, and set the cycle_day at the same time.
        if (0 < max($allow_days)) {
          $init_time = ('create' == $op) ? time() : strtotime($params['next_sched_contribution_date']);
          $from_time = CRM_Iats_Transaction::contributionrecur_next($init_time, $allow_days);
          $params['next_sched_contribution_date'] = date('YmdHis', $from_time);
          // Day of month without leading 0.
          $params['cycle_day'] = date('j', $from_time);
        }
      }
      // Fix a civi bug while I'm here.
      if (empty($params['installments'])) {
        $params['installments'] = '0';
      }
    }
  }
}

function iats_get_setting($key = NULL) {
  static $settings;
  if (empty($settings)) { 
    $settings = CRM_Core_BAO_Setting::getItem('iATS Payments Extension', 'iats_settings');
  }
  return empty($key) ?  $settings : (isset($settings[$key]) ? $settings[$key] : '');
}

/**
 * The contribution itself doesn't tell you which payment processor it came from
 * So we have to dig back via the contribution_recur_id that it is associated with.
 */
function _iats_civicrm_get_payment_processor_id($contribution_recur_id) {
  $params = array(
    'id' => $contribution_recur_id,
  );
  try {
    $result = civicrm_api3('ContributionRecur', 'getsingle', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    return FALSE;
  }
  if (empty($result['payment_processor_id'])) {
    return FALSE;
    // TODO: log error.
  }
  return $result['payment_processor_id'];
}

/**
 * Utility function to see if a payment processor id is using one of the iATS payment processors.
 *
 * This function relies on our naming convention for the iats payment processor classes, staring with the string Payment_iATSService.
 */
function _iats_civicrm_is_iats($payment_processor_id) {
  if (empty($payment_processor_id)) {
    return FALSE;
  }
  $params = array(
    'id' => $payment_processor_id,
  );
  try {
    $result = civicrm_api3('PaymentProcessor', 'getsingle', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    return FALSE;
    // TODO: log error?.
  }
  if (empty($result['class_name'])) {
    return FALSE;
    // TODO: log error?.
  }
  // type is class name with Payment_ stripped from the front
  $type = substr($result['class_name'], 8);
  $is_iats = (0 == strpos($type, 'iATSService')) || (0 == strpos($type, 'Faps'));
  return ($is_iats ? $type : FALSE);
}

/**
 * Internal utility function: return the id's of any iATS processors matching various conditions.
 *
 * class: the payment object class name to match (prefixed w/ 'Payment_')
 * processors: an array of payment processors indexed by id to filter by
 * params: an array of additional params to pass to the api call.
 */
function _iats_filter_payment_processors($class, $processors = array(), $params = array()) {
  $list = array();
  $params['class_name'] = ['LIKE' => 'Payment_' . $class];
  // On the chance that there are a lot of payment processors and the caller
  // hasn't specified a limit, assume they want them all.
  if (empty($params['options']['limit'])) {
    $params['options']['limit'] = 0;
  }
  // Set the domain id if not passed in.
  if (!array_key_exists('domain_id', $params)) {
    $params['domain_id']    = CRM_Core_Config::domainID();
  }
  $params['sequential'] = FALSE; // return list indexed by processor id
  $result = civicrm_api3('PaymentProcessor', 'get', $params);
  if (0 == $result['is_error'] && count($result['values']) > 0) {
    $list = (0 < count($processors)) ? array_intersect_key($result['values'], $processors) : $result['values'];
  }
  return $list;
}


/**
 * Internal utility function: return a list of the payment processors attached
 * to a contribution form of variable class
 * */
function _iats_get_form_payment_processors($form) {
  $form_class = get_class($form);

  if ($form_class == 'CRM_Financial_Form_Payment') {
    // We're on CRM_Financial_Form_Payment, we've got just one payment processor
    $id = $form->_paymentProcessor['id'];
    return array($id => $form->_paymentProcessor);
  }
  else { 
    // Handle the legacy: event and contribution page forms
    if (empty($form->_paymentProcessors)) {
      if (empty($form->_paymentProcessorIDs)) {
        return;
      }
      else {
        return array_fill_keys($form->_paymentProcessorIDs, 1);
      }
    }
    else {
      return $form->_paymentProcessors;
    }
  }
}

/**
 *
 */
function iats_getCurrency($form) {
  // getting the currency depends on the form class
  $form_class = get_class($form);
  $currency = '';
  switch($form_class) {
    case 'CRM_Contribute_Form_Contribution':
    case 'CRM_Contribute_Form_Contribution_Main':
    case 'CRM_Member_Form_Membership':
      $currency = $form->_values['currency'];
      break;
    case 'CRM_Financial_Form_Payment':
      // This is the new ajax-loaded payment form.
      $currency = $form->getCurrency();
      break;
    case 'CRM_Event_Form_Participant':
    case 'CRM_Event_Form_Registration_Register':
      $currency = $form->_values['event']['currency'];
      break;
  }
  if (empty($currency)) {
    // This may occur in edge cases, so don't break, though the form won't be rendered correctly.
    // See comment on civicrm core commit f61437d
    CRM_Core_Error::debug_var($form_class, $form);
  }
  return $currency;
}

/**
 * Provide helpful links to backend-ish payment pages for ACH/EFT, since the backend credit card pages don't work/apply
 * Could do the same for swipe?
 */
function iats_civicrm_buildForm_CRM_Contribute_Form_Search(&$form) {
  // Ignore invocations that aren't for a specific contact, e.g. the civicontribute dashboard.
  if (empty($form->_defaultValues['contact_id'])) {
    return;
  }
  $contactID = $form->_defaultValues['contact_id'];
  $acheft = _iats_filter_payment_processors('iATSServiceACHEFT', array(), array('is_active' => 1, 'is_test' => 0));
  $acheft_backoffice_links = array();
  // For each ACH/EFT payment processor, try to provide a different mechanism for 'backoffice' type contributions
  // note: only offer payment pages that provide iATS ACH/EFT exclusively.
  foreach (array_keys($acheft) as $pp_id) {
    $params = array('is_active' => 1, 'payment_processor' => $pp_id);
    $result = civicrm_api3('ContributionPage', 'get', $params);
    if (0 == $result['is_error'] && count($result['values']) > 0) {
      foreach ($result['values'] as $page) {
        $url = CRM_Utils_System::url('civicrm/contribute/transact', 'reset=1&cid=' . $contactID . '&id=' . $page['id']);
        $acheft_backoffice_links[] = array('url' => $url, 'title' => $page['title']);
      }
    }
  }
  if (count($acheft_backoffice_links)) {
    CRM_Core_Resources::singleton()->addVars('iatspayments', array('backofficeLinks' => $acheft_backoffice_links));
    CRM_Core_Resources::singleton()->addScriptFile('com.iatspayments.civicrm', 'js/contribute_form_search.js');
  }
}

/**
 * If this recurring contribution sequence is using an iATS payment processor,
 * modify the recurring contribution cancelation form to exclude the confusing message about sending the request to the backend.
 */
function iats_civicrm_buildForm_CRM_Contribute_Form_CancelSubscription(&$form) {
  $crid = CRM_Utils_Request::retrieve('crid', 'Integer', $form, FALSE);
  try {
    $recur = civicrm_api3('ContributionRecur', 'getsingle', array('id' => $crid));
  }
  catch (CiviCRM_API3_Exception $e) {
    return;
  }
  if (_iats_civicrm_is_iats($recur['payment_processor_id'])) {
    if ($form->elementExists('send_cancel_request')) {
      $form->removeElement('send_cancel_request');
    }
  }
}

/**
 * Add some functionality to the update subscription form for recurring contributions.
 */
function iats_civicrm_buildForm_CRM_Contribute_Form_UpdateSubscription(&$form) {

  /* For 4.7, we implement getEditableRecurringScheduleFields but still need this for additional niceness */

  // Only do this if the user is allowed to edit contributions. A more stringent permission might be smart.
  if (!CRM_Core_Permission::check('edit contributions')) {
    return;
  }
  // Only mangle this form for recurring contributions using iATS
  $payment_processor_type = empty($form->_paymentProcessor) ? substr(get_class($form->_paymentProcessorObj),9) : $form->_paymentProcessor['class_name'];
  if (  (0 !== strpos($payment_processor_type, 'Payment_iATSService'))
     && (0 !== strpos($payment_processor_type, 'Payment_Faps')) ){
    return;
  }
  $settings = civicrm_api3('Setting', 'getvalue', array('name' => 'iats_settings'));
  // don't do this if the site administrator has disabled it.
  if (!empty($settings['no_edit_extra'])) {
    return;
  }
  $allow_days = empty($settings['days']) ? array('-1') : $settings['days'];
  if (0 < max($allow_days)) {
    $userAlert = ts('Your next scheduled contribution date will automatically be updated to the next allowable day of the month: %1', 
      array(1 => implode(', ', $allow_days)));
    CRM_Core_Session::setStatus($userAlert, ts('Warning'), 'alert');
  }
  $crid = CRM_Utils_Request::retrieve('crid', 'Integer', $form, FALSE);
  /* get the recurring contribution record and the contact record, or quit */
  try {
    $recur = civicrm_api3('ContributionRecur', 'getsingle', array('id' => $crid));
  }
  catch (CiviCRM_API3_Exception $e) {
    return;
  }
  try {
    $contact = civicrm_api3('Contact', 'getsingle', array('id' => $recur['contact_id']));
  }
  catch (CiviCRM_API3_Exception $e) {
    return;
  }
  try {
    $pp = civicrm_api3('PaymentProcessor', 'getsingle', array('id' => $recur['payment_processor_id']));
  }
  catch (CiviCRM_API3_Exception $e) {
    $pp = array();
  }
  // Turn off default notification checkbox, because that's a better default.
  $defaults = array('is_notify' => 0);
  $edit_fields = array(
    'contribution_status_id' => 'Status',
    'next_sched_contribution_date' => 'Next Scheduled Contribution',
    'start_date' => 'Start Date',
    'is_email_receipt' => 'Email receipt for each Contribution in this Recurring Series',
  );
  $dupe_fields = array();
  // To be a good citizen, I check if core or another extension hasn't already added these fields 
  // and don't add them again if they have.
  foreach (array_keys($edit_fields) as $fid) {
    if ($form->elementExists($fid)) {
      unset($edit_fields[$fid]);
      $dupe_fields[] = str_replace('_','-',$fid);
    }
    elseif (isset($recur[$fid])) {
      $defaults[$fid] = $recur[$fid];
    }
  }
  // Use this in my js to identify which fields need to be removed from the tpl I inject below
  CRM_Core_Resources::singleton()->addVars('iatspayments', array('dupeSubscriptionFields' => $dupe_fields));
  foreach ($edit_fields as $fid => $label) {
    switch($fid) {
      case 'contribution_status_id':
        $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
        $form->addElement('select', 'contribution_status_id', ts('Status'), $contributionStatus);
        break;
      case 'is_email_receipt':
        $receiptStatus = array('0' => 'No', '1' => 'Yes');
        $form->addElement('select', $fid, ts($label), $receiptStatus);
        break;
      default:
        $form->addDateTime($fid, ts($label));
        break;
    }
  }
  $form->setDefaults($defaults);
  // Now add some more fields for display only
  /* Add in the contact's name */
  $form->addElement('static', 'contact', $contact['display_name']);
  // get my pp, if available.
  $pp_label = empty($pp['name']) ? $form->_paymentProcessor['name'] : $pp['name'];
  $form->addElement('static', 'payment_processor', $pp_label);
  $label = CRM_Contribute_Pseudoconstant::financialType($recur['financial_type_id']);
  $form->addElement('static', 'financial_type', $label);
  $labels = CRM_Contribute_Pseudoconstant::paymentInstrument();
  $label = $labels[$recur['payment_instrument_id']];
  $form->addElement('static', 'payment_instrument', $label);
  $form->addElement('static', 'failure_count', $recur['failure_count']);
  CRM_Core_Region::instance('page-body')->add(array(
    'template' => 'CRM/Iats/Subscription.tpl',
  ));
  CRM_Core_Resources::singleton()->addScriptFile('com.iatspayments.civicrm', 'js/subscription.js');
}

function iats_civicrm_buildForm_CRM_Contribute_Form_UpdateBilling(&$form) {
  // add hidden form field for the contribution recur ID taken from URL
  // if not specified directly, look it up via a membership ID
  $crid = CRM_Utils_Array::value('crid', $_GET);
  if (!$crid) {
    $mid = CRM_Utils_Array::value('mid', $_GET);
    if ($mid) {
      try {
        $crid = civicrm_api3('Membership', 'getvalue', array(
          'id' => $mid,
          'return' => 'contribution_recur_id',
        ));
      }
      catch (CiviCRM_API3_Exception $e) {
        $crid = 0;
      }
    }
  }
  if ($crid) {
    $form->addElement('hidden', 'crid', $crid);
  }
}
