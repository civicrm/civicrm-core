<?php

namespace Civi\Api4\Action\Afform;

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\AfformSubmission;
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
    // This uploader can be used when saving a draft or during final submission
    if ($this->isDraft()) {
      $draft = $this->getDraft();
    }
    else {
      $entityId = $this->getEntityId();
    }

    $file = civicrm_api4('File', 'create', [
      'values' => [
        'mime_type' => $_FILES['file']['type'],
        'file_name' => $_FILES['file']['name'],
        'move_file' => $_FILES['file']['tmp_name'],
      ],
      'checkPermissions' => FALSE,
    ])->single();

    if ($this->isDraft()) {
      return $this->updateDraft($draft, $file['id']);
    }
    else {
      return $this->updateEntity($entityId, $file['id']);
    }
  }

  /**
   * Load entityIds from web token
   */
  protected function loadEntities() {
    if ($this->isDraft()) {
      // Not needed when saving a draft
      return;
    }

    /** @var \Civi\Crypto\CryptoJwt $jwt */
    $jwt = \Civi::service('crypto.jwt');

    // Double-decode is needed to convert PHP objects to arrays
    $info = json_decode(json_encode($jwt->decode($this->token)), TRUE);

    if ($info['civiAfformSubmission']['name'] !== $this->getName()) {
      throw new UnauthorizedException('Name mismatch');
    }

    $this->_entityIds = $info['civiAfformSubmission']['data'];
  }

  private function getEntityApiName(): string {
    $afformEntity = $this->_formDataModel->getEntity($this->modelName);
    return $this->joinEntity ?: $afformEntity['type'];
  }

  private function getEntityId(): mixed {
    $apiEntity = $this->getEntityApiName();
    $entityIndex = (int) $this->entityIndex;
    $joinIndex = (int) $this->joinIndex;
    $idField = CoreUtil::getIdFieldName($apiEntity);
    if ($this->joinEntity) {
      $entityId = $this->_entityIds[$this->modelName][$entityIndex]['joins'][$this->joinEntity][$joinIndex][$idField] ?? NULL;
    }
    else {
      $entityId = $this->_entityIds[$this->modelName][$entityIndex][$idField] ?? NULL;
    }

    if (!$entityId) {
      throw new \CRM_Core_Exception('Entity not found');
    }
    return $entityId;
  }

  private function updateEntity(mixed $entityId, int $fileId): array {
    $apiEntity = $this->getEntityApiName();
    $idField = CoreUtil::getIdFieldName($apiEntity);
    civicrm_api4($apiEntity, 'update', [
      'values' => [
        $idField => $entityId,
        $this->fieldName => $fileId,
      ],
      'checkPermissions' => FALSE,
    ]);
    return [];
  }

  private function getDraft(): array {
    $cid = \CRM_Core_Session::getLoggedInContactID();
    if (!$cid) {
      throw new UnauthorizedException('Only authenticated users may save a draft.');
    }
    $draft = AfformSubmission::get(FALSE)
      ->addWhere('contact_id', '=', $cid)
      ->addWhere('status_id:name', '=', 'Draft')
      ->addWhere('afform_name', '=', $this->getName())
      ->execute()->first();
    if (!$draft) {
      throw new \CRM_Core_Exception('Draft not found');
    }
    return $draft;
  }

  private function updateDraft(array $draft, int $fileId): array {
    $fileInfo = $this->getFileInfo($fileId, $this->modelName);

    // Place fileInfo into correct entity
    $entityData =& $draft['data'][$this->modelName][$this->entityIndex];
    if ($this->joinEntity) {
      $entityData['joins'][$this->joinEntity][$this->joinIndex][$this->fieldName] = $fileInfo;
    }
    else {
      $entityData['fields'][$this->fieldName] = $fileInfo;
    }

    AfformSubmission::save(FALSE)
      ->addRecord($draft)
      ->execute();

    return [$fileInfo];
  }

  private function isDraft(): bool {
    return empty($this->token);
  }

}
