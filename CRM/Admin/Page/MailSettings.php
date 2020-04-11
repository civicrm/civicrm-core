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
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;

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
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      // helper variable for nicer formatting
      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/mailSettings',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Mail Settings'),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/mailSettings',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Mail Settings'),
        ],
      ];
    }

    return self::$_links;
  }

  /**
   * Browse all mail settings.
   */
  public function browse() {
    //get all mail settings.
    $allMailSettings = [];
    $mailSetting = new CRM_Core_DAO_MailSettings();

    $allProtocols = CRM_Core_PseudoConstant::get('CRM_Core_DAO_MailSettings', 'protocol');

    //multi-domain support for mail settings. CRM-5244
    $mailSetting->domain_id = CRM_Core_Config::domainID();

    //find all mail settings.
    $mailSetting->find();
    while ($mailSetting->fetch()) {
      //replace protocol value with name
      $mailSetting->protocol = $allProtocols[$mailSetting->protocol] ?? NULL;
      CRM_Core_DAO::storeValues($mailSetting, $allMailSettings[$mailSetting->id]);

      //form all action links
      $action = array_sum(array_keys($this->links()));

      // disallow the DELETE action for the default set of settings
      if ($mailSetting->is_default) {
        $action &= ~CRM_Core_Action::DELETE;
      }

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

    $this->assign('rows', $allMailSettings);
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
