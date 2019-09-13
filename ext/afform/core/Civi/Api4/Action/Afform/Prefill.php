<?php

namespace Civi\Api4\Action\Afform;

/**
 * Class Prefill
 * @package Civi\Api4\Action\Afform
 */
class Prefill extends AbstractProcessor {

  protected $_data = [];

  protected function processForm() {
    foreach ($this->_afformEntities as $entityName => $entity) {
      // Load entities from args
      if (!empty($this->args[$entityName])) {
        $this->loadEntity($entity, $this->args[$entityName]);
      }
      // Load entities from autofill settings
      elseif (!empty($entity['autofill'])) {
        $this->autofillEntity($entity, $entity['autofill']);
      }
    }
    $data = [];
    foreach ($this->_data as $name => $values) {
      $data[] = ['name' => $name, 'values' => $values];
    }
    return $data;
  }

  /**
   * Fetch all fields needed to display a given entity on this form
   *
   * @param $entity
   * @param $id
   * @throws \API_Exception
   */
  private function loadEntity($entity, $id) {
    $checkPermissions = TRUE;
    if ($entity['type'] == 'Contact' && !empty($this->args[$entity['name'] . '-cs'])) {
      $checkSum = civicrm_api4('Contact', 'validateChecksum', [
        'checksum' => $this->args[$entity['name'] . '-cs'],
        'contactId' => $id,
      ]);
      $checkPermissions = empty($checkSum[0]['valid']);
    }
    $result = civicrm_api4($entity['type'], 'get', [
      'where' => [['id', '=', $id]],
      'select' => array_column($entity['fields'], 'name'),
      'checkPermissions' => $checkPermissions,
    ]);
    if ($result->first()) {
      $this->_data[$entity['name']] = $result->first();
    }
  }

  /**
   * Fetch an entity based on its autofill settings
   *
   * @param $entity
   * @param $mode
   */
  private function autoFillEntity($entity, $mode) {
    $id = NULL;
    if ($entity['type'] == 'Contact') {
      if ($mode == 'user') {
        $id = \CRM_Core_Session::getLoggedInContactID();
      }
    }
    if ($id) {
      $this->loadEntity($entity, $id);
    }
  }

}
