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
 * Page for displaying list of survey types.
 */
class CRM_Campaign_Page_SurveyType extends CRM_Core_Page_Basic {

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
   * @var array
   */
  protected $_gName;

  /**
   * The option group name in display format (capitalized, without underscores...etc)
   *
   * @var array
   */
  protected $_GName;

  /**
   * The option group id.
   *
   * @var array
   */
  protected $_gid = NULL;

  /**
   * Obtains the group name from url and sets the title.
   */
  public function preProcess() {
    $this->_gName = 'activity_type';

    $this->_gid = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $this->_gName, 'id', 'name');

    $this->_GName = 'Survey Type';

    $this->assign('gName', $this->_gName);
    $this->assign('GName', $this->_GName);

    CRM_Utils_System::setTitle(ts('%1 Options', [1 => $this->_GName]));

    $this->assign('addSurveyType', ["civicrm/admin/campaign/surveyType", "reset=1&action=add"]);
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
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/campaign/surveyType',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit %1', [1 => $this->_gName]),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable %1', [1 => $this->_gName]),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DISABLE),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable %1', [1 => $this->_gName]),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::ENABLE),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/campaign/surveyType',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete %1 Type', [1 => $this->_gName]),
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
    $campaingCompId = CRM_Core_Component::getComponentID('CiviCampaign');
    $groupParams = ['name' => $this->_gName];
    $optionValues = CRM_Core_OptionValue::getRows($groupParams, $this->links(), 'component_id,weight');

    foreach ($optionValues as $key => $optionValue) {
      if (($optionValue['component_id'] ?? NULL) != $campaingCompId) {
        unset($optionValues[$key]);
      }
    }

    $returnURL = CRM_Utils_System::url("civicrm/admin/campaign/surveyType",
      "reset=1"
    );
    $filter = "option_group_id = " . $this->_gid;
    CRM_Utils_Weight::addOrder($optionValues, 'CRM_Core_DAO_OptionValue',
      'id', $returnURL, $filter
    );
    $this->assign('rows', $optionValues);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Campaign_Form_SurveyType';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return $this->_GName;
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
    return 'civicrm/admin/campaign/surveyType';
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
    return 'reset=1';
  }

}
