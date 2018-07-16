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
    $tags = civicrm_api3('Tag', 'get', array('id' => array('IN' => $this->_id), 'options' => array('limit' => 0)));
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
    $this->add('text', 'name', ts('Name of combined tag'), TRUE);
    $this->assign('tags', CRM_Utils_Array::collect('name', $this->_tags));

    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Merge'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   * Set default values for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $primary = CRM_Utils_Array::first($this->_tags);
    return array(
      'name' => $primary['name'],
    );
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $params = $this->exportValues();
    $deleted = CRM_Utils_Array::collect('name', $this->_tags);
    $primary = array_shift($this->_tags);

    foreach ($this->_tags as $tag) {
      CRM_Core_BAO_EntityTag::mergeTags($primary['id'], $tag['id']);
    }

    if ($params['name'] != $primary['name']) {
      civicrm_api3('Tag', 'create', array('id' => $primary['id'], 'name' => $params['name']));
    }

    $key = array_search($params['name'], $deleted);
    if ($key !== FALSE) {
      unset($deleted[$key]);
    }

    CRM_Core_Session::setStatus(
      ts('All records previously tagged %1 are now tagged %2.', array(1 => implode(' ' . ts('or') . ' ', $deleted), 2 => $params['name'])),
      ts('%1 Tags Merged', array(1 => count($this->_id))),
      'success'
    );

    CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/tag'));
  }

}
