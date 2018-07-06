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
 * $Id$
 *
 */

/**
 * Create a page for displaying Custom Options.
 *
 * Heart of this class is the run method which checks
 * for action type and then displays the appropriate
 * page.
 *
 */
class CRM_Custom_Page_Option extends CRM_Core_Page {

  public $useLivePageJS = TRUE;

  /**
   * The Group id of the option
   *
   * @var int
   */
  protected $_gid;

  /**
   * The field id of the option
   *
   * @var int
   */
  protected $_fid;

  /**
   * The action links that we need to display for the browse screen
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
      self::$_actionLinks = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit Option'),
          'url' => 'civicrm/admin/custom/group/field/option',
          'qs' => 'reset=1&action=update&id=%%id%%&fid=%%fid%%&gid=%%gid%%',
          'title' => ts('Edit Multiple Choice Option'),
        ),
        CRM_Core_Action::VIEW => array(
          'name' => ts('View'),
          'url' => 'civicrm/admin/custom/group/field/option',
          'qs' => 'action=view&id=%%id%%&fid=%%fid%%',
          'title' => ts('View Multiple Choice Option'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Multiple Choice Option'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Multiple Choice Option'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/custom/group/field/option',
          'qs' => 'action=delete&id=%%id%%&fid=%%fid%%',
          'title' => ts('Delete Multiple Choice Option'),
        ),
      );
    }
    return self::$_actionLinks;
  }

  /**
   * Alphabetize multiple option values
   *
   * @return void
   */
  public function alphabetize() {
    $optionGroupID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
      $this->_fid,
      'option_group_id'
    );
    $query = "
SELECT id, label
FROM   civicrm_option_value
WHERE  option_group_id = %1";
    $params = array(
      1 => array($optionGroupID, 'Integer'),
    );
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $optionValue = array();
    while ($dao->fetch()) {
      $optionValue[$dao->id] = $dao->label;
    }
    asort($optionValue, SORT_STRING | SORT_FLAG_CASE | SORT_NATURAL);

    $i = 1;
    foreach ($optionValue as $key => $_) {
      $clause[] = "WHEN $key THEN $i";
      $i++;
    }

    $when = implode(' ', $clause);
    $sql = "
UPDATE civicrm_option_value
SET weight = CASE id
$when
END
WHERE option_group_id = %1";

    $dao = CRM_Core_DAO::executeQuery($sql, $params);
  }

  /**
   * Browse all custom group fields.
   *
   * @return void
   */
  public function browse() {

    // get the option group id
    $optionGroupID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
      $this->_fid,
      'option_group_id'
    );

    $query = "
SELECT id, label
FROM   civicrm_custom_field
WHERE  option_group_id = %1";
    $params = array(
      1 => array($optionGroupID, 'Integer'),
      2 => array($this->_fid, 'Integer'),
    );
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $reusedNames = array();
    if ($dao->N > 1) {
      while ($dao->fetch()) {
        $reusedNames[] = $dao->label;
      }
      $reusedNames = implode(', ', $reusedNames);
      $newTitle = ts('%1 - Multiple Choice Options',
        array(1 => $reusedNames)
      );
      CRM_Utils_System::setTitle($newTitle);
      $this->assign('reusedNames', $reusedNames);
    }
    $this->assign('optionGroupID', $optionGroupID);
  }

  /**
   * Edit custom Option.
   *
   * editing would involved modifying existing fields + adding data to new fields.
   *
   * @param string $action
   *   The action to be invoked.
   *
   * @return void
   */
  public function edit($action) {
    // create a simple controller for editing custom data
    $controller = new CRM_Core_Controller_Simple('CRM_Custom_Form_Option', ts('Custom Option'), $action);

    // set the userContext stack
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/custom/group/field/option',
      "reset=1&action=browse&fid={$this->_fid}&gid={$this->_gid}"
    ));
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

    // get the field id
    $this->_fid = CRM_Utils_Request::retrieve('fid', 'Positive',
      $this, FALSE, 0
    );
    $this->_gid = CRM_Utils_Request::retrieve('gid', 'Positive',
      $this, FALSE, 0
    );

    if ($isReserved = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $this->_gid, 'is_reserved', 'id')) {
      CRM_Core_Error::fatal("You cannot add or edit multiple choice options in a reserved custom field-set.");
    }

    $optionGroupId = $this->getOptionGroupId($this->_fid);
    $isOptionGroupLocked = $optionGroupId ? $this->isOptionGroupLocked($optionGroupId) : FALSE;
    $this->assign('optionGroupId', $optionGroupId);
    $this->assign('isOptionGroupLocked', $isOptionGroupLocked);

    //as url contain $gid so append breadcrumb dynamically.
    $breadcrumb = array(
      array(
        'title' => ts('Custom Data Fields'),
        'url' => CRM_Utils_System::url('civicrm/admin/custom/group/field', 'reset=1&gid=' . $this->_gid),
      ),
    );
    CRM_Utils_System::appendBreadCrumb($breadcrumb);

    if ($this->_fid) {
      $fieldTitle = CRM_Core_BAO_CustomField::getTitle($this->_fid);
      $this->assign('fid', $this->_fid);
      $this->assign('gid', $this->_gid);
      $this->assign('fieldTitle', $fieldTitle);
      CRM_Utils_System::setTitle(ts('%1 - Multiple Choice Options', array(1 => $fieldTitle)));
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

    // take action in addition to default browse ?
    if (($action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD |
          CRM_Core_Action::VIEW | CRM_Core_Action::DELETE
        )
      ) ||
      !empty($_POST)
    ) {
      // no browse for edit/update/view
      $this->edit($action);
    }
    elseif ($action & CRM_Core_Action::MAP) {
      $this->alphabetize();
    }
    $this->browse();

    // Call the parents run method
    return parent::run();
  }

  /**
   * Gets the "is_locked" status for the provided option group
   *
   * @param int $optionGroupId
   *
   * @return bool
   */
  private function isOptionGroupLocked($optionGroupId) {
    return (bool) CRM_Core_DAO::getFieldValue(
      CRM_Core_DAO_OptionGroup::class,
      $optionGroupId,
      'is_locked'
    );
  }

  /**
   * Gets the associated "option_group_id" for a custom field
   *
   * @param int $customFieldId
   *
   * @return int
   */
  private function getOptionGroupId($customFieldId) {
    return (int) CRM_Core_DAO::getFieldValue(
      CRM_Core_DAO_CustomField::class,
      $customFieldId,
      'option_group_id'
    );
  }

}
