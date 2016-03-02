<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * BAO object for crm_note table.
 */
class CRM_Core_BAO_Note extends CRM_Core_DAO_Note {

  /**
   * Const the max number of notes we display at any given time.
   * @var int
   */
  const MAX_NOTES = 3;

  /**
   * Given a note id, retrieve the note text.
   *
   * @param int $id
   *   Id of the note to retrieve.
   *
   * @return string
   *   the note text or NULL if note not found
   *
   */
  public static function getNoteText($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Note', $id, 'note');
  }

  /**
   * Given a note id, retrieve the note subject
   *
   * @param int $id
   *   Id of the note to retrieve.
   *
   * @return string
   *   the note subject or NULL if note not found
   *
   */
  public static function getNoteSubject($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Note', $id, 'subject');
  }

  /**
   * Given a note id, decide if the note should be displayed based on privacy setting
   *
   * @param object $note
   *   Either the id of the note to retrieve, or the CRM_Core_DAO_Note object itself.
   *
   * @return bool
   *   TRUE if the note should be displayed, otherwise FALSE
   *
   */
  public static function getNotePrivacyHidden($note) {
    if (CRM_Core_Permission::check('view all notes')) {
      return FALSE;
    }

    $noteValues = array();
    if (is_object($note) && get_class($note) == 'CRM_Core_DAO_Note') {
      CRM_Core_DAO::storeValues($note, $noteValues);
    }
    else {
      $noteDAO = new CRM_Core_DAO_Note();
      $noteDAO->id = $note;
      $noteDAO->find();
      if ($noteDAO->fetch()) {
        CRM_Core_DAO::storeValues($noteDAO, $noteValues);
      }
    }

    CRM_Utils_Hook::notePrivacy($noteValues);

    if (!$noteValues['privacy']) {
      return FALSE;
    }
    elseif (isset($noteValues['notePrivacy_hidden'])) {
      // If the hook has set visibility, use that setting.
      return $noteValues['notePrivacy_hidden'];
    }
    else {
      // Default behavior (if hook has not set visibility)
      // is to hide privacy notes unless the note creator is the current user.

      if ($noteValues['privacy']) {
        $session = CRM_Core_Session::singleton();
        $userID = $session->get('userID');
        return ($noteValues['contact_id'] != $userID);
      }
      else {
        return FALSE;
      }
    }
  }

  /**
   * Takes an associative array and creates a note object.
   *
   * the function extract all the params it needs to initialize the create a
   * note object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   * @param array $ids
   *   (deprecated) associated array with note id - preferably set $params['id'].
   *
   * @return object
   *   $note CRM_Core_BAO_Note object
   */
  public static function add(&$params, $ids = array()) {
    $dataExists = self::dataExists($params);
    if (!$dataExists) {
      return CRM_Core_DAO::$_nullObject;
    }

    $note = new CRM_Core_BAO_Note();

    if (!isset($params['modified_date'])) {
      $params['modified_date'] = date("Ymd");
    }

    if (!isset($params['privacy'])) {
      $params['privacy'] = 0;
    }

    $note->copyValues($params);
    if (empty($params['contact_id'])) {
      if ($params['entity_table'] == 'civicrm_contact') {
        $note->contact_id = $params['entity_id'];
      }
    }
    $id = CRM_Utils_Array::value('id', $params, CRM_Utils_Array::value('id', $ids));
    if ($id) {
      $note->id = $id;
    }

    $note->save();

    // check and attach and files as needed
    CRM_Core_BAO_File::processAttachment($params, 'civicrm_note', $note->id);

    if ($note->entity_table == 'civicrm_contact') {
      CRM_Core_BAO_Log::register($note->entity_id,
        'civicrm_note',
        $note->id
      );
      $displayName = CRM_Contact_BAO_Contact::displayName($note->entity_id);

      $noteActions = FALSE;
      $session = CRM_Core_Session::singleton();
      if ($session->get('userID')) {
        if ($session->get('userID') == $note->entity_id) {
          $noteActions = TRUE;
        }
        elseif (CRM_Contact_BAO_Contact_Permission::allow($note->entity_id, CRM_Core_Permission::EDIT)) {
          $noteActions = TRUE;
        }
      }

      $recentOther = array();
      if ($noteActions) {
        $recentOther = array(
          'editUrl' => CRM_Utils_System::url('civicrm/contact/view/note',
            "reset=1&action=update&cid={$note->entity_id}&id={$note->id}&context=home"
          ),
          'deleteUrl' => CRM_Utils_System::url('civicrm/contact/view/note',
            "reset=1&action=delete&cid={$note->entity_id}&id={$note->id}&context=home"
          ),
        );
      }

      // add the recently created Note
      CRM_Utils_Recent::add($displayName . ' - ' . $note->subject,
        CRM_Utils_System::url('civicrm/contact/view/note',
          "reset=1&action=view&cid={$note->entity_id}&id={$note->id}&context=home"
        ),
        $note->id,
        'Note',
        $note->entity_id,
        $displayName,
        $recentOther
      );
    }

    return $note;
  }

