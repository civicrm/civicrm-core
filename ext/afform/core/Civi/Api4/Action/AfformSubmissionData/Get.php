<?php
namespace Civi\Api4\Action\AfformSubmissionData;

use Civi\Api4\Generic\Result;

class Get extends \Civi\Api4\Generic\BasicGetAction {

  /**
   * @var bool
   */
  private $isWildcardSelect = FALSE;

  public function _run(Result $result) {
    $select = $this->getSelect();
    if (empty($select) || in_array('*', $select, TRUE)) {
      $this->isWildcardSelect = TRUE;
    }
    $this->expandSelectClauseWildcards();
    $values = $this->getSubmissionData($result);
    $this->queryArray($values, $result);
  }

  public function getSubmissionData(Result $result): array {
    // 1. Fetch submission records for this afform
    $selectClause = $this->getSelect();
    $selectClause = array_unique(array_merge($selectClause, ['data', 'afform_name']));
    $whereClause = $this->getWhere();
    $submissions = \Civi\Api4\AfformSubmission::get($this->checkPermissions)
      ->setSelect($selectClause)
      ->setWhere($whereClause)
      ->setDebug($this->debug)
      ->execute();
    $result->debug = $submissions->debug;

    $afformNameClause = $this->extractAfformNameFromWhereClause();

    if (!count($submissions) || !$afformNameClause) {
      return (array) $submissions;
    }

    $afformName = $submissions[0]['afform_name'];

    // 3. Load layout fields for the given afform
    $afform = \Civi\Api4\Afform::get(FALSE)
      ->addSelect('layout')
      ->addWhere('name', '=', $afformName)
      ->execute()->first();

    $layout = $afform['layout'] ?? NULL;
    $formDataModel = $layout ? new \Civi\Afform\FormDataModel($layout) : NULL;

    $referencedFields = $this->getReferencedFields($formDataModel);
    foreach ($referencedFields as $fieldPath) {
      if (!in_array($fieldPath, $this->select, TRUE)) {
        $this->select[] = $fieldPath;
      }
    }

    $baseFields = \Civi::entity('AfformSubmission')->getFields();

    // 5. Build records
    $records = [];
    foreach ($submissions as $submission) {
      $record = $submission;
      unset($record['data']);

      $data = $submission['data'] ?? [];

      foreach ($referencedFields as $fieldPath) {
        // Skip standard fields (already populated)
        [$fieldBase] = explode('.', $fieldPath);
        [$fieldBase] = explode(':', $fieldBase);
        if (array_key_exists($fieldBase, $baseFields)) {
          continue;
        }
        $record[$fieldPath] = self::getSubmittedValue($data, $fieldPath);
      }

      $records[] = $record;
    }

    return $records;
  }

  protected function getReferencedFields(?\Civi\Afform\FormDataModel $formDataModel): array {
    $fields = [];

    $select = $this->getSelect();
    if ($this->isWildcardSelect && $formDataModel) {
      // Add all index 0 fields of the layout
      foreach ($formDataModel->getEntities() as $entityName => $entity) {
        if ($entityName !== 'extra') {
          foreach ($entity['fields'] as $fieldName => $props) {
            $fields["$entityName.0.$fieldName"] = TRUE;
          }
          $fields["$entityName.0.id"] = TRUE;
          foreach ($entity['joins'] as $joinEntity => $join) {
            foreach ($join['fields'] as $fieldName => $props) {
              $fields["$entityName.0.$joinEntity.0.$fieldName"] = TRUE;
            }
            $fields["$entityName.0.$joinEntity.0.id"] = TRUE;
          }
        }
        else {
          foreach ($entity['fields'] as $fieldName => $props) {
            $fields["extra.$fieldName"] = TRUE;
          }
        }
      }
    }
    else {
      foreach ($select as $fieldName) {
        $fields[$fieldName] = TRUE;
      }
    }

    // WHERE clause
    foreach ($this->getWhere() as $clause) {
      $this->extractFieldsFromClause($clause, $fields);
    }

    return array_keys($fields);
  }

  private function extractFieldsFromClause($clause, &$fields) {
    if (!is_array($clause)) {
      return;
    }
    if (in_array($clause[0], ['AND', 'OR', 'NOT'], TRUE)) {
      foreach ($clause[1] ?? [] as $sub) {
        $this->extractFieldsFromClause($sub, $fields);
      }
    }
    elseif (isset($clause[0]) && is_string($clause[0])) {
      $fields[explode(':', $clause[0])[0]] = TRUE;
    }
  }

  public static function getSubmittedValue(array $data, string $fieldPath) {
    $parts = explode('.', $fieldPath);
    if (empty($parts[0])) {
      return NULL;
    }

    // Case 1: extra.field_name
    if ($parts[0] === 'extra' && isset($parts[1])) {
      return $data['extra']['fields'][$parts[1]] ?? NULL;
    }

    // Case 2: EntityName.Index.FieldName
    if (count($parts) === 3) {
      [$entityName, $index, $field] = $parts;
      if (!isset($data[$entityName])) {
        return NULL;
      }
      $entityData = $data[$entityName];
      if (isset($entityData[$index])) {
        $item = $entityData[$index];
      }
      elseif ($index == 0 && isset($entityData['fields'])) {
        $item = $entityData;
      }
      else {
        return NULL;
      }
      if ($field === 'id') {
        return $item['id'] ?? NULL;
      }
      return $item['fields'][$field] ?? NULL;
    }

    // Case 3: EntityName.Index.JoinName.Index.FieldName
    if (count($parts) === 5) {
      [$entityName, $index, $joinEntity, $joinIndex, $field] = $parts;
      if (!isset($data[$entityName])) {
        return NULL;
      }
      $entityData = $data[$entityName];
      if (isset($entityData[$index])) {
        $parentItem = $entityData[$index];
      }
      elseif ($index == 0 && isset($entityData['joins'])) {
        $parentItem = $entityData;
      }
      else {
        return NULL;
      }
      $joins = $parentItem['joins'] ?? NULL;
      if (!$joins || !isset($joins[$joinEntity])) {
        return NULL;
      }
      $joinList = $joins[$joinEntity];
      if (isset($joinList[$joinIndex])) {
        $joinItem = $joinList[$joinIndex];
      }
      elseif ($joinIndex == 0 && (isset($joinItem['fields']) || isset($joinItem[$field]))) {
        $joinItem = $joinList;
      }
      else {
        return NULL;
      }
      if ($field === 'id') {
        return $joinItem['id'] ?? NULL;
      }
      return $joinItem[$field] ?? $joinItem['fields'][$field] ?? NULL;
    }

    return NULL;
  }

  private function extractAfformNameFromWhereClause(): bool {
    foreach ($this->where as $index => $clause) {
      if (
        ($clause[0] === 'afform_name' || $clause[0] === 'afform_name:name') &&
        // Clause is not set to match an expression
        empty($clause[3]) &&
        // Clause uses exact-match operators (=, IN, or LIKE with no wildcard)
        in_array($clause[1], ['=', 'IN', 'LIKE'], TRUE) &&
        ((is_string($clause[2]) && !str_contains($clause[2], '%')) || (is_array($clause[2]) && count($clause[2]) === 1))
      ) {
        unset($this->where[$index]);
        return TRUE;
      }
    }
    return FALSE;
  }

}
