<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class is to build the form for Deleting Group
 */
class CRM_Custom_Form_MoveField extends CRM_Core_Form {

  /**
   * the src group id
   *
   * @var int
   */
  protected $_srcGID;

  /**
   * the src field id
   *
   * @var int
   */
  protected $_srcFID;

  /**
   * the dst group id
   *
   * @var int
   */
  protected $_dstGID;

  /**
   * the dst field id
   *
   * @var int
   */
  protected $_dstFID;

  /**
   * The title of the field being moved
   *
   * @var string
   */
  protected $_srcFieldLabel;

  /**
   * set up variables to build the form
   *
   * @return void
   * @acess protected
   */
  function preProcess() {
    $this->_srcFID = CRM_Utils_Request::retrieve('fid', 'Positive',
      $this, TRUE
    );

    $this->_srcGID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
      $this->_srcFID,
      'custom_group_id'
    );

    $this->_srcFieldLabel = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
      $this->_srcFID,
      'label'
    );

    CRM_Utils_System::setTitle(ts('Custom Field Move: %1',
        array(1 => $this->_srcFieldLabel)
      ));

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/custom/group/field', "reset=1&action=browse&gid={$this->_srcGID}"));
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {

    $customGroup = CRM_Core_PseudoConstant::get('CRM_Core_DAO_CustomField', 'custom_group_id');
    unset($customGroup[$this->_srcGID]);
    if (empty($customGroup)) {
      CRM_Core_Error::statusBounce(ts('You need more than one custom group to move fields'));
    }

    $customGroup = array(
      '' => ts('- select -')) + $customGroup;
    $this->add('select',
      'dst_group_id',
      ts('Destination'),
      $customGroup,
      TRUE
    );

    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Move Custom Field'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );

    $this->addFormRule(array('CRM_Custom_Form_MoveField', 'formRule'), $this);
  }

  /**
   * @param $fields
   * @param $files
   * @param $self
   *
   * @return array|bool
   */
  static function formRule($fields, $files, $self) {
    $self->_dstGID = $fields['dst_group_id'];
    $tmp           = CRM_Core_BAO_CustomField::_moveFieldValidate($self->_srcFID, $self->_dstGID);
    $errors        = array();
    if ($tmp['newGroupID']) {
      $errors['dst_group_id'] = $tmp['newGroupID'];
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form when submitted
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    CRM_Core_BAO_CustomField::moveField($this->_srcFID, $this->_dstGID);

    $dstGroup = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup',
      $this->_dstGID,
      'title'
    );
    $srcUrl = CRM_Utils_System::url('civicrm/admin/custom/group/field', "reset=1&action=browse&gid={$this->_dstGID}");
    CRM_Core_Session::setStatus(ts("%1 has been moved to the custom set <a href='%3'>%2</a>.",
      array(
      1 => $this->_srcFieldLabel,
          2 => $dstGroup,
        3 => $srcUrl
        )), '', 'success');
  }
}

