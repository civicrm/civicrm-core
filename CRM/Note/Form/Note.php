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

use Civi\Api4\Utils\CoreUtil;

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

    $currentUserId = CRM_Core_Session::getLoggedInContactID() ?: 0;
    $delegateAction = ($this->_action & CRM_Core_Action::VIEW) ? 'get' : 'update';

    if ($this->_id) {
      $note = new CRM_Core_DAO_Note();
      $note->id = $this->_id;
      if (!$note->find(TRUE)) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
      }
      $this->_entityTable = $note->entity_table;
      $this->_entityId = (int) $note->entity_id;

      if ((int) $note->privacy === 1 && !CRM_Core_Permission::check('view all notes')) {
        if (!$currentUserId || (int) $note->contact_id !== (int) $currentUserId) {
          CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
        }
      }
    }
    elseif ($this->_action & CRM_Core_Action::ADD) {
      if (!$this->_entityTable || !$this->_entityId) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
      }
    }
    else {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    $targetEntity = CRM_Core_DAO_AllCoreTables::getEntityNameForTable($this->_entityTable);
    if (!$targetEntity || !CoreUtil::checkAccessDelegated($targetEntity, $delegateAction, ['id' => $this->_entityId], $currentUserId)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
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

    if ($this->_action & CRM_Core_Action::ADD) {
      $params['contact_id'] = $session->get('userID');
      $params['entity_table'] = $this->_entityTable;
      $params['entity_id'] = $this->_entityId;
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->setFormRedirect();
      CRM_Core_BAO_Note::deleteRecord(['id' => $this->_id, 'check_permissions' => TRUE]);
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

    $params['check_permissions'] = TRUE;
    $note = CRM_Core_BAO_Note::add($params);

    // check and attach and files as needed
    CRM_Core_BAO_File::processAttachment($params, 'civicrm_note', $note->id);

    // Required for postProcess hooks
    $this->setEntityId($note->id);

    CRM_Core_Session::setStatus(ts('Your Note has been saved.'), ts('Saved'), 'success');
    $this->setFormRedirect();
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

  private function setFormRedirect() {
    // Redirect to viewing the entity to which this note (or note parent) belongs
    $parent = CRM_Core_BAO_Note::getTopParent($this->getEntityId());
    $entityName = Civi::table($parent->entity_table)->getMeta('name');
    $link = civicrm_api4($entityName, 'getLinks', [
      'values' => ['id' => $parent->entity_id],
      'where' => [['ui_action', '=', 'view']],
    ])->first();
    if ($link) {
      $path = $link['path'];
      // Select note tab on contact summary
      if ($entityName === 'Contact') {
        $path .= '&selectedChild=note';
      }
      $url = (string) Civi::url($path);
      CRM_Core_Session::singleton()->pushUserContext($url);
    }
  }

}
