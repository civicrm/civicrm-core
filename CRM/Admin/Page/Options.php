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
 * Page for displaying list of option groups and option values.
 */
class CRM_Admin_Page_Options extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;

  /**
   * The option group name.
   *
   * @var string
   */
  public static $_gName = NULL;

  /**
   * The option group name in display format (capitalized, without underscores...etc)
   *
   * @var string
   */
  public static $_gLabel = NULL;

  /**
   * The option group id.
   *
   * @var int
   */
  public static $_gId = NULL;

  /**
   * A boolean determining if you can add options to this group in the GUI.
   *
   * @var bool
   */
  public static $_isLocked = FALSE;

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
      self::$_gId = (int) $_GET['gid'];
      self::$_gName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', self::$_gId, 'name');
      self::$_isLocked = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', self::$_gId, 'is_locked');
      $breadCrumb = [
        'title' => ts('Option Groups'),
        'url' => CRM_Utils_System::url('civicrm/admin/options', 'reset=1'),
      ];
      CRM_Utils_System::appendBreadCrumb([$breadCrumb]);
    }
    if (!self::$_gName) {
      self::$_gName = $this->get('gName');
    }
    // If we don't have a group we will browse all groups
    if (!self::$_gName) {
      // Ensure that gName is assigned to the template to prevent smarty notice.
      $this->assign('gName');
      return;
    }
    $this->set('gName', self::$_gName);
    if (!self::$_gId) {
      self::$_gId = (int) CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', self::$_gName, 'id', 'name');
    }

    self::$_gLabel = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', self::$_gId, 'title');
    if (!self::$_gLabel) {
      self::$_gLabel = ts('Option');
    }

    if (self::$_gName == 'acl_role') {
      CRM_Utils_System::setTitle(ts('Manage ACL Roles'));
      // set breadcrumb to append to admin/access
      $breadCrumb = [
        [
          'title' => ts('Access Control'),
          'url' => CRM_Utils_System::url('civicrm/admin/access', 'reset=1'),
        ],
      ];
      CRM_Utils_System::appendBreadCrumb($breadCrumb);
    }
    else {
      CRM_Utils_System::setTitle(ts("%1 Options", [1 => self::$_gLabel]));
    }
    $this->assign('showIsDefault', in_array(self::$_gName,
      [
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
      ]
    ));

    $this->assign('showCounted', self::$_gName === 'participant_role');
    $this->assign('isLocked', self::$_isLocked);
    $this->assign('allowLoggedIn', Civi::settings()->get('allow_mail_from_logged_in_contact'));
    $this->assign('showComponent', self::$_gName === 'activity_type');
    $this->assign('gName', self::$_gName);
    $this->assign('gLabel', self::$_gLabel);
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
    if (!self::$_links) {
      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/options/' . self::$_gName,
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit %1', [1 => self::$_gName]),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable %1', [1 => self::$_gName]),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DISABLE),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable %1', [1 => self::$_gName]),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::ENABLE),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/options/' . self::$_gName,
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete %1 Type', [1 => self::$_gName]),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        ],
      ];

      if (self::$_gName === 'custom_search') {
        $runLink = [
          CRM_Core_Action::FOLLOWUP => [
            'name' => ts('Run'),
            'url' => 'civicrm/contact/search/custom',
            'qs' => 'reset=1&csid=%%value%%',
            'title' => ts('Run %1', [1 => self::$_gName]),
            'class' => 'no-popup',
            'weight' => 10,
          ],
        ];
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
    $groupParams = ['name' => self::$_gName];
    $optionValue = CRM_Core_OptionValue::getRows($groupParams, $this->links(), 'component_id,weight');
    $gName = self::$_gName;
    $returnURL = CRM_Utils_System::url("civicrm/admin/options/$gName",
      "reset=1&group=$gName"
    );
    $filter = "option_group_id = " . self::$_gId;
    CRM_Utils_Weight::addOrder($optionValue, 'CRM_Core_DAO_OptionValue',
      'id', $returnURL, $filter
    );
    $this->assign('hasIcons', FALSE);

    // retrieve financial account name for the payment method page
    foreach ($optionValue as $key => $option) {
      if ($gName === 'payment_instrument') {
        $optionValue[$key]['financial_account'] = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($key, NULL, 'civicrm_option_value', 'financial_account_id.name');
      }
      foreach (['weight', 'description', 'value', 'color', 'label', 'is_default', 'icon'] as $expectedKey) {
        if (!array_key_exists($expectedKey, $option)) {
          $optionValue[$key][$expectedKey] = NULL;
        }
      }
      if ($option['icon']) {
        $this->assign('hasIcons', TRUE);
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
