<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
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
