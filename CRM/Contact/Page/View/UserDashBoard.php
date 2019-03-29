<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class is used to build User Dashboard
 */
class CRM_Contact_Page_View_UserDashBoard extends CRM_Core_Page {
  public $_contactId = NULL;

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
  static $_links = NULL;

  /**
   * @throws Exception
   */
  public function __construct() {
    parent::__construct();

    if (!CRM_Core_Permission::check('access Contact Dashboard')) {
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/dashboard', 'reset=1'));
    }

    $this->_contactId = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $userID = CRM_Core_Session::singleton()->getLoggedInContactID();

    $userChecksum = $this->getUserChecksum();
    $validUser = FALSE;
    if ($userChecksum) {
      $this->assign('userChecksum', $userChecksum);
      $validUser = CRM_Contact_BAO_Contact_Utils::validChecksum($this->_contactId, $userChecksum);
      $this->_isChecksumUser = $validUser;
    }

    if (!$this->_contactId) {
      $this->_contactId = $userID;
    }
    elseif ($this->_contactId != $userID && !$validUser) {
      if (!CRM_Contact_BAO_Contact_Permission::allow($this->_contactId, CRM_Core_Permission::VIEW)) {
        CRM_Core_Error::fatal(ts('You do not have permission to access this contact.'));
      }
      if (!CRM_Contact_BAO_Contact_Permission::allow($this->_contactId, CRM_Core_Permission::EDIT)) {
        $this->_edit = FALSE;
      }
    }
  }

  /**
   * Heart of the viewing process.
   *
   * The runner gets all the meta data for the contact and calls the appropriate type of page to view.
   */
  public function preProcess() {
    if (!$this->_contactId) {
      CRM_Core_Error::fatal(ts('You must be logged in to view this page.'));
    }

    list($displayName, $contactImage) = CRM_Contact_BAO_Contact::getDisplayAndImage($this->_contactId);

    $this->set('displayName', $displayName);
    $this->set('contactImage', $contactImage);

    CRM_Utils_System::setTitle(ts('Dashboard - %1', [1 => $displayName]));

    $this->assign('recentlyViewed', FALSE);
  }

  /**
   * Build user dashboard.
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

    // CRM-16512 - Hide related contact table if user lacks permission to view self
    if (!empty($dashboardOptions['Permissioned Orgs']) && CRM_Core_Permission::check('view my contact')) {
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
      list($pcpBlock, $pcpInfo) = CRM_PCP_BAO_PCP::getPcpDashboardInfo($this->_contactId);
      $this->assign('pcpBlock', $pcpBlock);
      $this->assign('pcpInfo', $pcpInfo);
    }

    if (!empty($dashboardOptions['Assigned Activities']) && empty($this->_isChecksumUser)) {
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
    $this->assign('dashboardElements', $dashboardElements);

    // return true when 'Invoices / Credit Notes' checkbox is checked
    $this->assign('invoices', $dashboardOptions['Invoices / Credit Notes']);

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
      CRM_Core_DAO::$_nullObject,
      self::$_links
    );
    return self::$_links;
  }

  /**
   * Get the user checksum from the url to use in links.
   *
   * @return string
   */
  protected function getUserChecksum() {
    $userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $this);
    if (empty($userID) && $this->_contactId) {
      return $userChecksum;
    }
    return FALSE;
  }

}
