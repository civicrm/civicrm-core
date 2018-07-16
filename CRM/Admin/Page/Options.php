<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Page for displaying list of Gender.
 */
class CRM_Admin_Page_Options extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  static $_links = NULL;

  /**
   * The option group name.
   *
   * @var array
   */
  static $_gName = NULL;

  /**
   * The option group name in display format (capitalized, without underscores...etc)
   *
   * @var array
   */
  static $_gLabel = NULL;

  /**
   * The option group id.
   *
   * @var array
   */
  static $_gId = NULL;

  /**
   * A boolean determining if you can add options to this group in the GUI.
   *
   * @var boolean
   */
  static $_isLocked = FALSE;

  /**
   * Obtains the group name from url string or id from $_GET['gid'].
   *
   * Sets the title.
   */
  public function preProcess() {
    if (!self::$_gName && !empty($this->urlPath[3])) {
      self::$_gName = $this->urlPath[3];
      self::$_isLocked = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', self::$_gName, 'is_locked', 'name');
    }
    // If an id arg is passed instead of a group name in the path
    elseif (!self::$_gName && !empty($_GET['gid'])) {
      self::$_gId = $_GET['gid'];
      self::$_gName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', self::$_gId, 'name');
      self::$_isLocked = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', self::$_gId, 'is_locked');
      $breadCrumb = array(
        'title' => ts('Option Groups'),
        'url' => CRM_Utils_System::url('civicrm/admin/options', 'reset=1'),
      );
      CRM_Utils_System::appendBreadCrumb(array($breadCrumb));
    }
    if (!self::$_gName) {
      self::$_gName = $this->get('gName');
    }
    // If we don't have a group we will browse all groups
    if (!self::$_gName) {
      return;
    }
    $this->set('gName', self::$_gName);
    if (!self::$_gId) {
      self::$_gId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', self::$_gName, 'id', 'name');
    }

    self::$_gLabel = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', self::$_gId, 'title');
    if (!self::$_gLabel) {
      self::$_gLabel = ts('Option');
    }

    $this->assign('gName', self::$_gName);
    $this->assign('gLabel', self::$_gLabel);

    if (self::$_gName == 'acl_role') {
      CRM_Utils_System::setTitle(ts('Manage ACL Roles'));
      // set breadcrumb to append to admin/access
      $breadCrumb = array(
        array(
          'title' => ts('Access Control'),
          'url' => CRM_Utils_System::url('civicrm/admin/access',
            'reset=1'
          ),
        ),
      );
      CRM_Utils_System::appendBreadCrumb($breadCrumb);
    }
    else {
      CRM_Utils_System::setTitle(ts("%1 Options", array(1 => self::$_gLabel)));
    }
    if (in_array(self::$_gName,
      array(
        'from_email_address',
        'email_greeting',
        'postal_greeting',
        'addressee',
        'communication_style',
        'case_status',
        'encounter_medium',
        'case_type',
        'payment_instrument',
        'soft_credit_type',
        'website_type',
      )
    )) {
      $this->assign('showIsDefault', TRUE);
    }

    if (self::$_gName == 'participant_role') {
      $this->assign('showCounted', TRUE);
    }
    $this->assign('isLocked', self::$_isLocked);
    $this->assign('allowLoggedIn', Civi::settings()->get('allow_mail_from_logged_in_contact'));
    $config = CRM_Core_Config::singleton();
    if (self::$_gName == 'activity_type') {
      $this->assign('showComponent', TRUE);
    }
  }

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return self::$_gName ? 'CRM_Core_BAO_OptionValue' : 'CRM_Core_BAO_OptionGroup';
  }

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/options/' . self::$_gName,
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit %1', array(1 => self::$_gName)),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable %1', array(1 => self::$_gName)),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable %1', array(1 => self::$_gName)),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/options/' . self::$_gName,
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete %1 Type', array(1 => self::$_gName)),
        ),
      );

      if (self::$_gName == 'custom_search') {
        $runLink = array(
          CRM_Core_Action::FOLLOWUP => array(
            'name' => ts('Run'),
            'url' => 'civicrm/contact/search/custom',
            'qs' => 'reset=1&csid=%%value%%',
            'title' => ts('Run %1', array(1 => self::$_gName)),
            'class' => 'no-popup',
          ),
        );
        self::$_links = $runLink + self::$_links;
      }
    }
    return self::$_links;
  }

  /**
   * Run the basic page (run essentially starts execution for that page).
   */
  public function run() {
    $this->preProcess();
    return parent::run();
  }

  /**
   * Browse all options.
   */
  public function browse() {
    if (!self::$_gName) {
      return parent::browse();
    }
    $groupParams = array('name' => self::$_gName);
    $optionValue = CRM_Core_OptionValue::getRows($groupParams, $this->links(), 'component_id,weight');
    $gName = self::$_gName;
    $returnURL = CRM_Utils_System::url("civicrm/admin/options/$gName",
      "reset=1&group=$gName"
    );
    $filter = "option_group_id = " . self::$_gId;
    CRM_Utils_Weight::addOrder($optionValue, 'CRM_Core_DAO_OptionValue',
      'id', $returnURL, $filter
    );

    // retrieve financial account name for the payment method page
    if ($gName = "payment_instrument") {
      foreach ($optionValue as $key => $option) {
        $optionValue[$key]['financial_account'] = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($key, NULL, 'civicrm_option_value', 'financial_account_id.name');
      }
    }
    $this->assign('rows', $optionValue);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return self::$_gName ? 'CRM_Admin_Form_Options' : 'CRM_Admin_Form_OptionGroup';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return self::$_gLabel;
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
    return 'civicrm/admin/options' . (self::$_gName ? '/' . self::$_gName : '');
  }

}
