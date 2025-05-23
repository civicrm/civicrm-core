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
 * Page for displaying list of Providers
 */
class CRM_SMS_Page_Provider extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_SMS_BAO_SmsProvider';
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   */
  public function run() {
    // set title and breadcrumb
    CRM_Utils_System::setTitle(ts('Settings - SMS Provider'));
    $breadCrumb = [
      [
        'title' => ts('SMS Provider'),
        'url' => CRM_Utils_System::url('civicrm/admin/sms/provider',
          'reset=1'
        ),
      ],
    ];
    CRM_Utils_System::appendBreadCrumb($breadCrumb);

    return parent::run();
  }

  /**
   * Browse all Providers.
   *
   * @param array $action
   */
  public function browse($action = NULL) {
    $providers = CRM_SMS_BAO_SmsProvider::getProviders();
    $rows = [];
    foreach ($providers as $provider) {
      $action = array_sum(array_keys($this->links()));
      // update enable/disable links.
      if ($provider['is_active']) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }

      $apiTypes = CRM_Core_OptionGroup::values('sms_api_type', FALSE, FALSE, FALSE, NULL, 'label');
      $provider['api_type'] = $apiTypes[$provider['api_type']];

      $provider['action'] = CRM_Core_Action::formLink(self::links(), $action,
        ['id' => $provider['id']],
        ts('more'),
        FALSE,
        'sms.provider.row',
        'SMSProvider',
        $provider['id']
      );
      $rows[] = $provider;
    }
    $this->assign('rows', $rows);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_SMS_Form_Provider';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'SMS Provider';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string
   *   user context.
   */
  public function userContext($mode = NULL) {
    return 'civicrm/admin/sms/provider';
  }

}
