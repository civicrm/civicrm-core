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
 * Create a page for displaying Custom Fields.
 *
 * Heart of this class is the run method which checks
 * for action type and then displays the appropriate
 * page.
 *
 */
class CRM_Custom_Page_Field extends CRM_Core_Page {

  public $useLivePageJS = TRUE;

  /**
   * The group id of the field.
   *
   * @var int
   */
  protected $_gid;

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  private static $_actionLinks;

  /**
   * Get the action links for this page.
   *
   * @return array
   *   array of action links that we need to display for the browse screen
   */
  public static function &actionLinks() {
    if (!isset(self::$_actionLinks)) {
      self::$_actionLinks = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit Field'),
          'url' => 'civicrm/admin/custom/group/field/update',
          'qs' => 'action=update&reset=1&gid=%%gid%%&id=%%id%%',
          'title' => ts('Edit Custom Field'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
        CRM_Core_Action::BROWSE => [
          'name' => ts('Edit Multiple Choice Options'),
          'url' => 'civicrm/admin/custom/group/field/option',
          'qs' => 'reset=1&action=browse&gid=%%gid%%&fid=%%id%%',
          'title' => ts('List Custom Options'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::BROWSE),
        ],
        CRM_Core_Action::PREVIEW => [
          'name' => ts('Preview Field Display'),
          'url' => 'civicrm/admin/custom/group/preview',
          'qs' => 'action=preview&reset=1&fid=%%id%%',
          'title' => ts('Preview Custom Field'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::PREVIEW),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Custom Field'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DISABLE),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Custom Field'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::ENABLE),
        ],
        CRM_Core_Action::EXPORT => [
          'name' => ts('Move'),
          'url' => 'civicrm/admin/custom/group/field/move',
          'class' => 'small-popup',
          'qs' => 'reset=1&fid=%%id%%',
          'title' => ts('Move Custom Field'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::EXPORT),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/custom/group/field/delete',
          'qs' => 'reset=1&id=%%id%%',
          'title' => ts('Delete Custom Field'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        ],
      ];
    }
    return self::$_actionLinks;
  }

  /**
   * Browse all custom group fields.
   *
   * @return void
   */
  public function browse() {
    $resourceManager = CRM_Core_Resources::singleton();
    if (!empty($_GET['new']) && $resourceManager->ajaxPopupsEnabled) {
      $resourceManager->addScriptFile('civicrm', 'js/crm.addNew.js', 999, 'html-header');
    }

    $customField = [];
    $customFieldBAO = new CRM_Core_BAO_CustomField();

    // fkey is gid
    $customFieldBAO->custom_group_id = $this->_gid;
    $customFieldBAO->orderBy('weight, label');
    $customFieldBAO->find();

    while ($customFieldBAO->fetch()) {
      $customField[$customFieldBAO->id] = [];
      CRM_Core_DAO::storeValues($customFieldBAO, $customField[$customFieldBAO->id]);
      $action = array_sum(array_keys(self::actionLinks()));
      if ($customFieldBAO->is_active) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }

      // Remove link to edit option group if there isn't one
      if (!$customFieldBAO->option_group_id) {
        $action -= CRM_Core_Action::BROWSE;
      }

      $customFieldDataType = array_column(CRM_Core_BAO_CustomField::dataType(), 'label', 'id');
      $customField[$customFieldBAO->id]['data_type'] = $customFieldDataType[$customField[$customFieldBAO->id]['data_type']];
      $customField[$customFieldBAO->id]['order'] = $customField[$customFieldBAO->id]['weight'];
      $customField[$customFieldBAO->id]['action'] = CRM_Core_Action::formLink(self::actionLinks(), $action,
        [
          'id' => $customFieldBAO->id,
          'gid' => $this->_gid,
        ],
        ts('more'),
        FALSE,
        'customField.row.actions',
        'CustomField',
        $customFieldBAO->id
      );
    }

    $returnURL = CRM_Utils_System::url('civicrm/admin/custom/group/field', "reset=1&action=browse&gid={$this->_gid}");
    $filter = "custom_group_id = {$this->_gid}";
    CRM_Utils_Weight::addOrder($customField, 'CRM_Core_DAO_CustomField',
      'id', $returnURL, $filter
    );

    $this->assign('customField', $customField);
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   *
   * @return void
   */
  public function run() {
    $this->_gid = CRM_Utils_Request::retrieve('gid', 'Positive', $this, TRUE);

    if (CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $this->_gid, 'is_reserved')) {
      CRM_Core_Error::statusBounce("You cannot add or edit fields in a reserved custom field-set.");
    }

    $groupTitle = CRM_Core_BAO_CustomGroup::getTitle($this->_gid);
    $this->assign('gid', $this->_gid);
    $this->assign('groupTitle', $groupTitle);

    // assign vars to templates
    $this->assign('action', 'browse');

    $this->browse();

    return parent::run();
  }

}
