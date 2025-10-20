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
 * BAO object for crm_note table.
 */
class CRM_Core_BAO_Note extends CRM_Core_DAO_Note implements \Civi\Core\HookInterface {
  use CRM_Core_DynamicFKAccessTrait;

  /**
   * Const the max number of notes we display at any given time.
   * @var int
   */
  const MAX_NOTES = 3;

  /**
   * Given a note id, retrieve the note subject
   *
   * @param int $id
   *   Id of the note to retrieve.
   *
   * @return string
   *   the note subject or NULL if note not found
   *
   * @throws \CRM_Core_Exception
   */
  public static function getNoteSubject($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Note', $id, 'subject');
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
   * @return CRM_Core_BAO_Note|null
   * @throws \CRM_Core_Exception
   */
  public static function add(&$params, $ids = []) {
    if (!empty($ids)) {
      CRM_Core_Error::deprecatedWarning('ids is deprecated');
    }
    $dataExists = self::dataExists($params);
    if (!$dataExists) {
      return NULL;
    }

    if (!empty($params['entity_table']) && $params['entity_table'] == 'civicrm_contact' && !empty($params['check_permissions'])) {
      if (!CRM_Contact_BAO_Contact_Permission::allow($params['entity_id'], CRM_Core_Permission::EDIT)) {
        throw new CRM_Core_Exception('Permission denied to modify contact record');
      }
    }

    $loggedInContactID = CRM_Core_Session::getLoggedInContactID();

    $id = $params['id'] ?? $ids['id'] ?? NULL;

    // Ugly legacy support for deprecated $ids param
    if ($id) {
      $params['id'] = $id;
    }
    // Set defaults for create mode
    else {
      $params += [
        'privacy' => 0,
        'contact_id' => $loggedInContactID,
      ];
      // If not created by a user, guess the note was self-created
      if (empty($params['contact_id']) && $params['entity_table'] == 'civicrm_contact') {
        $params['contact_id'] = $params['entity_id'];
      }
    }

    $note = new CRM_Core_BAO_Note();
    $note->copyValues($params);
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

      if ($loggedInContactID) {
        if ($loggedInContactID == $note->entity_id) {
          $noteActions = TRUE;
        }
        elseif (CRM_Contact_BAO_Contact_Permission::allow($note->entity_id, CRM_Core_Permission::EDIT)) {
          $noteActions = TRUE;
        }
      }

      $recentOther = [];
      if ($noteActions) {
        $recentOther = [
          'editUrl' => CRM_Utils_System::url('civicrm/note',
            "reset=1&action=update&id={$note->id}&context=home"
          ),
          'deleteUrl' => CRM_Utils_System::url('civicrm/note',
            "reset=1&action=delete&id={$note->id}&context=home"
          ),
        ];
      }

      // add the recently created Note
      CRM_Utils_Recent::add($displayName . ' - ' . $note->subject,
        CRM_Utils_System::url('civicrm/note',
          "reset=1&action=view&id={$note->id}&context=home"
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
    if (empty($params['id']) && !strlen($params['note'])) {
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
   * @return array
   */
  public static function &getValues($params, &$values, $numNotes = self::MAX_NOTES) {
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

    $notes = [];
    $count = 0;
    while ($note->fetch()) {
      $values['note'][$note->id] = [];
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
   * Event fired prior to modifying a Note.
   * @param \Civi\Core\Event\PreEvent $event
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if ($event->action === 'delete' && $event->id) {
      // When deleting a note, also delete child notes
      // This causes recursion as this hook is called again while deleting child notes,
      // So the children of children, etc. will also be deleted.
      foreach (self::getDescendentIds($event->id) as $child) {
        self::deleteRecord(['id' => $child]);
      }
    }
  }

  /**
   * Delete the notes.
   *
   * @param int $id
   *
   * @deprecated
   * @return int
   */
  public static function del($id) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    self::deleteRecord(['id' => $id]);

    return 1;
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
    $viewNote = [];

    $query = "
  SELECT  id,
          note
    FROM  civicrm_note
   WHERE  entity_table=\"{$entityTable}\"
     AND  entity_id = %1
     AND  note is not null
ORDER BY  modified_date desc";
    $params = [1 => [$id, 'Integer']];

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    while ($dao->fetch()) {
      $viewNote[$dao->id] = $dao->note;
    }

    return $viewNote;
  }

  /**
   * @deprecated
   */
  public static function getContactNoteCount($contactID) {
    CRM_Core_Error::deprecatedFunctionWarning('API');
    $note = new CRM_Core_DAO_Note();
    $note->entity_id = $contactID;
    $note->entity_table = 'civicrm_contact';
    return $note->count();
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
   * @deprecated only called by deprecated APIv3
   */
  public static function getNoteTree($parentId, $maxDepth = 0, $snippet = FALSE) {
    return self::buildNoteTree($parentId, $maxDepth, $snippet);
  }

  /**
   * @deprecated
   */
  public static function getChildCount($id) {
    CRM_Core_Error::deprecatedFunctionWarning('API');
    $note = new CRM_Core_DAO_Note();
    $note->entity_table = 'civicrm_note';
    $note->entity_id = $id;
    return $note->count();
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
   * @deprecated only called by deprecated APIv3
   */
  private static function buildNoteTree($parentId, $maxDepth = 0, $snippet = FALSE, &$tree = [], $depth = 0) {
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
      CRM_Core_DAO::storeValues($note, $tree[$note->id]);

      // get name of user that created this note
      $contact = new CRM_Contact_DAO_Contact();
      $createdById = $note->contact_id;
      $contact->id = $createdById;
      $contact->find();
      $contact->fetch();
      $tree[$note->id]['createdBy'] = $contact->display_name;
      $tree[$note->id]['createdById'] = $createdById;
      $tree[$note->id]['note_date'] = CRM_Utils_Date::customFormat($tree[$note->id]['note_date']);
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

    return $tree;
  }

  /**
   * Get direct children of given parentId note
   *
   * @param int $parentId
   *
   * @return array
   *   One-dimensional array containing ids of child notes
   */
  public static function getDescendentIds($parentId) {
    $ids = [];
    $note = new CRM_Core_DAO_Note();
    $note->entity_table = 'civicrm_note';
    $note->entity_id = $parentId;
    $note->find();
    while ($note->fetch()) {
      $ids[] = $note->id;
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
    $params = [1 => [$contactID, 'Integer']];

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
      self::deleteRecord(['id' => $contactNoteId->id]);
    }
  }

  public function addSelectWhereClause(?string $entityName = NULL, ?int $userId = NULL, array $conditions = []): array {
    $clauses = [];
    $relatedClauses = self::getDynamicFkAclClauses('entity_table', 'entity_id', $conditions['entity_table'] ?? NULL);
    if ($relatedClauses) {
      // Nested array will be joined with OR
      $clauses['entity_table'] = [$relatedClauses];
      // Fix dev/core#4924 : Allow inclusion of rows that have no Note.
      // This is a workaround specifically for CiviReport, which erroneously
      // adds this clause to the WHERE instead of the JOIN. If that problem
      // were solved in CiviReport, this could be removed.
      // @see CRM_Report_Form::buildPermissionClause
      $clauses['entity_table'][0][] = 'IS NULL';
    }

    // Enforce note privacy setting
    if (!CRM_Core_Permission::check('view all notes', $userId)) {
      // It was ok to have $userId = NULL for the permission check but must be an int for the query
      $cid = $userId ?? (int) CRM_Core_Session::getLoggedInContactID();
      $clauses['privacy'] = [
        [
          '= 0',
          // OR
          "= 1 AND {contact_id} = $cid",
        ],
      ];
    }
    CRM_Utils_Hook::selectWhereClause($this, $clauses, $userId, $conditions);
    return $clauses;
  }

}
