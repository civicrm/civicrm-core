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
 * This class is used to build User Dashboard
 */
class CRM_Contact_Page_View_UserDashBoard extends CRM_Core_Page {
  public $_contactId;

  /**
   * Always show public groups.
   * @var bool
   */
  public $_onlyPublicGroups = TRUE;

  public $_edit = TRUE;

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links;

  /**
   * @throws Exception
   */
  public function __construct() {
    parent::__construct();

    if (!CRM_Core_Permission::check('access Contact Dashboard')) {
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/dashboard', 'reset=1'));
    }

    $this->_contactId = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $userID = CRM_Core_Session::getLoggedInContactID();

    $userChecksum = $this->getUserChecksum();
    $this->assign('userChecksum', $userChecksum);

    if (!$this->_contactId) {
      $this->_contactId = $userID;
    }
    elseif ($this->_contactId != $userID && !$userChecksum) {
      if (!CRM_Contact_BAO_Contact_Permission::allow($this->_contactId, CRM_Core_Permission::VIEW)) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to access this contact.'));
      }
      if (!CRM_Contact_BAO_Contact_Permission::allow($this->_contactId, CRM_Core_Permission::EDIT)) {
        $this->_edit = FALSE;
      }
    }
  }

  /**
   * Heart of the viewing process.
   *
   * The runner gets all the meta data for the contact and calls the
   * appropriate type of page to view.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    if (!$this->_contactId) {
      throw new CRM_Core_Exception(ts('You must be logged in to view this page.'));
    }

    [$displayName, $contactImage] = CRM_Contact_BAO_Contact::getDisplayAndImage($this->_contactId);

    $this->set('displayName', $displayName);
    $this->set('contactImage', $contactImage);

    CRM_Utils_System::setTitle(ts('Dashboard - %1', [1 => $displayName]));

    $this->assign('recentlyViewed', FALSE);
  }

  /**
   * Build user dashboard.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildUserDashBoard() {
    //build component selectors
    $dashboardElements = [];

    $dashboardOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'user_dashboard_options'
    );

    $components = CRM_Core_Component::getEnabledComponents();
    $this->assign('contactId', $this->_contactId);
    foreach ($components as $name => $component) {
      $elem = $component->getUserDashboardElement();
      if (!$elem) {
        continue;
      }

      if (!empty($dashboardOptions[$name]) &&
        (CRM_Core_Permission::access($component->name) ||
          CRM_Core_Permission::check($elem['perm'][0])
        )
      ) {

        $userDashboard = $component->getUserDashboardObject();
        $dashboardElements[] = [
          'class' => 'crm-dashboard-' . strtolower($component->name),
          'sectionTitle' => $elem['title'],
          'templatePath' => $userDashboard->getTemplateFileName(),
          'weight' => $elem['weight'],
        ];
        $userDashboard->run();
      }
    }

    // Relationship section
    // FIXME - this used to share code with the contact summary "Relationships" tab
    // now that tab has been switched to use SearchKit, and this ought to be switched as well;
    // then remove all code commented with "only-used-by-user-dashboard"
    if (!empty($dashboardOptions['Permissioned Orgs']) && CRM_Core_Permission::check('view my contact')) {
      $columnHeaders = CRM_Contact_BAO_Relationship::getColumnHeaders();
      $contactRelationships = $selector = NULL;
      CRM_Utils_Hook::searchColumns('relationship.columns', $columnHeaders, $contactRelationships, $selector);
      $this->assign('columnHeaders', $columnHeaders);
      $this->assign('entityInClassFormat', 'relationship');
      $dashboardElements[] = [
        'class' => 'crm-dashboard-permissionedOrgs',
        'templatePath' => 'CRM/Contact/Page/View/RelationshipSelector.tpl',
        'sectionTitle' => ts('Your Contacts / Organizations'),
        'weight' => 40,
      ];

    }

    if (!empty($dashboardOptions['PCP'])) {
      $dashboardElements[] = [
        'class' => 'crm-dashboard-pcp',
        'templatePath' => 'CRM/Contribute/Page/PcpUserDashboard.tpl',
        'sectionTitle' => ts('Personal Campaign Pages'),
        'weight' => 40,
      ];
      [$pcpBlock, $pcpInfo] = CRM_PCP_BAO_PCP::getPcpDashboardInfo((int) $this->_contactId);
      $this->assign('pcpBlock', $pcpBlock);
      $this->assign('pcpInfo', $pcpInfo);
    }

    if (!empty($dashboardOptions['Assigned Activities']) && !$this->getUserChecksum()) {
      // Assigned Activities section
      $dashboardElements[] = [
        'class' => 'crm-dashboard-assignedActivities',
        'templatePath' => 'CRM/Activity/Page/UserDashboard.tpl',
        'sectionTitle' => ts('Your Assigned Activities'),
        'weight' => 5,
      ];
      $userDashboard = new CRM_Activity_Page_UserDashboard();
      $userDashboard->run();
    }

    usort($dashboardElements, ['CRM_Utils_Sort', 'cmpFunc']);
    foreach ($dashboardElements as $index => $dashboardElement) {
      // Ensure property is set to avoid smarty notices
      if (!array_key_exists('class', $dashboardElement)) {
        $dashboardElements[$index]['class'] = NULL;
      }
    }
    $this->assign('dashboardElements', $dashboardElements);

    if (!empty($dashboardOptions['Groups'])) {
      $this->assign('showGroup', TRUE);
      //build group selector
      $gContact = new CRM_Contact_Page_View_UserDashBoard_GroupContact();
      $gContact->run();
    }
    else {
      $this->assign('showGroup', FALSE);
    }
  }

  /**
   * Perform actions and display for user dashboard.
   */
  public function run() {
    $this->preProcess();
    $this->buildUserDashBoard();
    return parent::run();
  }

  /**
   * Get action links.
   *
   * @return array
   *   (reference) of action links
   */
  public static function &links() {
    if (!(self::$_links)) {
      $disableExtra = ts('Are you sure you want to disable this relationship?');

      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit Contact Information'),
          'url' => 'civicrm/contact/relatedcontact',
          'qs' => 'action=update&reset=1&cid=%%cbid%%&rcid=%%cid%%',
          'title' => ts('Edit Contact Information'),
        ],
        CRM_Core_Action::VIEW => [
          'name' => ts('Dashboard'),
          'url' => 'civicrm/user',
          'class' => 'no-popup',
          'qs' => 'reset=1&id=%%cbid%%',
          'title' => ts('View Contact Dashboard'),
        ],
      ];

      if (CRM_Core_Permission::check('access CiviCRM')) {
        self::$_links += [
          CRM_Core_Action::DISABLE => [
            'name' => ts('Disable'),
            'url' => 'civicrm/contact/view/rel',
            'qs' => 'action=disable&reset=1&cid=%%cid%%&id=%%id%%&rtype=%%rtype%%&selectedChild=rel&context=dashboard',
            'extra' => 'onclick = "return confirm(\'' . $disableExtra . '\');"',
            'title' => ts('Disable Relationship'),
          ],
        ];
      }
    }

    // call the hook so we can modify it
    CRM_Utils_Hook::links('view.contact.userDashBoard',
      'Contact',
      NULL,
      self::$_links
    );
    return self::$_links;
  }

  /**
   * Get the user checksum from the url to use in links.
   *
   * @return string|false
   * @throws \CRM_Core_Exception
   */
  protected function getUserChecksum() {
    $userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $this);
    if ($this->_contactId && CRM_Contact_BAO_Contact_Utils::validChecksum($this->_contactId, $userChecksum)) {
      return $userChecksum;
    }
    return FALSE;
  }

}
