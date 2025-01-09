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
    $bounceUrl = CRM_Utils_System::url('civicrm/tag');
    if ($this->_action == CRM_Core_Action::DELETE) {
      if (!$this->_id) {
        $this->_id = explode(',', CRM_Utils_Request::retrieve('id', 'String'));
      }
      $this->_id = (array) $this->_id;
      if (!$this->_id) {
        CRM_Core_Error::statusBounce(ts("Unknown tag."), $bounceUrl);
      }
      foreach ($this->_id as $id) {
        if (!CRM_Utils_Rule::positiveInteger($id)) {
          CRM_Core_Error::statusBounce(ts("Unknown tag."), $bounceUrl);
        }
        if ($tag = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $id, 'name', 'parent_id')) {
          CRM_Core_Error::statusBounce(ts("This tag cannot be deleted. You must delete all its child tags ('%1', etc) prior to deleting this tag.", [1 => $tag]), $bounceUrl);
        }
        if (!CRM_Core_Permission::check('administer reserved tags') && CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $id, 'is_reserved')) {
          CRM_Core_Error::statusBounce(ts("You do not have sufficient permission to delete this reserved tag."), $bounceUrl);
        }
      }
      if (count($this->_id) > 1) {
        $this->assign('delName', ts('%1 tags', [1 => count($this->_id)]));
      }
    }
    else {
      $adminTagset = CRM_Core_Permission::check('administer Tagsets');
      $adminReservedTags = CRM_Core_Permission::check('administer reserved tags');

      $this->_isTagSet = CRM_Utils_Request::retrieve('tagset', 'Positive', $this);

      if (!$this->_isTagSet && $this->_id &&
        CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $this->_id, 'is_tagset')
      ) {
        $this->_isTagSet = TRUE;
      }
      if ($this->_isTagSet && !$adminTagset) {
        CRM_Core_Error::statusBounce(ts("You do not have sufficient permission to edit this tagset."), $bounceUrl);
      }
      if ($this->_id && !$adminReservedTags && CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $this->_id, 'is_reserved')) {
        CRM_Core_Error::statusBounce(ts("You do not have sufficient permission to edit this reserved tag."), $bounceUrl);
      }

      if ($this->_id) {
        $parentId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $this->_id, 'parent_id');
      }
      else {
        $parentId = CRM_Utils_Request::retrieve('parent_id', 'Integer', $this);
      }
      $isTagSetChild = $parentId ? CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $parentId, 'is_tagset') : FALSE;

      if (!$this->_isTagSet) {
        if (!$isTagSetChild) {
          $colorTags = CRM_Core_BAO_Tag::getColorTags(NULL, TRUE, $this->_id);
          $this->add('select2', 'parent_id', ts('Parent Tag'), $colorTags, FALSE, ['placeholder' => ts('- select -')]);
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

      $this->add('text', 'label', ts('Label'),
        CRM_Core_DAO::getAttribute('CRM_Core_DAO_Tag', 'label'), TRUE
      );

      $this->add('text', 'description', ts('Description'),
        CRM_Core_DAO::getAttribute('CRM_Core_DAO_Tag', 'description')
      );

      $isReserved = $this->add('checkbox', 'is_reserved', ts('Reserved?'));

      if (!$isTagSetChild) {
        $this->addSelect('used_for', ['multiple' => TRUE, 'option_url' => NULL]);
      }

      $this->assign('adminTagset', $adminTagset);

      if (!$adminReservedTags) {
        $isReserved->freeze();
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
    $cloneFrom = CRM_Utils_Request::retrieve('clone_from', 'Integer');
    if (empty($this->_id) && $cloneFrom) {
      $params = ['id' => $cloneFrom];
      CRM_Core_BAO_Tag::retrieve($params, $this->_values);
      $this->_values['label'] .= ' ' . ts('(copy)');
      if (!empty($this->_values['is_reserved']) && !CRM_Core_Permission::check('administer reserved tags')) {
        $this->_values['is_reserved'] = 0;
      }
      $defaults = $this->_values;
    }
    if (empty($defaults['color'])) {
      $defaults['color'] = '#ffffff';
    }
    if (empty($this->_id) && empty($defaults['used_for'])) {
      $defaults['used_for'] = 'civicrm_contact';
    }
    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action == CRM_Core_Action::DELETE) {
      $deleted = 0;
      $tag = civicrm_api3('tag', 'getsingle', ['id' => $this->_id[0]]);
      foreach ($this->_id as $id) {
        if (CRM_Core_BAO_Tag::deleteRecord(['id' => $id])) {
          $deleted++;
        }
      }
      if (count($this->_id) == 1 && $deleted == 1) {
        if ($tag['is_tagset']) {
          CRM_Core_Session::setStatus(ts("The tag set '%1' has been deleted.", [1 => $tag['label']]), ts('Deleted'), 'success');
        }
        else {
          CRM_Core_Session::setStatus(ts("The tag '%1' has been deleted.", [1 => $tag['label']]), ts('Deleted'), 'success');
        }
      }
      else {
        CRM_Core_Session::setStatus(ts("Deleted %1 tags.", [1 => $deleted]), ts('Deleted'), 'success');
      }
    }
    else {
      $params = $this->exportValues();
      if ($this->_id) {
        $params['id'] = $this->_id;
      }

      if (isset($params['used_for']) && ($this->_action == CRM_Core_Action::ADD || $this->_action == CRM_Core_Action::UPDATE)) {
        $params['used_for'] = implode(",", $params['used_for']);
      }

      $params['is_tagset'] = 0;
      if ($this->_isTagSet) {
        $params['is_tagset'] = 1;
      }

      if (!isset($params['is_reserved'])) {
        $params['is_reserved'] = 0;
      }

      if (!isset($params['parent_id']) && $this->get('parent_id')) {
        $params['parent_id'] = $this->get('parent_id');
      }
      if (empty($params['parent_id'])) {
        $params['parent_id'] = '';
      }

      if (!isset($params['is_selectable'])) {
        $params['is_selectable'] = 0;
      }
      $tag = CRM_Core_BAO_Tag::add($params);
      CRM_Core_Session::setStatus(ts("The tag '%1' has been saved.", [1 => $tag->label]), ts('Saved'), 'success');
      $this->ajaxResponse['tag'] = $tag->toArray();
    }
    CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/tag'));
  }

}
