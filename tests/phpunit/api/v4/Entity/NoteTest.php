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

use api\v4\UnitTestCase;
use Civi\Api4\Note;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class NoteTest extends UnitTestCase implements TransactionalInterface {

  public function testDeleteWithChildren() {
    $c1 = $this->createEntity(['type' => 'Individual']);

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

}
