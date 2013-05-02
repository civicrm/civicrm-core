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
 * This class generates form components generic to note
 *
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 *
 */
class CRM_Note_Form_Note extends CRM_Core_Form {

  /**
   * The table name, used when editing/creating a note
   *
   * @var string
   */
  protected $_entityTable;

  /**
   * The table id, used when editing/creating a note
   *
   * @var int
   */
  protected $_entityId;

  /**
   * The note id, used when editing the note
   *
   * @var int
   */
  protected $_id;

  /**
   * The parent note id, used when adding a comment to a note
   *
   * @var int
   */
  protected $_parentId;

  function preProcess() {
    $this->_entityTable = $this->get('entityTable');
    $this->_entityId    = $this->get('entityId');
    $this->_id          = $this->get('id');
    $this->_parentId    = CRM_Utils_Array::value('parentId', $_GET, 0);
    if ($this->_parentId) {
      $this->assign('parentId', $this->_parentId);
    }

    if ($this->_id && CRM_Core_BAO_Note::getNotePrivacyHidden($this->_id)) {
      CRM_Core_Error::statusBounce(ts('You do not have access to this note.'));
    }

    // set title to "Note - " + Contact Name
    $displayName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_entityId, 'display_name');
    $pageTitle = 'Note - ' . $displayName;
    $this->assign('pageTitle', $pageTitle);
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $defaults = array();

    if ($this->_action & CRM_Core_Action::UPDATE) {
      if (isset($this->_id)) {
        $params['id'] = $this->_id;
        CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_Note', $params, $defaults);
      }
      if ($defaults['entity_table'] == 'civicrm_note') {
        $defaults['parent_id'] = $defaults['entity_id'];
      }
    }
    elseif ($this->_action & CRM_Core_Action::ADD && $this->_parentId) {
      $defaults['parent_id'] = $this->_parentId;
      $defaults['subject'] = 'Re: ' . CRM_Core_BAO_Note::getNoteSubject($this->_parentId);
    }
    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_action & CRM_Core_Action::DELETE) {
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
      return;
    }

    $this->add('text', 'subject', ts('Subject:'), array('size' => 20));
    $this->add('textarea', 'note', ts('Note:'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Note', 'note'), TRUE);
    $this->add('select', 'privacy', ts('Privacy:'), CRM_Core_OptionGroup::values('note_privacy'));

    $this->add('hidden', 'parent_id');

    // add attachments part
    CRM_Core_BAO_File::buildAttachment($this, 'civicrm_note', $this->_id, NULL, TRUE);

    $this->addButtons(array(
        array(
          'type' => 'upload',
          'name' => ts('Save'),
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
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    $session = CRM_Core_Session::singleton();
    $params['contact_id'] = $session->get('userID');

    if ($params['parent_id']) {
      $params['entity_table'] = 'civicrm_note';
      $params['entity_id'] = $params['parent_id'];
    }
    else {
      $params['entity_table'] = $this->_entityTable;
      $params['entity_id'] = $this->_entityId;
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Core_BAO_Note::del($this->_id);
      return;
    }

    $params['id'] = null;
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
    }

    // add attachments as needed
    CRM_Core_BAO_File::formatAttachment($params, $params, 'civicrm_note', $params['id']);

    $ids = array();
    $note = CRM_Core_BAO_Note::add($params, $ids);

    CRM_Core_Session::setStatus(ts('Your Note has been saved.'), ts('Saved'), 'success');
  }
}
