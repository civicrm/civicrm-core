<?php
namespace Civi\Api4\Action\AfformSubmissionData;

use Civi\Api4\Generic\Result;
use Civi\Api4\Utils\FormattingUtil;

class Get extends \Civi\Api4\Generic\BasicGetAction {

  use AfformSubmissionDataTrait;

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

    $formDataModel = $this->getFormDataModel($afformName);

    $referencedExpressions = $this->getReferencedExpressions($formDataModel);
    foreach ($referencedExpressions as $expression) {
      if (!in_array($expression, $this->select, TRUE)) {
        $this->select[] = $expression;
      }
    }

    $entitySpecs = $this->loadEntitySpecs($formDataModel, ['id', 'name', 'label']);

    $baseFields = \Civi::entity('AfformSubmission')->getFields();

    // 5. Build records
    $records = [];
    foreach ($submissions as $submission) {
      $record = $submission;
      unset($record['data']);

      $data = $submission['data'] ?? [];

      foreach ($referencedExpressions as $expression) {
        // Skip standard fields (already populated)
        [$fieldBase] = explode('.', $expression);
        [$fieldBase] = explode(':', $fieldBase);
        if (array_key_exists($fieldBase, $baseFields)) {
          continue;
        }
        $record[$expression] = $this->resolveAndFormatField($expression, $data, $formDataModel, $entitySpecs);
      }

      $records[] = $record;
    }

