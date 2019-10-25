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
 * Create a page for displaying CiviCRM Profile Fields.
 *
 * Heart of this class is the run method which checks
 * for action type and then displays the appropriate
 * page.
 *
 */
class CRM_UF_Page_Field extends CRM_Core_Page {

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
   */
  public static function &actionLinks() {
    if (!isset(self::$_actionLinks)) {
      self::$_actionLinks = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/uf/group/field/update',
          'qs' => 'reset=1&action=update&id=%%id%%&gid=%%gid%%',
          'title' => ts('Edit CiviCRM Profile Field'),
        ],
        CRM_Core_Action::PREVIEW => [
          'name' => ts('Preview'),
          'url' => 'civicrm/admin/uf/group/field',
          'qs' => 'action=preview&id=%%id%%&field=1',
          'title' => ts('Preview CiviCRM Profile Field'),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable CiviCRM Profile Field'),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable CiviCRM Profile Field'),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/uf/group/field',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Enable CiviCRM Profile Field'),
        ],
      ];
    }
    return self::$_actionLinks;
  }

  /**
   * Browse all CiviCRM Profile group fields.
   *
   * @return void
   */
  public function browse() {
    $resourceManager = CRM_Core_Resources::singleton();
    if (!empty($_GET['new']) && $resourceManager->ajaxPopupsEnabled) {
      $resourceManager->addScriptFile('civicrm', 'js/crm.addNew.js', 999, 'html-header');
    }

    $ufField = [];
    $ufFieldBAO = new CRM_Core_BAO_UFField();

    // fkey is gid
    $ufFieldBAO->uf_group_id = $this->_gid;
    $ufFieldBAO->orderBy('weight', 'field_name');
    $ufFieldBAO->find();

    $otherModules = CRM_Core_BAO_UFGroup::getUFJoinRecord($this->_gid);
    $this->assign('otherModules', $otherModules);

    $isGroupReserved = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $this->_gid, 'is_reserved');
    $this->assign('isGroupReserved', $isGroupReserved);

    $isMixedProfile = CRM_Core_BAO_UFField::checkProfileType($this->_gid);
    if ($isMixedProfile) {
      $this->assign('skipCreate', TRUE);
    }

    $locationType = [];
    $locationType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');

    $fields = CRM_Contact_BAO_Contact::exportableFields('All', FALSE, TRUE);
    $fields = array_merge(CRM_Contribute_BAO_Contribution::getContributionFields(), $fields);

    $select = [];
    foreach ($fields as $name => $field) {
      if ($name) {
        $select[$name] = $field['title'];
      }
    }
    $select['group'] = ts('Group(s)');
    $select['tag'] = ts('Tag(s)');

    $visibility = CRM_Core_SelectValues::ufVisibility();
    while ($ufFieldBAO->fetch()) {
      $ufField[$ufFieldBAO->id] = [];
      $phoneType = $locType = '';
      CRM_Core_DAO::storeValues($ufFieldBAO, $ufField[$ufFieldBAO->id]);
      $ufField[$ufFieldBAO->id]['visibility_display'] = $visibility[$ufFieldBAO->visibility];

      $ufField[$ufFieldBAO->id]['label'] = $ufFieldBAO->label;

      $action = array_sum(array_keys(self::actionLinks()));
      if ($ufFieldBAO->is_active) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }

      if ($ufFieldBAO->is_reserved) {
        $action -= CRM_Core_Action::UPDATE;
        $action -= CRM_Core_Action::DISABLE;
        $action -= CRM_Core_Action::DELETE;
      }
      $ufField[$ufFieldBAO->id]['order'] = $ufField[$ufFieldBAO->id]['weight'];
      $ufField[$ufFieldBAO->id]['action'] = CRM_Core_Action::formLink(self::actionLinks(),
        $action,
        [
          'id' => $ufFieldBAO->id,
          'gid' => $this->_gid,
        ],
        ts('more'),
        FALSE,
        'ufField.row.actions',
        'UFField',
        $ufFieldBAO->id
      );
    }

    $returnURL = CRM_Utils_System::url('civicrm/admin/uf/group/field',
      "reset=1&action=browse&gid={$this->_gid}"
    );
    $filter = "uf_group_id = {$this->_gid}";
    CRM_Utils_Weight::addOrder($ufField, 'CRM_Core_DAO_UFField',
      'id', $returnURL, $filter
    );

    $this->assign('ufField', $ufField);

    // retrieve showBestResult from session
    $session = CRM_Core_Session::singleton();
    $showBestResult = $session->get('showBestResult');
    $this->assign('showBestResult', $showBestResult);
    $session->set('showBestResult', 0);
  }

  /**
   * Edit CiviCRM Profile data.
   *
   * editing would involved modifying existing fields + adding data to new fields.
   *
   * @param string $action
   *   The action to be invoked.
   *
   * @return void
   */
  public function edit($action) {
    // create a simple controller for editing CiviCRM Profile data
    $controller = new CRM_Core_Controller_Simple('CRM_UF_Form_Field', ts('CiviCRM Profile Field'), $action);

    // set the userContext stack
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/uf/group/field',
      'reset=1&action=browse&gid=' . $this->_gid
    ));
    $controller->set('gid', $this->_gid);
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();
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
    // get the group id
    $this->_gid = CRM_Utils_Request::retrieve('gid', 'Positive',
      $this, FALSE, 0
    );

    if ($this->_gid) {
      $groupTitle = CRM_Core_BAO_UFGroup::getTitle($this->_gid);
      $this->assign('gid', $this->_gid);
      $this->assign('groupTitle', $groupTitle);
      CRM_Utils_System::setTitle(ts('%1 - CiviCRM Profile Fields', [1 => $groupTitle]));
    }

    // get the requested action
    $action = CRM_Utils_Request::retrieve('action', 'String',
      // default to 'browse'
      $this, FALSE, 'browse'
    );

    // assign vars to templates
    $this->assign('action', $action);

    $id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE, 0
    );

    // what action to take ?
    if ($action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD | CRM_Core_Action::VIEW | CRM_Core_Action::DELETE)) {
      // no browse for edit/update/view/delete
      $this->edit($action);
    }
    elseif ($action & CRM_Core_Action::PREVIEW) {
      $this->preview($id, $this->_gid);
    }
    else {
      $this->browse();
    }

    // Call the parents run method
    return parent::run();
  }

  /**
   * Preview custom field.
   *
   * @param int $fieldId
   *   Custom field id.
   * @param int $groupId
   *
   * @return void
   */
  public function preview($fieldId, $groupId) {
    $controller = new CRM_Core_Controller_Simple('CRM_UF_Form_Preview', ts('Preview Custom Data'), CRM_Core_Action::PREVIEW);
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/uf/group/field',
      'reset=1&action=browse&gid=' . $this->_gid
    ));
    $controller->set('fieldId', $fieldId);
    $controller->set('id', $groupId);
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();
  }

}
