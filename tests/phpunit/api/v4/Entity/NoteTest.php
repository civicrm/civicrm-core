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

namespace api\v4\Entity;

use api\v4\Api4TestBase;
use Civi\Api4\Contact;
use Civi\Api4\Note;
use Civi\Test\ACLPermissionTrait;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class NoteTest extends Api4TestBase implements TransactionalInterface {

  use ACLPermissionTrait;

  public function testDeleteWithChildren(): void {
    $c1 = $this->createTestRecord('Contact');

    $text = uniqid(__FUNCTION__, TRUE);

    // Create 2 top-level notes.
    $notes = Note::save(FALSE)
      ->setRecords([['note' => $text], ['note' => $text]])
      ->setDefaults([
        'entity_id' => $c1['id'],
        'entity_table' => 'civicrm_contact',
      ])->execute();

    // Add 2 children of the first note.
    $children = Note::save(FALSE)
      ->setRecords([['note' => $text], ['note' => $text]])
      ->setDefaults([
        'entity_id' => $notes->first()['id'],
        'entity_table' => 'civicrm_note',
      ])->execute();

    // Add 2 children of the first child.
    $grandChildren = Note::save(FALSE)
      ->setRecords([['note' => $text], ['note' => $text]])
      ->setDefaults([
        'entity_id' => $children->first()['id'],
        'entity_table' => 'civicrm_note',
      ])->execute();

    // We just created 2 top-level notes and 4 children. Ensure we have a total of 6.
    $existing = Note::get(FALSE)
      ->addWhere('note', '=', $text)
      ->execute();
    $this->assertCount(6, $existing);

    // Delete parent
    Note::delete(FALSE)
      ->addWhere('id', '=', $notes->first()['id'])
      ->execute();

    // Should have deleted 1 parent + 4 child-notes, for a new total of 1 remaining.
    $existing = Note::get(FALSE)
      ->addWhere('note', '=', $text)
      ->execute();
    $this->assertCount(1, $existing);
  }

  public function testJoinNotesFromContact(): void {
    $userId = $this->createLoggedInUser();
    $c1 = $this->createTestRecord('Contact');
    $c2 = $this->createTestRecord('Contact');

    // Create 2 notes for $c1 and 1 for $c2.
    $notes = Note::save(FALSE)
      ->setRecords([
        ['note' => 'Note1', 'entity_id' => $c1['id']],
        ['note' => 'Note2', 'entity_id' => $c1['id']],
        ['note' => 'Note3', 'entity_id' => $c2['id']],
      ])
      ->setDefaults([
        'entity_id' => $c1['id'],
        'entity_table' => 'civicrm_contact',
      ])->execute();

    $results = Contact::get(FALSE)
      ->addWhere('id', 'IN', [$c1['id'], $c2['id']])
      ->addOrderBy('id')
      ->addJoin('Note AS Contact_Note',
        'LEFT',
        ['id', '=', 'Contact_Note.entity_id'],
        ['Contact_Note.entity_table', '=', '"civicrm_contact"']
      )
      ->addSelect('id', 'Contact_Note.note', 'Contact_Note.contact_id')
      ->execute()->indexBy('Contact_Note.note');

    $this->assertCount(3, $results);
    $this->assertEquals($c1['id'], $results['Note1']['id']);
    $this->assertEquals($c1['id'], $results['Note2']['id']);
    $this->assertEquals($c2['id'], $results['Note3']['id']);
    // Note creator should have been set to current user
    $this->assertEquals($userId, $results['Note1']['Contact_Note.contact_id']);
    $this->assertEquals($userId, $results['Note2']['Contact_Note.contact_id']);
    $this->assertEquals($userId, $results['Note3']['Contact_Note.contact_id']);
  }

  public function testNotePermissions(): void {
    $userId = $this->createLoggedInUser();
    $cid1 = $this->createTestRecord('Contact')['id'];
    $cid2 = $this->createTestRecord('Contact')['id'];
    $this->allowedContacts = [$cid1];
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view debug output'];
    \CRM_Utils_Hook::singleton()->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);
    // Create 2 notes for $c1 and 1 for $c2.
    $notes = Note::save(FALSE)
      ->setRecords([
        ['note' => 'Not private'],
        ['note' => 'Private', 'privacy' => 1],
        ['note' => 'Private not mine', 'privacy' => 1, 'contact_id' => $cid2],
        ['note' => 'C2 note', 'entity_id' => $cid2],
        ['note' => 'C2 private note', 'privacy' => 1, 'entity_id' => $cid2],
      ])
      ->setDefaults([
        'entity_id' => $cid1,
        'entity_table' => 'civicrm_contact',
      ])->execute()->column('id');
    $visibleNotes = Note::get()
      ->addWhere('entity_id', 'IN', [$cid1, $cid2])
      ->addWhere('entity_table', 'IN', ['civicrm_contact'])
      ->setDebug(TRUE)
      ->execute()
      ->indexBy('id');
    $this->assertCount(2, $visibleNotes);
    $this->assertEquals('Not private', $visibleNotes[$notes[0]]['note']);
    $this->assertEquals('Private', $visibleNotes[$notes[1]]['note']);
    // ACL clause should have been inserted only once because entity_table was specified
    $this->assertEquals(1, substr_count($visibleNotes->debug['sql'][0], 'civicrm_acl_contact_cache'));
  }

  public function testGetHierarchicalNotes(): void {
    $c1 = $this->createTestRecord('Individual');
    $c2 = $this->createTestRecord('Individual');
    $text = uniqid(__FUNCTION__, TRUE);

    // Create 2 top-level notes.
    $topNotes = Note::save(FALSE)
      ->setRecords([
        ['note' => $text, 'subject' => 'Top B'],
        ['note' => $text, 'subject' => 'Top A'],
      ])
      ->setDefaults([
        'entity_id' => $c1['id'],
        'entity_table' => 'civicrm_contact',
      ])->execute();

    // Add 2 children of the first note
    $children = Note::save(FALSE)
      ->setRecords([
        ['note' => $text, 'subject' => 'Mid A'],
        ['note' => $text, 'subject' => 'Mid B'],
      ])
      ->setDefaults([
        'entity_id' => $topNotes[0]['id'],
        'entity_table' => 'civicrm_note',
      ])->execute();

    // Add 2 children of the first child, 1 of 2nd child
    $grandChildren = Note::save(FALSE)
      ->setRecords([
        ['note' => $text, 'subject' => 'Bottom B', 'entity_id' => $children[0]['id']],
        ['note' => $text, 'subject' => 'Bottom A', 'entity_id' => $children[0]['id']],
        ['note' => $text, 'subject' => 'Bottom C', 'entity_id' => $children[1]['id']],
      ])
      ->setDefaults([
        'entity_table' => 'civicrm_note',
      ])->execute();

    // Create 2 red herring notes for a different contact
    $redHerrings = Note::save(FALSE)
      ->setRecords([['note' => $text, 'subject' => 'Red'], ['note' => $text, 'subject' => 'Herring']])
      ->setDefaults([
        'entity_id' => $c2['id'],
        'entity_table' => 'civicrm_contact',
      ])->execute();
    Note::save(FALSE)
      ->setRecords([
        ['note' => $text, 'subject' => 'Red Child', 'entity_id' => $redHerrings[0]['id']],
        ['note' => $text, 'subject' => 'Herring Child', 'entity_id' => $redHerrings[1]['id']],
      ])
      ->setDefaults([
        'entity_table' => 'civicrm_note',
      ])->execute();

    // Get hierarchy for a single contact
    $notes = Note::get(FALSE)
      ->addSelect('id', 'subject', '_depth', '_descendents')
      ->addWhere('entity_id', 'IN', [$c1['id']])
      ->addWhere('entity_table', 'IN', ['civicrm_contact'])
      ->addOrderBy('subject', 'ASC')
      ->execute();
    $this->assertCount(7, $notes);
    $this->assertSame(['Top A', 'Top B', 'Mid A', 'Bottom A', 'Bottom B', 'Mid B', 'Bottom C'], $notes->column('subject'));
    $this->assertSame([0, 0, 1, 2, 2, 1, 2], $notes->column('_depth'));
    $this->assertSame([0, 5, 2, 0, 0, 1, 0], $notes->column('_descendents'));

    // Get entire hierarchy
    $notes = Note::get(FALSE)
      ->addSelect('id', 'subject', '_depth', '_descendents')
      ->addWhere('note', '=', $text)
      ->addWhere('entity_table', 'IN', ['civicrm_contact', 'civicrm_note'])
      ->addOrderBy('subject', 'ASC')
      ->execute();
    $this->assertCount(11, $notes);
    $this->assertSame(['Herring', 'Herring Child', 'Red', 'Red Child', 'Top A', 'Top B', 'Mid A', 'Bottom A', 'Bottom B', 'Mid B', 'Bottom C'], $notes->column('subject'));
    $this->assertSame([0, 1, 0, 1, 0, 0, 1, 2, 2, 1, 2], $notes->column('_depth'));
    $this->assertSame([1, 0, 1, 0, 0, 5, 2, 0, 0, 1, 0], $notes->column('_descendents'));
  }

}
