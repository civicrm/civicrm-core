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
 * Page for displaying list of report options
 */
class CRM_Report_Page_Options extends CRM_Core_Page_Basic {

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
   * @var string|null
   */
  public static $_gName = NULL;

  /**
   * The option group name in display format (capitalized, without underscores...etc)
   *
   * @var string|null
   */
  public static $_gLabel = NULL;

  /**
   * The option group id.
   *
   * @var int|null
   */
  public static $_gId = NULL;

  /**
   * Obtains the group name from url and sets the title.
   */
  public function preProcess() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE);
    $this->_id = CRM_Utils_Request::retrieve('id', 'String', $this, FALSE);

    self::$_gName = "report_template";
    self::$_gLabel = ts("Report Template");
    self::$_gId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', self::$_gName, 'id', 'name');

    $this->assign('gName', self::$_gName);
    $this->assign('gLabel', self::$_gLabel);
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
      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/report/register/' . self::$_gName,
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit %1', [1 => self::$_gLabel]),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable %1', [1 => self::$_gLabel]),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DISABLE),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable %1', [1 => self::$_gLabel]),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::ENABLE),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/report/register/' . self::$_gName,
          'qs' => 'action=delete&id=%%id%%&reset=1',
          'title' => ts('Delete %1 Type', [1 => self::$_gLabel]),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        ],
      ];
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
    $groupParams = ['name' => self::$_gName];
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