  /**
   * Check if there is data to create the object.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return bool
   */
  public static function dataExists(&$params) {
    // return if no data present
    if (!strlen($params['note'])) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $params
   *   Input parameters to find object.
   * @param array $values
   *   Output values of the object.
   * @param int $numNotes
   *   The maximum number of notes to return (0 if all).
   *
   * @return object
   *   $notes  Object of CRM_Core_BAO_Note
   */
  public static function &getValues(&$params, &$values, $numNotes = self::MAX_NOTES) {
    if (empty($params)) {
      return NULL;
    }
    $note = new CRM_Core_BAO_Note();
    $note->entity_id = $params['contact_id'];
    $note->entity_table = 'civicrm_contact';

    // get the total count of notes
    $values['noteTotalCount'] = $note->count();

    // get only 3 recent notes
    $note->orderBy('modified_date desc');
    $note->limit($numNotes);
    $note->find();

    $notes = array();
    $count = 0;
    while ($note->fetch()) {
      $values['note'][$note->id] = array();
      CRM_Core_DAO::storeValues($note, $values['note'][$note->id]);
      $notes[] = $note;

      $count++;
      // if we have collected the number of notes, exit loop
      if ($numNotes > 0 && $count >= $numNotes) {
        break;
      }
    }

    return $notes;
  }

  /**
   * Delete the notes.
   *
   * @param int $id
   *   Note id.
   * @param bool $showStatus
   *   Do we need to set status or not.
   *
   * @return int|NULL
   *   no of deleted notes on success, null otherwise
   */
  public static function del($id, $showStatus = TRUE) {
    $return = NULL;
    $recent = array($id);
    $note = new CRM_Core_DAO_Note();
    $note->id = $id;
    $note->find();
    $note->fetch();
    if ($note->entity_table == 'civicrm_note') {
      $status = ts('Selected Comment has been deleted successfully.');
    }
    else {
      $status = ts('Selected Note has been deleted successfully.');
    }

    // Delete all descendents of this Note
    foreach (self::getDescendentIds($id) as $childId) {
      $childNote = new CRM_Core_DAO_Note();
      $childNote->id = $childId;
      $childNote->delete();
      $childNote->free();
      $recent[] = $childId;
    }

    $return = $note->delete();
    $note->free();
    if ($showStatus) {
      CRM_Core_Session::setStatus($status, ts('Deleted'), 'success');
    }

    // delete the recently created Note
    foreach ($recent as $recentId) {
      $noteRecent = array(
        'id' => $recentId,
        'type' => 'Note',
      );
      CRM_Utils_Recent::del($noteRecent);
    }
    return $return;
  }

  /**
   * Delete all records for this contact id.
   *
   * @param int $id
   *   ID of the contact for which note needs to be deleted.
   */
  public static function deleteContact($id) {
    // need to delete for both entity_id
    $dao = new CRM_Core_DAO_Note();
    $dao->entity_table = 'civicrm_contact';
    $dao->entity_id = $id;
    $dao->delete();

    // and the creator contact id
    $dao = new CRM_Core_DAO_Note();
    $dao->contact_id = $id;
    $dao->delete();
  }

  /**
   * Retrieve all records for this entity-id
   *
   * @param int $id
   *   ID of the relationship for which records needs to be retrieved.
   *
   * @param string $entityTable
   *
   * @return array
   *   array of note properties
   *
   */
  public static function &getNote($id, $entityTable = 'civicrm_relationship') {
    $viewNote = array();

    $query = "
  SELECT  id,
          note
    FROM  civicrm_note
   WHERE  entity_table=\"{$entityTable}\"
     AND  entity_id = %1
     AND  note is not null
ORDER BY  modified_date desc";
    $params = array(1 => array($id, 'Integer'));

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    while ($dao->fetch()) {
      $viewNote[$dao->id] = $dao->note;
    }

    return $viewNote;
  }

  /**
   * Get log record count for a Contact.
   *
   * @param int $contactID
   *
   * @return int
   *   $count count of log records
   *
   */
  public static function getContactNoteCount($contactID) {
    $note = new CRM_Core_DAO_Note();
    $note->entity_id = $contactID;
    $note->entity_table = 'civicrm_contact';
    $note->find();
    $count = 0;
    while ($note->fetch()) {
      if (!self::getNotePrivacyHidden($note)) {
        $count++;
      }
    }
    return $count;
  }

  /**
   * Get all descendent notes of the note with given ID.
   *
   * @param int $parentId
   *   ID of the note to start from.
   * @param int $maxDepth
   *   Maximum number of levels to descend into the tree; if not given, will include all descendents.
   * @param bool $snippet
   *   If TRUE, returned values will be pre-formatted for display in a table of notes.
   *
   * @return array
   *   Nested associative array beginning with direct children of given note.
   *
   */
  public static function getNoteTree($parentId, $maxDepth = 0, $snippet = FALSE) {
    return self::buildNoteTree($parentId, $maxDepth, $snippet);
  }

  /**
   * Get total count of direct children visible to the current user.
   *
   * @param int $id
   *   Note ID.
   *
   * @return int
   *   $count Number of notes having the give note as parent
   *
   */
  public static function getChildCount($id) {
    $note = new CRM_Core_DAO_Note();
    $note->entity_table = 'civicrm_note';
    $note->entity_id = $id;
    $note->find();
    $count = 0;
    while ($note->fetch()) {
      if (!self::getNotePrivacyHidden($note)) {
        $count++;
      }
    }
    return $count;
  }

  /**
   * Recursive function to get all descendent notes of the note with given ID.
   *
   * @param int $parentId
   *   ID of the note to start from.
   * @param int $maxDepth
   *   Maximum number of levels to descend into the tree; if not given, will include all descendents.
   * @param bool $snippet
   *   If TRUE, returned values will be pre-formatted for display in a table of notes.
   * @param array $tree
   *   (Reference) Variable to store all found descendents.
   * @param int $depth
   *   Depth of current iteration within the descendent tree (used for comparison against maxDepth).
   *
   * @return array
   *   Nested associative array beginning with direct children of given note.
   */
  private static function buildNoteTree($parentId, $maxDepth = 0, $snippet = FALSE, &$tree = array(), $depth = 0) {
    if ($maxDepth && $depth > $maxDepth) {
      return FALSE;
    }

    // get direct children of given parentId note
    $note = new CRM_Core_DAO_Note();
    $note->entity_table = 'civicrm_note';
    $note->entity_id = $parentId;
    $note->orderBy('modified_date asc');
    $note->find();
    while ($note->fetch()) {
      // foreach child, call this function, unless the child is private/hidden
      if (!self::getNotePrivacyHidden($note)) {
        CRM_Core_DAO::storeValues($note, $tree[$note->id]);

        // get name of user that created this note
        $contact = new CRM_Contact_DAO_Contact();
        $createdById = $note->contact_id;
        $contact->id = $createdById;
        $contact->find();
        $contact->fetch();
        $tree[$note->id]['createdBy'] = $contact->display_name;
        $tree[$note->id]['createdById'] = $createdById;
        $tree[$note->id]['modified_date'] = CRM_Utils_Date::customFormat($tree[$note->id]['modified_date']);

        // paper icon view for attachments part
        $paperIconAttachmentInfo = CRM_Core_BAO_File::paperIconAttachment('civicrm_note', $note->id);
        $tree[$note->id]['attachment'] = $paperIconAttachmentInfo ? implode('', $paperIconAttachmentInfo) : '';

        if ($snippet) {
          $tree[$note->id]['note'] = nl2br($tree[$note->id]['note']);
          $tree[$note->id]['note'] = smarty_modifier_mb_truncate(
            $tree[$note->id]['note'],
            80,
            '...',
            TRUE
          );
          CRM_Utils_Date::customFormat($tree[$note->id]['modified_date']);
        }
        self::buildNoteTree(
          $note->id,
          $maxDepth,
          $snippet,
          $tree[$note->id]['child'],
          $depth + 1
        );
      }
    }

    return $tree;
  }

  /**
   * Given a note id, get a list of the ids of all notes that are descendents of that note
   *
   * @param int $parentId
   *   Id of the given note.
   * @param array $ids
   *   (reference) one-dimensional array to store found descendent ids.
   *
   * @return array
   *   One-dimensional array containing ids of all desendent notes
   */
  public static function getDescendentIds($parentId, &$ids = array()) {
    // get direct children of given parentId note
    $note = new CRM_Core_DAO_Note();
    $note->entity_table = 'civicrm_note';
    $note->entity_id = $parentId;
    $note->find();
    while ($note->fetch()) {
      // foreach child, add to ids list, and recurse
      $ids[] = $note->id;
      self::getDescendentIds($note->id, $ids);
    }
    return $ids;
  }

  /**
   * Delete all note related to contact when contact is deleted.
   *
   * @param int $contactID
   *   Contact id whose notes to be deleted.
   */
  public static function cleanContactNotes($contactID) {
    $params = array(1 => array($contactID, 'Integer'));

    // delete all notes related to contribution
    $contributeQuery = "DELETE note.*
FROM civicrm_note note LEFT JOIN civicrm_contribution contribute ON note.entity_id = contribute.id
WHERE contribute.contact_id = %1 AND note.entity_table = 'civicrm_contribution'";

    CRM_Core_DAO::executeQuery($contributeQuery, $params);

    // delete all notes related to participant
    $participantQuery = "DELETE note.*
FROM civicrm_note note LEFT JOIN civicrm_participant participant ON note.entity_id = participant.id
WHERE participant.contact_id = %1 AND  note.entity_table = 'civicrm_participant'";

    CRM_Core_DAO::executeQuery($participantQuery, $params);

    // delete all contact notes
    $contactQuery = "SELECT id FROM civicrm_note WHERE entity_id = %1 AND entity_table = 'civicrm_contact'";

    $contactNoteId = CRM_Core_DAO::executeQuery($contactQuery, $params);
    while ($contactNoteId->fetch()) {
      self::del($contactNoteId->id, FALSE);
    }
  }

  /**
   * Whitelist of possible values for the entity_table field
   * @return array
   */
  public static function entityTables() {
    $tables = array(
      'civicrm_relationship',
      'civicrm_contact',
      'civicrm_participant',
      'civicrm_contribution',
    );
    // Identical keys & values
    return array_combine($tables, $tables);
  }

}
