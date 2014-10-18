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
 * This class generates form components for Tag
 *
 */
class CRM_Admin_Form_Tag extends CRM_Admin_Form {
  protected $_isTagSet;

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    $this->setPageTitle($this->_isTagSet ? ts('Tag Set') : ts('Tag'));

    if ($this->_action == CRM_Core_Action::DELETE) {
      if ($this->_id && $tag = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $this->_id, 'name', 'parent_id')) {
        $url = CRM_Utils_System::url('civicrm/admin/tag', "reset=1");
        CRM_Core_Error::statusBounce(ts("This tag cannot be deleted. You must delete all its child tags ('%1', etc) prior to deleting this tag.", array(1 => $tag)), $url);
      }
      if ($this->_values['is_reserved'] == 1 && !CRM_Core_Permission::check('administer reserved tags')) {
        CRM_Core_Error::statusBounce(ts("You do not have sufficient permission to delete this reserved tag."));
      }
    }
    else {
      $this->_isTagSet = CRM_Utils_Request::retrieve('tagset', 'Positive', $this);

      if (!$this->_isTagSet &&
        $this->_id &&
        CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $this->_id, 'is_tagset')
      ) {
        $this->_isTagSet = TRUE;
      }

      $allTag = array('' => ts('- select -')) + CRM_Core_BAO_Tag::getTagsNotInTagset();

      if ($this->_id) {
        unset($allTag[$this->_id]);
      }

      if (!$this->_isTagSet) {
        $this->add('select', 'parent_id', ts('Parent Tag'), $allTag, FALSE, array('class' => 'crm-select2'));
      }

      $this->assign('isTagSet', $this->_isTagSet);

      $this->applyFilter('__ALL__', 'trim');

      $this->add('text', 'name', ts('Name'),
        CRM_Core_DAO::getAttribute('CRM_Core_DAO_Tag', 'name'), TRUE
      );
      $this->addRule('name', ts('Name already exists in Database.'), 'objectExists', array('CRM_Core_DAO_Tag', $this->_id));

      $this->add('text', 'description', ts('Description'),
        CRM_Core_DAO::getAttribute('CRM_Core_DAO_Tag', 'description')
      );

      //@lobo haven't a clue why the checkbox isn't displayed (it should be checked by default
      $this->add('checkbox', 'is_selectable');

      $isReserved = $this->add('checkbox', 'is_reserved', ts('Reserved?'));

      $usedFor = $this->addSelect('used_for', array('multiple' => TRUE, 'option_url' => NULL));

      if ($this->_id &&
        CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $this->_id, 'parent_id')
      ) {
        $usedFor->freeze();
      }

      $adminTagset = TRUE;
      if (!CRM_Core_Permission::check('administer Tagsets')) {
        $adminTagset = FALSE;
      }
      $this->assign('adminTagset', $adminTagset);

      $adminReservedTags = TRUE;
      if (!CRM_Core_Permission::check('administer reserved tags')) {
        $isReserved->freeze();
        $adminReservedTags = FALSE;
      }
      $this->assign('adminReservedTags', $adminReservedTags);

    }
    parent::buildQuickForm();
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    $params = $ids = array();

    // store the submitted values in an array
    $params = $this->exportValues();

    $ids['tag'] = $this->_id;
    if ($this->_action == CRM_Core_Action::ADD ||
      $this->_action == CRM_Core_Action::UPDATE
    ) {
      $params['used_for'] = implode(",", $params['used_for']);
    }

    $params['is_tagset'] = 0;
    if ($this->_isTagSet) {
      $params['is_tagset'] = 1;
    }

    if (!isset($params['is_reserved'])) {
      $params['is_reserved'] = 0;
    }

    if ($this->_action == CRM_Core_Action::DELETE) {
      if ($this->_id > 0) {
        $tag = civicrm_api3('tag', 'getsingle', array('id' => $this->_id));
        CRM_Core_BAO_Tag::del($this->_id);
        CRM_Core_Session::setStatus(ts('The tag \'%1\' has been deleted.', array(1 => $tag['name'])), ts('Deleted'), 'success');
      }
    }
    else {
      $tag = CRM_Core_BAO_Tag::add($params, $ids);
      CRM_Core_Session::setStatus(ts('The tag \'%1\' has been saved.', array(1 => $tag->name)), ts('Saved'), 'success');
    }
  }

}

