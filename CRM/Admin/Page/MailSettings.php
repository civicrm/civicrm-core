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
 * Page for displaying list of Mail account settings.
 */
class CRM_Admin_Page_MailSettings extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Core_BAO_MailSettings';
  }

  /**
   * Browse all mail settings.
   */
  public function browse() {
    $allMailSettings = [];
    $mailSetting = new CRM_Core_DAO_MailSettings();

    $allProtocols = CRM_Core_DAO_MailSettings::buildOptions('protocol');

    //multi-domain support for mail settings. CRM-5244
    $mailSetting->domain_id = CRM_Core_Config::domainID();

    $mailSetting->find();
    while ($mailSetting->fetch()) {
      //replace protocol value with name
      $mailSetting->protocol = $allProtocols[$mailSetting->protocol] ?? NULL;
      CRM_Core_DAO::storeValues($mailSetting, $allMailSettings[$mailSetting->id]);

      $action = array_sum(array_keys($this->links()));

      if ($mailSetting->is_default) {
        $action -= CRM_Core_Action::DELETE;
        $action -= CRM_Core_Action::DISABLE;
      }
      $action -= ($mailSetting->is_active) ? CRM_Core_Action::ENABLE : CRM_Core_Action::DISABLE;

      //add action links.
      $allMailSettings[$mailSetting->id]['action'] = CRM_Core_Action::formLink(self::links(), $action,
        ['id' => $mailSetting->id],
        ts('more'),
        FALSE,
        'mailSetting.manage.action',
        'MailSetting',
        $mailSetting->id
      );
    }
    $expectedKeys = ['server', 'username', 'localpart', 'domain', 'return_path', 'protocol', 'source', 'port', 'is_ssl'];
    foreach ($allMailSettings as $key => $allMailSetting) {
      // make sure they are there to prevent smarty notices.
      $allMailSettings[$key] = array_merge(array_fill_keys($expectedKeys, NULL), $allMailSetting);
    }
    $this->assign('rows', $allMailSettings);

    $setupActions = CRM_Core_BAO_MailSettings::getSetupActions();
    if (count($setupActions) > 1 || !isset($setupActions['standard'])) {
      $this->assign('setupActions', $setupActions);
    }
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Admin_Form_MailSettings';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'Mail Settings';
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
    return 'civicrm/admin/mailSettings';
  }

}
