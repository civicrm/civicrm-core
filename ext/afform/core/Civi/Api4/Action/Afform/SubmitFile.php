<?php

namespace Civi\Api4\Action\Afform;

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Utils\CoreUtil;

/**
 * Special-purpose API for uploading files as part of a form submission.
 *
 * This API is meant to be called with a multipart POST ajax request which includes the uploaded file.
 *
 * @method $this setToken(string $token)
 * @method $this setFieldName(string $fieldName)
 * @method $this setModelName(string $modelName)
 * @method $this setJoinEntity(string $joinEntity)
 * @method $this setEntityIndex(int $entityIndex)
 * @method $this setJoinIndex(int $joinIndex)
 * @method string getToken()
 * @method string getFieldName()
 * @method string getModelName()
 * @method string getJoinEntity()
 * @method int getEntityIndex()
 * @method int getJoinIndex()
 * @package Civi\Api4\Action\Afform
 */
class SubmitFile extends AbstractProcessor {

  /**
   * Submission token
   * @var string
   * @required
   */
  protected $token;

  /**
   * @var string
   * @required
   */
  protected $modelName;

  /**
   * @var string
   * @required
   */
  protected $fieldName;

  /**
   * @var string
   */
  protected $joinEntity;

  /**
   * @var string|int
   */
  protected $entityIndex;

  /**
   * @var string|int
   */
  protected $joinIndex;

  protected function processForm() {
    if (empty($_FILES['file'])) {
      throw new \CRM_Core_Exception('File upload required');
    }
    $afformEntity = $this->_formDataModel->getEntity($this->modelName);
    $apiEntity = $this->joinEntity ?: $afformEntity['type'];
    $entityIndex = (int) $this->entityIndex;
    $joinIndex = (int) $this->joinIndex;
    $idField = CoreUtil::getIdFieldName($apiEntity);
    if ($this->joinEntity) {
      $entityId = $this->_entityIds[$this->modelName][$entityIndex]['_joins'][$this->joinEntity][$joinIndex][$idField] ?? NULL;
    }
    else {
      $entityId = $this->_entityIds[$this->modelName][$entityIndex][$idField] ?? NULL;
    }

    if (!$entityId) {
      throw new \CRM_Core_Exception('Entity not found');
    }

    $attachmentParams = [
      'entity_id' => $entityId,
      'mime_type' => $_FILES['file']['type'],
      'name' => $_FILES['file']['name'],
      'options' => [
        'move-file' => $_FILES['file']['tmp_name'],
      ],
    ];

    if (strpos($this->fieldName, '.')) {
      $attachmentParams['field_name'] = $this->convertFieldNameToApi3($apiEntity, $this->fieldName);
    }
    else {
      $attachmentParams['entity_table'] = CoreUtil::getTableName($apiEntity);
    }

    $file = civicrm_api3('Attachment', 'create', $attachmentParams);

    // Update multi-record custom field with value
    if (strpos($apiEntity, 'Custom_') === 0) {
      civicrm_api4($apiEntity, 'update', [
        'values' => [
          $idField => $entityId,
          $this->fieldName => $file['id'],
        ],
      ]);
    }

    return [];
  }

  /**
   * Load entityIds from web token
   */
  protected function loadEntities() {
    /** @var \Civi\Crypto\CryptoJwt $jwt */
    $jwt = \Civi::service('crypto.jwt');

    // Double-decode is needed to convert PHP objects to arrays
    $info = json_decode(json_encode($jwt->decode($this->token)), TRUE);

    if ($info['civiAfformSubmission']['name'] !== $this->getName()) {
      throw new UnauthorizedException('Name mismatch');
    }

    $this->_entityIds = $info['civiAfformSubmission']['data'];
  }

  /**
   * @param string $apiEntity
   * @param string $fieldName
   * @return string
   */
  private function convertFieldNameToApi3($apiEntity, $fieldName) {
    if (strpos($fieldName, '.')) {
      $fields = civicrm_api4($apiEntity, 'getFields', [
        'checkPermissions' => FALSE,
        'where' => [['name', '=', $fieldName]],
      ]);
      return 'custom_' . $fields[0]['custom_field_id'];
    }
    return $fieldName;
  }

}
