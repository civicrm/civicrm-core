<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
 */

/**
 * This class generates form components for Tag.
 */
class CRM_Tag_Form_Edit extends CRM_Admin_Form {
  protected $_isTagSet;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Tag';
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    if ($this->_action == CRM_Core_Action::DELETE) {
      if ($this->_id && $tag = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $this->_id, 'name', 'parent_id')) {
        $url = CRM_Utils_System::url('civicrm/tag', "reset=1");
        CRM_Core_Error::statusBounce(ts("This tag cannot be deleted. You must delete all its child tags ('%1', etc) prior to deleting this tag.", array(1 => $tag)), $url);
      }
      if ($this->_values['is_reserved'] == 1 && !CRM_Core_Permission::check('administer reserved tags')) {
        CRM_Core_Error::statusBounce(ts("You do not have sufficient permission to delete this reserved tag."));
      }
    }
    else {
      $parentId = NULL;
      $isTagSetChild = FALSE;

      $this->_isTagSet = CRM_Utils_Request::retrieve('tagset', 'Positive', $this);

      if (!$this->_isTagSet &&
        $this->_id &&
        CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $this->_id, 'is_tagset')
      ) {
        $this->_isTagSet = TRUE;
      }

      if ($this->_id) {
        $parentId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $this->_id, 'parent_id');
        $isTagSetChild = $parentId ? CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $parentId, 'is_tagset') : FALSE;
      }

      if (!$this->_isTagSet) {
        if (!$isTagSetChild) {
          $colorTags = CRM_Core_BAO_Tag::getColorTags(NULL, TRUE, $this->_id);
          $this->add('select2', 'parent_id', ts('Parent Tag'), $colorTags, FALSE, array('placeholder' => ts('- select -')));
        }

        // Tagsets are not selectable by definition so only include the selectable field if NOT a tagset.
        $selectable = $this->add('checkbox', 'is_selectable', ts('Selectable?'));
        // Selectable should be checked by default when creating a new tag
        if ($this->_action == CRM_Core_Action::ADD) {
          $selectable->setValue(1);
        }

        $this->add('color', 'color', ts('Color'));
      }

      $this->assign('isTagSet', $this->_isTagSet);

      $this->applyFilter('__ALL__', 'trim');

      $this->add('text', 'name', ts('Name'),
        CRM_Core_DAO::getAttribute('CRM_Core_DAO_Tag', 'name'), TRUE
      );
      $this->addRule('name', ts('Name already exists in Database.'), 'objectExists', array(
          'CRM_Core_DAO_Tag',
          $this->_id,
        ));

      $this->add('text', 'description', ts('Description'),
        CRM_Core_DAO::getAttribute('CRM_Core_DAO_Tag', 'description')
      );

      $isReserved = $this->add('checkbox', 'is_reserved', ts('Reserved?'));

      $this->addSelect('used_for', array('multiple' => TRUE, 'option_url' => NULL));

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
    $this->setPageTitle($this->_isTagSet ? ts('Tag Set') : ts('Tag'));
    parent::buildQuickForm();
  }

  /**
   * Set default values for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    if (empty($this->_id) || !CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $this->_id, 'color')) {
      $defaults['color'] = '#ffffff';
    }
    if (empty($this->_id)) {
      $defaults['used_for'] = 'civicrm_contact';
    }
    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->exportValues();
    if ($this->_id) {
      $params['id'] = $this->_id;
    }

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

    if (!isset($params['is_selectable'])) {
      $params['is_selectable'] = 0;
    }

    if (strtolower($params['color']) == '#ffffff') {
      $params['color'] = 'null';
    }

    if ($this->_action == CRM_Core_Action::DELETE) {
      if ($this->_id > 0) {
        $tag = civicrm_api3('tag', 'getsingle', array('id' => $this->_id));
        CRM_Core_BAO_Tag::del($this->_id);
        CRM_Core_Session::setStatus(ts("The tag '%1' has been deleted.", array(1 => $tag['name'])), ts('Deleted'), 'success');
      }
    }
    else {
      $tag = CRM_Core_BAO_Tag::add($params);
      CRM_Core_Session::setStatus(ts("The tag '%1' has been saved.", array(1 => $tag->name)), ts('Saved'), 'success');
    }
  }

}
