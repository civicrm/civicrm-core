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

namespace Civi\Schema;

interface EntityStorageInterface {

  public function writeRecords(array $records): array;

  public function deleteRecords(array $records): array;

  public function findReferences(array $record): array;

  public function getReferenceCounts(array $record): array;

}
