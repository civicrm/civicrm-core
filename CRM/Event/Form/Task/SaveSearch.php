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
 * This class provides the functionality to save a search
 * Saved Searches are used for saving frequently used queries
 * regarding the event participations
 */
class CRM_Event_Form_Task_SaveSearch extends CRM_Event_Form_Task {

  /**
   * Saved search id if any.
   *
   * @var int
   */
  protected $_id;

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    parent::preProcess();
    $this->_id = NULL;
  }

  /**
   * Build the form object - it consists of
   *    - displaying the QILL (query in local language)
   *    - displaying elements for saving the search
   *
   *
   * @return void
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Smart Group'));
    // get the qill
    $query = new CRM_Event_BAO_Query($this->get('formValues'));
    $qill = $query->qill();

    // Values from the search form
    $formValues = $this->controller->exportValues();

    // need to save qill for the smarty template
    $this->assign('qill', $qill);

    // the name and description are actually stored with the group and not the saved search
    $this->add('text', 'title', ts('Name'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Group', 'title'), TRUE
    );

    $this->addElement('text', 'description', ts('Description'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Group', 'description')
    );

    // get the group id for the saved search
    $groupId = NULL;
    if (isset($this->_id)) {
      $params = array('saved_search_id' => $this->_id);
      CRM_Contact_BAO_Group::retrieve($params, $values);
      $groupId = $values['id'];

      $this->addDefaultButtons(ts('Update Smart Group'));
    }
    else {
      $this->addDefaultButtons(ts('Save Smart Group'));
      $this->assign('partiallySelected', $formValues['radio_ts'] != 'ts_all');
    }

    $this->addRule('title', ts('Name already exists in Database.'),
      'objectExists', array('CRM_Contact_DAO_Group', $groupId, 'title')
    );
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   *
   * @return void
   */
  public function postProcess() {
    // saved search form values
    $formValues = $this->controller->exportValues();

    $session = CRM_Core_Session::singleton();

    //save the search
    $savedSearch = new CRM_Contact_BAO_SavedSearch();
    $savedSearch->id = $this->_id;
    $savedSearch->form_values = serialize($this->get('formValues'));
    $savedSearch->save();
    $this->set('ssID', $savedSearch->id);
    CRM_Core_Session::setStatus(ts("Your smart group has been saved as '%1'.", array(1 => $formValues['title'])), ts('Saved'), 'success');

    // also create a group that is associated with this saved search only if new saved search
    $params = array();
    $params['title'] = $formValues['title'];
    $params['description'] = $formValues['description'];
    $params['visibility'] = 'User and User Admin Only';
    $params['saved_search_id'] = $savedSearch->id;
    $params['is_active'] = 1;

    if ($this->_id) {
      $params['id'] = CRM_Contact_BAO_SavedSearch::getName($this->_id, 'id');
    }
    $group = CRM_Contact_BAO_Group::create($params);
  }

}