    return $records;
  }

  protected function getReferencedExpressions(?\Civi\Afform\FormDataModel $formDataModel): array {
    $fields = [];

    $select = $this->getSelect();
    if ($this->isWildcardSelect && $formDataModel) {
      // Add all index 0 fields of the layout
      foreach ($this->getLayoutFields($formDataModel) as $lf) {
        $fields[$lf['name']] = TRUE;
      }
    }
    foreach ($select as $f) {
      if ($f !== '*') {
        $fields[$f] = TRUE;
      }
    }

    // WHERE clause
    foreach ($this->getWhere() as $clause) {
      $this->extractExpressionsFromClause($clause, $fields);
    }

    return array_keys($fields);
  }

  private function extractExpressionsFromClause($clause, &$fields) {
    if (!is_array($clause)) {
      return;
    }
    if (in_array($clause[0], ['AND', 'OR', 'NOT'], TRUE)) {
      foreach ($clause[1] ?? [] as $sub) {
        $this->extractExpressionsFromClause($sub, $fields);
      }
    }
    elseif (isset($clause[0]) && is_string($clause[0])) {
      $fields[$clause[0]] = TRUE;
    }
  }

  public static function parseFieldPath(string $fieldPath) {
    $parts = explode('.', $fieldPath);
    if (empty($parts[0])) {
      return NULL;
    }

    // extra.field_name
    if ($parts[0] === 'extra' && isset($parts[1])) {
      $lastPart = $parts[1];
      $suffix = NULL;
      if (str_contains($lastPart, ':')) {
        [$lastPart, $suffix] = explode(':', $lastPart, 2);
      }
      return [
        'type' => 'extra',
        'field' => $lastPart,
        'suffix' => $suffix,
      ];
    }

    // EntityName.Index.FieldName
    if (count($parts) === 3) {
      [$entityName, $index, $lastPart] = $parts;
      $suffix = NULL;
      if (str_contains($lastPart, ':')) {
        [$lastPart, $suffix] = explode(':', $lastPart, 2);
      }
      return [
        'type' => 'entity',
        'entityName' => $entityName,
        'index' => $index,
        'field' => $lastPart,
        'suffix' => $suffix,
      ];
    }

    // EntityName.Index.JoinName.Index.FieldName
    if (count($parts) === 5) {
      [$entityName, $index, $joinEntity, $joinIndex, $lastPart] = $parts;
      $suffix = NULL;
      if (str_contains($lastPart, ':')) {
        [$lastPart, $suffix] = explode(':', $lastPart, 2);
      }
      return [
        'type' => 'join',
        'entityName' => $entityName,
        'index' => $index,
        'joinEntity' => $joinEntity,
        'joinIndex' => $joinIndex,
        'field' => $lastPart,
        'suffix' => $suffix,
      ];
    }

    return NULL;
  }

  protected function resolveAndFormatField(string $expression, array $data, ?\Civi\Afform\FormDataModel $formDataModel, array $entitySpecs) {
    $parsed = self::parseFieldPath($expression);
    if (!$parsed || !$formDataModel) {
      return NULL;
    }

    $baseFieldName = $parsed['field'];
    if ($parsed['type'] === 'extra') {
      return $data['extra']['fields'][$baseFieldName] ?? NULL;
    }

    $entityName = $parsed['entityName'] ?? '';
    $joinEntity = $parsed['joinEntity'] ?? NULL;
    $requestedSuffix = $parsed['suffix'];

    // Find the entity in the layout
    $entities = $formDataModel->getEntities();
    $entity = $entityName !== '' ? ($entities[$entityName] ?? NULL) : NULL;
    if (!$entity) {
      return NULL;
    }

    $entityType = $entity['type'];
    $entityFieldsInLayout = $entity['fields'] ?? [];

    // If it's a join, look up join entity type
    if ($joinEntity) {
      $joinInfo = $entity['joins'][$joinEntity] ?? NULL;
      if (!$joinInfo) {
        return NULL;
      }
      $entityType = $joinEntity;
      $entityFieldsInLayout = $joinInfo['fields'] ?? [];
    }

    // Find the exact layout field name and suffix
    $layoutFieldName = $baseFieldName;
    $layoutSuffix = NULL;
    foreach (array_keys($entityFieldsInLayout) as $layoutKey) {
      $keyParts = explode(':', $layoutKey);
      if ($keyParts[0] === $baseFieldName) {
        $layoutFieldName = $layoutKey;
        $layoutSuffix = $keyParts[1] ?? NULL;
        break;
      }
    }

    // Retrieve the raw stored value from JSON
    $storedValue = $this->getRawSubmittedValue($data, $parsed, $layoutFieldName);

    // Format the value based on specs
    $spec = $entitySpecs[$entityType][$baseFieldName] ?? NULL;
    $options = $spec['options'] ?? NULL;
    $serialize = !empty($spec['serialize']);

    if (is_array($options)) {
      $normalizedOptions = [];
      $isAssoc = array_keys($options) !== range(0, count($options) - 1);
      if ($isAssoc) {
        foreach ($options as $id => $label) {
          $normalizedOptions[] = [
            'id' => $id,
            'name' => $label,
            'label' => $label,
          ];
        }
      }
      else {
        $normalizedOptions = $options;
      }
      $options = $normalizedOptions;
    }
    else {
      $options = NULL;
    }

    return self::formatValueWithSuffix($storedValue, $options, $requestedSuffix, $layoutSuffix, $serialize);
  }

  protected function getRawSubmittedValue(array $data, array $parsed, string $layoutFieldName) {
    $entityName = $parsed['entityName'];
    $index = $parsed['index'];

    if ($parsed['type'] === 'entity') {
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
      if ($layoutFieldName === 'id') {
        return $item['id'] ?? NULL;
      }
      return $item['fields'][$layoutFieldName] ?? NULL;
    }

    if ($parsed['type'] === 'join') {
      $joinEntity = $parsed['joinEntity'];
      $joinIndex = $parsed['joinIndex'];

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
      elseif ($joinIndex == 0 && (isset($joinList['fields']) || isset($joinList[$layoutFieldName]))) {
        $joinItem = $joinList;
      }
      else {
        return NULL;
      }

      if ($layoutFieldName === 'id') {
        return $joinItem['id'] ?? NULL;
      }
      return $joinItem[$layoutFieldName] ?? $joinItem['fields'][$layoutFieldName] ?? NULL;
    }

    return NULL;
  }

  public static function formatValueWithSuffix($value, ?array $options, ?string $requestedSuffix, ?string $layoutSuffix, ?bool $serialize) {
    if ($value === NULL || $value === '' || empty($options)) {
      return $value;
    }

    // 1. Convert layout-suffix formatted value(s) back to raw ID(s)
    $storedProperty = $layoutSuffix ?: 'id';
    if ($storedProperty !== 'id') {
      $storedOptions = array_column($options, $storedProperty, 'id');
      $id = FormattingUtil::replacePseudoconstant($storedOptions, $value, TRUE);
    }
    else {
      $id = $value;
    }

    // 2. Format raw ID(s) to the requested suffix
    $requestedProperty = $requestedSuffix ?: 'id';
    if ($requestedProperty !== 'id') {
      $requestedOptions = array_column($options, $requestedProperty, 'id');
      return FormattingUtil::replacePseudoconstant($requestedOptions, $id, FALSE);
    }

    return $id;
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
