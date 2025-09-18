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
 */
class CRM_Note_Form_Note extends CRM_Core_Form {

  use CRM_Core_Form_EntityFormTrait;

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

  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Integer', $this);
    $this->_entityTable = CRM_Utils_Request::retrieve('entity_table', 'String', $this);
    $this->_entityId = CRM_Utils_Request::retrieve('entity_id', 'Integer', $this);

    if ($this->_id && CRM_Core_BAO_Note::getNotePrivacyHidden($this->_id)) {
      CRM_Core_Error::statusBounce(ts('You do not have access to this note.'));
    }
    $this->setPageTitle($this->_entityTable === 'civicrm_note' ? ts('Comment') : ts('Note'));
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function setDefaultValues() {
    $defaults = [];

    if ($this->_action & CRM_Core_Action::UPDATE) {
      if (isset($this->_id)) {
        $params['id'] = $this->_id;
        CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_Note', $params, $defaults);
      }
    }
    elseif ($this->_action & CRM_Core_Action::ADD) {
      $defaults['privacy'] = '0';
      $defaults['note_date'] = date('Y-m-d H:i:s');
      if ($this->_entityTable === 'civicrm_note') {
        $defaults['subject'] = ts('Re: %1', [1 => CRM_Core_BAO_Note::getNoteSubject($this->_entityId)]);
      }
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
    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->view();
      return;
    }
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
    $this->addField('note_date', [], TRUE, FALSE);
    $this->addField('note', [], TRUE);
    $this->addField('privacy', [
      'placeholder' => NULL,
      'option_url' => NULL,
    ]);

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

    if ($this->_action & CRM_Core_Action::ADD) {
      $params['entity_table'] = $this->_entityTable;
      $params['entity_id'] = $this->_entityId;
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Core_BAO_Note::deleteRecord(['id' => $this->_id]);
      $status = ts('Selected Note has been deleted successfully.');
      CRM_Core_Session::setStatus($status, ts('Deleted'), 'success');
      return;
    }

    $params['id'] = NULL;
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
    }

    // add attachments as needed
    CRM_Core_BAO_File::formatAttachment($params, $params, 'civicrm_note', $params['id']);

    $note = CRM_Core_BAO_Note::add($params);

    // Required for postProcess hooks
    $this->setEntityId($note->id);

    CRM_Core_Session::setStatus(ts('Your Note has been saved.'), ts('Saved'), 'success');
  }

  /**
   * View details of a note.
   */
  private function view() {
    $note = \Civi\Api4\Note::get()
      ->addSelect('*', 'privacy:label')
      ->addWhere('id', '=', $this->_id)
      ->execute()
      ->single();
    $note['privacy'] = $note['privacy:label'];
    $this->assign('note', $note);

    $comments = CRM_Core_BAO_Note::getNoteTree($this->_id, 1);
    $this->assign('comments', $comments);

    // add attachments part
    $currentAttachmentInfo = CRM_Core_BAO_File::getEntityFile('civicrm_note', $this->_id);
    $this->assign('currentAttachmentInfo', $currentAttachmentInfo);
  }

}
