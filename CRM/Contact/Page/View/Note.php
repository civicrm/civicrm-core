<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * Main page for viewing Notes.
 */
class CRM_Contact_Page_View_Note extends CRM_Core_Page {

  /**
   * The action links for notes that we need to display for the browse screen
   *
   * @var array
   */
  static $_links = NULL;

  /**
   * The action links for comments that we need to display for the browse screen
   *
   * @var array
   */
  static $_commentLinks = NULL;

  /**
   * View details of a note.
   */
  public function view() {
    $note = new CRM_Core_DAO_Note();
    $note->id = $this->_id;
    if ($note->find(TRUE)) {
      $values = array();

      CRM_Core_DAO::storeValues($note, $values);
      $values['privacy'] = CRM_Core_PseudoConstant::getLabel('CRM_Core_BAO_Note', 'privacy', $values['privacy']);
      $this->assign('note', $values);
    }

    $comments = CRM_Core_BAO_Note::getNoteTree($values['id'], 1);
    if (!empty($comments)) {
      $this->assign('comments', $comments);
    }

    // add attachments part
    $currentAttachmentInfo = CRM_Core_BAO_File::getEntityFile('civicrm_note', $this->_id);
    $this->assign('currentAttachmentInfo', $currentAttachmentInfo);

  }

  /**
   * called when action is browse.
   */
  public function browse() {
    $note = new CRM_Core_DAO_Note();
    $note->entity_table = 'civicrm_contact';
    $note->entity_id = $this->_contactId;

    $note->orderBy('modified_date desc');

    //CRM-4418, handling edit and delete separately.
    $permissions = array($this->_permission);
    if ($this->_permission == CRM_Core_Permission::EDIT) {
      //previously delete was subset of edit
      //so for consistency lets grant delete also.
      $permissions[] = CRM_Core_Permission::DELETE;
    }
    $mask = CRM_Core_Action::mask($permissions);

    $values = array();
    $links = self::links();
    $action = array_sum(array_keys($links)) & $mask;

    $note->find();
    while ($note->fetch()) {
      if (!CRM_Core_BAO_Note::getNotePrivacyHidden($note)) {
        CRM_Core_DAO::storeValues($note, $values[$note->id]);

        $values[$note->id]['action'] = CRM_Core_Action::formLink($links,
          $action,
          array(
            'id' => $note->id,
            'cid' => $this->_contactId,
          ),
          ts('more'),
          FALSE,
          'note.selector.row',
          'Note',
          $note->id
        );
        $contact = new CRM_Contact_DAO_Contact();
        $contact->id = $note->contact_id;
        $contact->find();
        $contact->fetch();
        $values[$note->id]['createdBy'] = $contact->display_name;
        $values[$note->id]['comment_count'] = CRM_Core_BAO_Note::getChildCount($note->id);

        // paper icon view for attachments part
        $paperIconAttachmentInfo = CRM_Core_BAO_File::paperIconAttachment('civicrm_note', $note->id);
        $values[$note->id]['attachment'] = $paperIconAttachmentInfo;
      }
    }

    $this->assign('notes', $values);

    $commentLinks = self::commentLinks();

    $action = array_sum(array_keys($commentLinks)) & $mask;

    $commentAction = CRM_Core_Action::formLink($commentLinks,
      $action,
      array(
        'id' => $note->id,
        'pid' => $note->entity_id,
        'cid' => $note->entity_id,
      ),
      ts('more'),
      FALSE,
      'note.comment.action',
      'Note',
      $note->id
    );
    $this->assign('commentAction', $commentAction);

    $this->ajaxResponse['tabCount'] = CRM_Contact_BAO_Contact::getCountComponent('note', $this->_contactId);
  }

  /**
   * called when action is update or new.
   */
  public function edit() {
    $controller = new CRM_Core_Controller_Simple('CRM_Note_Form_Note', ts('Contact Notes'), $this->_action);
    $controller->setEmbedded(TRUE);

    // set the userContext stack
    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/contact/view',
      'action=browse&selectedChild=note&cid=' . $this->_contactId
    );
    $session->pushUserContext($url);

    if (CRM_Utils_Request::retrieve('confirmed', 'Boolean',
      CRM_Core_DAO::$_nullObject
    )
    ) {
      CRM_Core_BAO_Note::del($this->_id);
      CRM_Utils_System::redirect($url);
    }

    $controller->reset();
    $controller->set('entityTable', 'civicrm_contact');
    $controller->set('entityId', $this->_contactId);
    $controller->set('id', $this->_id);

    $controller->process();
    $controller->run();
  }

  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    if ($this->_id && CRM_Core_BAO_Note::getNotePrivacyHidden($this->_id)) {
      CRM_Core_Error::statusBounce(ts('You do not have access to this note.'));
    }

    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->assign('contactId', $this->_contactId);

    // check logged in url permission
    CRM_Contact_Page_View::checkUserPermission($this);

    $displayName = CRM_Contact_BAO_Contact::displayName($this->_contactId);
    CRM_Utils_System::setTitle(ts('Notes for') . ' ' . $displayName);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->assign('action', $this->_action);
  }

  /**
   * the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
    $this->preProcess();

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->view();
    }
    elseif ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      $this->edit();
    }
    elseif ($this->_action & CRM_Core_Action::DELETE) {
      // we use the edit screen the confirm the delete
      $this->edit();
    }

    $this->browse();
    return parent::run();
  }

  /**
   * Delete the note object from the db.
   */
  public function delete() {
    CRM_Core_BAO_Note::del($this->_id);
  }

  /**
   * Get action links.
   *
   * @return array
   *   (reference) of action links
   */
  public static function &links() {
    if (!(self::$_links)) {
      $deleteExtra = ts('Are you sure you want to delete this note?');

      self::$_links = array(
        CRM_Core_Action::VIEW => array(
          'name' => ts('View'),
          'url' => 'civicrm/contact/view/note',
          'qs' => 'action=view&reset=1&cid=%%cid%%&id=%%id%%&selectedChild=note',
          'title' => ts('View Note'),
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/contact/view/note',
          'qs' => 'action=update&reset=1&cid=%%cid%%&id=%%id%%&selectedChild=note',
          'title' => ts('Edit Note'),
        ),
        CRM_Core_Action::ADD => array(
          'name' => ts('Comment'),
          'url' => 'civicrm/contact/view/note',
          'qs' => 'action=add&reset=1&cid=%%cid%%&parentId=%%id%%&selectedChild=note',
          'title' => ts('Add Comment'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/contact/view/note',
          'qs' => 'action=delete&reset=1&cid=%%cid%%&id=%%id%%&selectedChild=note',
          'title' => ts('Delete Note'),
        ),
      );
    }
    return self::$_links;
  }

  /**
   * Get action links for comments.
   *
   * @return array
   *   (reference) of action links
   */
  public static function &commentLinks() {
    if (!(self::$_commentLinks)) {
      self::$_commentLinks = array(
        CRM_Core_Action::VIEW => array(
          'name' => ts('View'),
          'url' => 'civicrm/contact/view/note',
          'qs' => 'action=view&reset=1&cid=%%cid%%&id={id}&selectedChild=note',
          'title' => ts('View Comment'),
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/contact/view/note',
          'qs' => 'action=update&reset=1&cid=%%cid%%&id={id}&parentId=%%pid%%&selectedChild=note',
          'title' => ts('Edit Comment'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/contact/view/note',
          'qs' => 'action=delete&reset=1&cid=%%cid%%&id={id}&selectedChild=note',
          'title' => ts('Delete Comment'),
        ),
      );
    }
    return self::$_commentLinks;
  }

}
