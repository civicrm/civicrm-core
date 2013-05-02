<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
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
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_action == CRM_Core_Action::DELETE) {
      if ($this->_id && $tag = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $this->_id, 'name', 'parent_id')) {
        CRM_Core_Session::setStatus(ts("This tag cannot be deleted. You must delete all its child tags ('%1', etc) prior to deleting this tag.", array(1 => $tag)), ts('Sorry'), 'error');
        $url = CRM_Utils_System::url('civicrm/admin/tag', "reset=1");
        CRM_Utils_System::redirect($url);
        return TRUE;
      }
      else {
        $this->addButtons(array(
            array(
              'type' => 'next',
              'name' => ts('Delete'),
              'isDefault' => TRUE,
            ),
            array(
              'type' => 'cancel',
              'name' => ts('Cancel'),
            ),
          )
        );
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

      $allTag = array('' => '- ' . ts('select') . ' -') + CRM_Core_BAO_Tag::getTagsNotInTagset();

      if ($this->_id) {
        unset($allTag[$this->_id]);
      }

      if (!$this->_isTagSet) {
        $this->add('select', 'parent_id', ts('Parent Tag'), $allTag);
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
      $this->add('checkbox', 'is_selectable', ts("If it's a tag or a category"));

      $isReserved = $this->add('checkbox', 'is_reserved', ts('Reserved?'));

      $usedFor = $this->add('select', 'used_for', ts('Used For'),
        CRM_Core_OptionGroup::values('tag_used_for')
      );
      $usedFor->setMultiple(TRUE);

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

      parent::buildQuickForm();
    }
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
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
        CRM_Core_BAO_Tag::del($this->_id);
      }
    }
    else {
      $tag = CRM_Core_BAO_Tag::add($params, $ids);
      CRM_Core_Session::setStatus(ts('The tag \'%1\' has been saved.', array(1 => $tag->name)), ts('Saved'), 'success');
    }
  }
  //end of function
}

