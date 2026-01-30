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
 * Form for merging tags.
 */
class CRM_Tag_Form_Merge extends CRM_Core_Form {
  protected $_id;
  protected $_tags;

  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'String', $this, FALSE);
    $this->_id = explode(',', $this->_id);
    $url = CRM_Utils_System::url('civicrm/tag');
    if (count($this->_id) < 2) {
      CRM_Core_Error::statusBounce(ts("You must select at least 2 tags for merging."), $url);
    }
    $tags = civicrm_api3('Tag', 'get', ['id' => ['IN' => $this->_id], 'options' => ['limit' => 0]]);
    $this->_tags = $tags['values'];
    if (count($this->_id) != count($this->_tags)) {
      CRM_Core_Error::statusBounce(ts("Unknown tag."), $url);
    }
    if (!CRM_Core_Permission::check('administer reserved tags')) {
      foreach ($tags['values'] as $tag) {
        if (!empty($tag['is_reserved'])) {
          CRM_Core_Error::statusBounce(ts("You do not have permission to administer reserved tags."), $url);
        }
      }
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->add('text', 'label', ts('Label of combined tag'), NULL, TRUE);
    $this->assign('tags', CRM_Utils_Array::collect('label', $this->_tags));

    $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Merge'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
    ]);
  }

  /**
   * Set default values for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $primary = CRM_Utils_Array::first($this->_tags);
    return [
      'label' => $primary['label'],
    ];
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $params = $this->exportValues();
    $deleted = CRM_Utils_Array::collect('label', $this->_tags);
    $primary = array_shift($this->_tags);

    foreach ($this->_tags as $tag) {
      CRM_Core_BAO_EntityTag::mergeTags($primary['id'], $tag['id']);
    }

    if ($params['label'] != $primary['label']) {
      civicrm_api3('Tag', 'create', ['id' => $primary['id'], 'label' => $params['label']]);
    }

    CRM_Core_Session::setStatus(
      ts('All records previously tagged %1 are now tagged %2.', [1 => implode(' ' . ts('or') . ' ', $deleted), 2 => $params['label']]),
      ts('%1 Tags Merged', [1 => count($this->_id)]),
      'success'
    );

    CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/tag'));
  }

}
