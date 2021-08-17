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

namespace Civi\Api4\Service\Schema;

class Joiner {
  /**
   * @var SchemaMap
   */
  protected $schemaMap;

  /**
   * @var \Civi\Api4\Service\Schema\Joinable\Joinable[][]
   */
  protected $cache = [];

  /**
   * @param SchemaMap $schemaMap
   */
  public function __construct(SchemaMap $schemaMap) {
    $this->schemaMap = $schemaMap;
  }

  /**
   * Get the path used to create an implicit join
   *
   * @param string $baseTable
   * @param array $joinPath
   *
   * @return \Civi\Api4\Service\Schema\Joinable\Joinable[]
   * @throws \API_Exception
   */
  public function getPath(string $baseTable, array $joinPath) {
    $cacheKey = sprintf('%s.%s', $baseTable, implode('.', $joinPath));
    if (!isset($this->cache[$cacheKey])) {
      $fullPath = [];

      foreach ($joinPath as $targetAlias) {
        $link = $this->schemaMap->getLink($baseTable, $targetAlias);

        if (!$link) {
          throw new \API_Exception(sprintf('Cannot join %s to %s', $baseTable, $targetAlias));
        }
        else {
          $fullPath[$targetAlias] = $link;
          $baseTable = $link->getTargetTable();
        }
      }

      $this->cache[$cacheKey] = $fullPath;
    }

    return $this->cache[$cacheKey];
  }

}
