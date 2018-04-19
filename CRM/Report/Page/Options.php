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
 * Page for displaying list of Gender
 */
class CRM_Report_Page_Options extends CRM_Core_Page_Basic {

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
  static $_GName = NULL;

  /**
   * The option group id.
   *
   * @var array
   */
  static $_gId = NULL;

  /**
   * Obtains the group name from url and sets the title.
   */
  public function preProcess() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE);
    $this->_id = CRM_Utils_Request::retrieve('id', 'String', $this, FALSE);

    self::$_gName = "report_template";

    if (self::$_gName) {
      self::$_gId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', self::$_gName, 'id', 'name');
    }
    else {
      CRM_Core_Error::fatal();
    }

    self::$_GName = ucwords(str_replace('_', ' ', self::$_gName));

    $this->assign('GName', self::$_GName);
    $newReportURL = CRM_Utils_System::url("civicrm/admin/report/register",
      'reset=1'
    );
    $this->assign('newReport', $newReportURL);
    CRM_Utils_System::setTitle(ts('Registered Templates'));
  }

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Core_BAO_OptionValue';
  }

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links.
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/report/register/' . self::$_gName,
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
          'url' => 'civicrm/admin/report/register/' . self::$_gName,
          'qs' => 'action=delete&id=%%id%%&reset=1',
          'title' => ts('Delete %1 Type', array(1 => self::$_gName)),
        ),
      );
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
    $groupParams = array('name' => self::$_gName);
    $optionValue = CRM_Core_OptionValue::getRows($groupParams, $this->links(), 'weight');
    $gName = self::$_gName;
    $returnURL = CRM_Utils_System::url("civicrm/admin/report/options/$gName",
      "reset=1"
    );
    $filter = "option_group_id = " . self::$_gId;

    $session = new CRM_Core_Session();
    $session->replaceUserContext($returnURL);
    CRM_Utils_Weight::addOrder($optionValue, 'CRM_Core_DAO_OptionValue',
      'id', $returnURL, $filter
    );
    $this->assign('rows', $optionValue);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Report_Form_Register';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return self::$_GName;
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
    return 'civicrm/report/options/' . self::$_gName;
  }

  /**
   * Get userContext params.
   *
   * @param int $mode
   *   Mode that we are in.
   *
   * @return string
   */
  public function userContextParams($mode = NULL) {
    return 'reset=1&action=browse';
  }

}
