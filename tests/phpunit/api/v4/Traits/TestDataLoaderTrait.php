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
 * $Id$
 *
 */


namespace api\v4\Traits;

/**
 * This probably should be a separate class
 */
trait TestDataLoaderTrait {

  /**
   * @var array
   *   References to entities used for loading test data
   */
  protected $references;

  /**
   * Creates entities from a JSON data set
   *
   * @param $path
   */
  protected function loadDataSet($path) {
    if (!file_exists($path)) {
      $path = __DIR__ . '/../DataSets/' . $path . '.json';
    }

    $dataSet = json_decode(file_get_contents($path), TRUE);
    foreach ($dataSet as $entityName => $entities) {
      foreach ($entities as $entityValues) {

        $entityValues = $this->replaceReferences($entityValues);

        $params = ['values' => $entityValues, 'checkPermissions' => FALSE];
        $result = civicrm_api4($entityName, 'create', $params);
        if (isset($entityValues['@ref'])) {
          $this->references[$entityValues['@ref']] = $result->first();
        }
      }
    }
  }

  /**
   * @param $name
   *
   * @return null|mixed
   */
  protected function getReference($name) {
    return isset($this->references[$name]) ? $this->references[$name] : NULL;
  }

  /**
   * @param array $entityValues
   *
   * @return array
   */
  private function replaceReferences($entityValues) {
    foreach ($entityValues as $name => $value) {
      if (is_array($value)) {
        $entityValues[$name] = $this->replaceReferences($value);
      }
      elseif (substr($value, 0, 4) === '@ref') {
        $referenceName = substr($value, 5);
        list ($reference, $property) = explode('.', $referenceName);
        $entityValues[$name] = $this->references[$reference][$property];
      }
    }
    return $entityValues;
  }

}
