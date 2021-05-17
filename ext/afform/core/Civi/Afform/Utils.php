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

  public static function getEntityWeights($formEntities, $entityValues) {
    $entityWeights = $entityMapping = $entitiesToBeProcessed = [];
    foreach ($formEntities as $entityName => $entity) {
      $entityWeights[$entityName] = 1;
      $entityMapping[$entityName] = $entity['type'];
      foreach ($entityValues[$entity['type']][$entityName] as $record) {
        foreach ($record as $index => $vals) {
          foreach ($vals as $field => $value) {
            if (array_key_exists($value, $entityWeights)) {
              $entityWeights[$entityName] = max((int) $entityWeights[$entityName], (int) ($entityWeights[$value] + 1));
            }
            else {
              if (!array_key_exists($value, $entitiesToBeProcessed)) {
                $entitiesToBeProcessed[$value] = [$entityName];
              }
              else {
                $entitiesToBeProcessed[$value][] = $entityName;
              }
            }
          }
        }
      }
      // If any other entities have been processed that relied on this entity lets now alter their weights based on this entity's weight.
      if (array_key_exists($entityName, $entitiesToBeProcessed)) {
        foreach ($entitiesToBeProcessed[$entityName] as $dependentEntity) {
          $entityWeights[$dependentEntity] = max((int) $entityWeights[$dependentEntity], (int) ($entityWeights[$entityName] + 1));
        }
      }
    }
    // Numerically sort the weights now that we have them set
    asort($entityWeights);
    return $entityWeights;
  }

}
