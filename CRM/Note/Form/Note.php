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

  public function preProcess() {
    $this->_entityTable = $this->get('entityTable');
    $this->_entityId = $this->get('entityId');
    $this->_id = $this->get('id');
    $this->_parentId = CRM_Utils_Array::value('parentId', $_GET, 0);
    if ($this->_parentId) {
      $this->assign('parentId', $this->_parentId);
    }

    if ($this->_id && CRM_Core_BAO_Note::getNotePrivacyHidden($this->_id)) {
      CRM_Core_Error::statusBounce(ts('You do not have access to this note.'));
    }
    $this->setPageTitle($this->_parentId ? ts('Comment') : ts('Note'));
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return void
   */
  public function setDefaultValues() {
    $defaults = [];

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
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Note';
  }

  /**
   * Explicitly declare the form context.
   */
  public function getDefaultContext() {
    return 'create';
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons([
          [
            'type' => 'next',
            'name' => ts('Delete'),
            'isDefault' => TRUE,
          ],
          [
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ],
      ]);
      return;
    }

    $this->addField('subject');
    $this->addField('note', [], TRUE);
    $this->addField('privacy');
    $this->add('hidden', 'parent_id');

    // add attachments part
    CRM_Core_BAO_File::buildAttachment($this, 'civicrm_note', $this->_id, NULL, TRUE);

    $this->addButtons([
        [
          'type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
    ]
    );
  }

  /**
   *
   * @return void
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

    $params['id'] = NULL;
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
    }

    // add attachments as needed
    CRM_Core_BAO_File::formatAttachment($params, $params, 'civicrm_note', $params['id']);

    $ids = [];
    $note = CRM_Core_BAO_Note::add($params, $ids);

    CRM_Core_Session::setStatus(ts('Your Note has been saved.'), ts('Saved'), 'success');
  }

}
