<?php

namespace Civi\Api4\Action\Afform;

/**
 * Class Prefill
 * @package Civi\Api4\Action\Afform
 */
class Prefill extends AbstractProcessor {

  protected $_data = [];

  protected function processForm() {
    foreach ($this->_formDataModel->getEntities() as $entityName => $entity) {
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
   * Fetch all data needed to display a given entity on this form
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
      'select' => array_keys($entity['fields']),
      'checkPermissions' => $checkPermissions,
    ]);
    foreach ($result as $item) {
      $data = ['fields' => $item];
      foreach ($entity['joins'] ?? [] as $joinEntity => $join) {
        $data['joins'][$joinEntity] = (array) civicrm_api4($joinEntity, 'get', [
          'where' => self::getJoinWhereClause($entity['type'], $joinEntity, $item['id']),
          'limit' => !empty($join['af-repeat']) ? $join['max'] ?? 0 : 1,
          'select' => array_keys($join['fields']),
          'checkPermissions' => $checkPermissions,
          'orderBy' => self::fieldExists($joinEntity, 'is_primary') ? ['is_primary' => 'DESC'] : [],
        ]);
      }
      $this->_data[$entity['name']][] = $data;
    }
  }

  /**
   * Fetch an entity based on its autofill settings
   *
   * @param $entity
   * @param $mode
   * @throws \API_Exception
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
