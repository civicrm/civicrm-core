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

namespace Civi\Afform;

/**
 *
 * @package Civi\Afform
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class Utils {

  /**
   * Sorts entities according to references to each other
   *
   * Returns a list of entity names in order of when they should be processed,
   * so that an entity being referenced is saved before the entity referencing it.
   *
   * @param $formEntities
   * @param $entityValues
   * @return string[]
   */
  public static function getEntityWeights($formEntities, $entityValues) {
    $sorter = new \MJS\TopSort\Implementations\FixedArraySort();

    foreach ($formEntities as $entityName => $entity) {
      $references = [];
      foreach ($entityValues[$entityName] as $record) {
        foreach ($record['fields'] as $fieldName => $fieldValue) {
          foreach ((array) $fieldValue as $value) {
            if (array_key_exists($value, $formEntities) && $value !== $entityName) {
              $references[$value] = $value;
            }
          }
        }
      }
      $sorter->add($entityName, $references);
    }
    // Return the list of entities ordered by weight
    return $sorter->sort();
  }

}
