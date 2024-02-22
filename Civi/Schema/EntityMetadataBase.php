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

abstract class EntityMetadataBase implements EntityMetadataInterface {

  /**
   * @var string
   */
  protected string $entityName;

  public function __construct(string $entityName) {
    $this->entityName = $entityName;
  }

  /**
   * @return array{name: string, table: string, class: string, module: string, getInfo: callable, getPaths: callable, getIndices: callable, getFields: callable, metaProvider: callable, storageProvider: callable}
   */
  protected function getEntity(): array {
    return EntityRepository::getEntity($this->entityName);
  }

}
