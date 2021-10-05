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

use Civi\Token\AbstractTokenSubscriber;
use Civi\Token\Event\TokenRegisterEvent;
use Civi\Token\Event\TokenValueEvent;
use Civi\Token\TokenRow;
use Civi\ActionSchedule\Event\MailingQueryEvent;
use Civi\Token\TokenProcessor;

/**
 * Class CRM_Core_EntityTokens
 *
 * Parent class for generic entity token functionality.
 *
 * WARNING - this class is highly likely to be temporary and
 * to be consolidated with the TokenTrait and / or the
 * AbstractTokenSubscriber in future. It is being used to clarify
 * functionality but should NOT be used from outside of core tested code.
 */
class CRM_Core_EntityTokens extends AbstractTokenSubscriber {

  /**
   * @var array
   */
  protected $prefetch = [];

  /**
   * Register the declared tokens.
   *
   * @param \Civi\Token\Event\TokenRegisterEvent $e
   *   The registration event. Add new tokens using register().
   */
  public function registerTokens(TokenRegisterEvent $e) {
    if (!$this->checkActive($e->getTokenProcessor())) {
      return;
    }
    foreach ($this->getAllTokens() as $name => $label) {
      if (!in_array($name, $this->getHiddenTokens(), TRUE)) {
        $e->register([
          'entity' => $this->entity,
          'field' => $name,
          'label' => $label,
        ]);
      }
    }
  }

  /**
   * @inheritDoc
   * @throws \CRM_Core_Exception
   */
  public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = NULL) {
    $this->prefetch = (array) $prefetch;
    $fieldValue = $this->getFieldValue($row, $field);
    if (is_array($fieldValue)) {
      // eg. role_id for participant would be an array here.
      $fieldValue = implode(',', $fieldValue);
    }

    if ($this->isPseudoField($field)) {
      if (!empty($fieldValue)) {
        // If it's set here it has already been loaded in pre-fetch.
        return $row->format('text/plain')->tokens($entity, $field, (string) $fieldValue);
      }
      // Once prefetch is fully standardised we can remove this - as long
      // as tests pass we should be fine as tests cover this.
      $split = explode(':', $field);
      return $row->tokens($entity, $field, $this->getPseudoValue($split[0], $split[1], $this->getFieldValue($row, $split[0])));
    }
    if ($this->isCustomField($field)) {
      $prefetchedValue = $this->getCustomFieldValue($this->getFieldValue($row, 'id'), $field);
      if ($prefetchedValue) {
        return $row->format('text/html')->tokens($entity, $field, $prefetchedValue);
      }
      return $row->customToken($entity, \CRM_Core_BAO_CustomField::getKeyID($field), $this->getFieldValue($row, 'id'));
    }
    if ($this->isMoneyField($field)) {
      return $row->format('text/plain')->tokens($entity, $field,
        \CRM_Utils_Money::format($fieldValue, $this->getCurrency($row)));
    }
    if ($this->isDateField($field)) {
      try {
        return $row->format('text/plain')
          ->tokens($entity, $field, new DateTime($fieldValue));
      }
      catch (Exception $e) {
        Civi::log()->info('invalid date token');
      }
    }
    $row->format('text/plain')->tokens($entity, $field, (string) $fieldValue);
  }

  /**
   * Metadata about the entity fields.
   *
   * @var array
   */
  protected $fieldMetadata = [];

  /**
   * Get the entity name for api v4 calls.
   *
   * @return string
   */
  protected function getApiEntityName(): string {
    return '';
  }

  /**
   * Get the entity alias to use within queries.
   *
   * The default has a double underscore which should prevent any
   * ambiguity with an existing table name.
   *
   * @return string
   */
  protected function getEntityAlias(): string {
    return $this->getApiEntityName() . '__';
  }

  /**
   * Get the name of the table this token class can extend.
   *
   * The default is based on the entity but some token classes,
   * specifically the event class, latch on to other tables - ie
   * the participant table.
   */
  public function getExtendableTableName(): string {
    return CRM_Core_DAO_AllCoreTables::getTableForEntityName($this->getApiEntityName());
  }

  /**
   * Get the relevant bao name.
   */
  public function getBAOName(): string {
    return CRM_Core_DAO_AllCoreTables::getFullName($this->getApiEntityName());
  }

  /**
   * Get an array of fields to be requested.
   *
   * @return string[]
   */
  public function getReturnFields(): array {
    return array_keys($this->getBasicTokens());
  }

  /**
   * Get all the tokens supported by this processor.
   *
   * @return array|string[]
   * @throws \API_Exception
   */
  protected function getAllTokens(): array {
    $basicTokens = $this->getBasicTokens();
    foreach (array_keys($basicTokens) as $fieldName) {
      // The goal is to be able to render more complete tokens
      // (eg. actual booleans, field names, raw ids) for a more
      // advanced audiences - ie those using conditionals
      // and to specify that audience in the api that retrieves.
      // But, for now, let's not advertise, given that most of these fields
      // aren't really needed even once...
      if ($this->isBooleanField($fieldName)) {
        unset($basicTokens[$fieldName]);
      }
    }
    foreach ($this->getBespokeTokens() as $token) {
      $basicTokens[$token['name']] = $token['title'];
    }
    return array_merge($basicTokens, $this->getPseudoTokens(), CRM_Utils_Token::getCustomFieldTokens($this->getApiEntityName()));
  }

  /**
   * Is the given field a boolean field.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  public function isBooleanField(string $fieldName): bool {
    return $this->getFieldMetadata()[$fieldName]['data_type'] === 'Boolean';
  }

  /**
   * Is the given field a date field.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  public function isDateField(string $fieldName): bool {
    return in_array($this->getFieldMetadata()[$fieldName]['data_type'], ['Timestamp', 'Date'], TRUE);
  }

  /**
   * Is the given field a pseudo field.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  public function isPseudoField(string $fieldName): bool {
    return strpos($fieldName, ':') !== FALSE;
  }

  /**
   * Is the given field a custom field.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  public function isCustomField(string $fieldName) : bool {
    return (bool) \CRM_Core_BAO_CustomField::getKeyID($fieldName);
  }

  /**
   * Is the given field a date field.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  public function isMoneyField(string $fieldName): bool {
    return $this->getFieldMetadata()[$fieldName]['data_type'] === 'Money';
  }

  /**
   * Get the metadata for the available fields.
   *
   * @return array
   */
  protected function getFieldMetadata(): array {
    if (empty($this->fieldMetadata)) {
      try {
        // Tests fail without checkPermissions = FALSE
        $this->fieldMetadata = (array) civicrm_api4($this->getApiEntityName(), 'getfields', ['checkPermissions' => FALSE], 'name');
      }
      catch (API_Exception $e) {
        $this->fieldMetadata = [];
      }
    }
    return $this->fieldMetadata;
  }

  /**
   * Get pseudoTokens - it tokens that reflect the name or label of a pseudoconstant.
   *
   * @internal - this function will likely be made protected soon.
   *
   * @return array
   */
  public function getPseudoTokens(): array {
    $return = [];
    foreach (array_keys($this->getBasicTokens()) as $fieldName) {
      if ($this->isAddPseudoTokens($fieldName)) {
        $fieldLabel = $this->fieldMetadata[$fieldName]['input_attrs']['label'] ?? $this->fieldMetadata[$fieldName]['label'];
        $return[$fieldName . ':label'] = $fieldLabel;
        $return[$fieldName . ':name'] = ts('Machine name') . ': ' . $fieldLabel;
      }
      if ($this->isBooleanField($fieldName)) {
        $return[$fieldName . ':label'] = $this->getFieldMetadata()[$fieldName]['title'];
      }
    }
    return $return;
  }

  /**
   * Get any tokens with custom calculation.
   */
  protected function getBespokeTokens(): array {
    return [];
  }

  /**
   * Is this a field we should add pseudo-tokens to?
   *
   * Pseudo-tokens allow access to name and label fields - e.g
   *
   * {contribution.contribution_status_id:name} might resolve to 'Completed'
   *
   * @param string $fieldName
   */
  public function isAddPseudoTokens($fieldName): bool {
    if (in_array($fieldName, $this->getCurrencyFieldName())) {
      // 'currency' is manually added to the skip list as an anomaly.
      // name & label aren't that suitable for 'currency' (symbol, which
      // possibly maps to 'abbr' would be) and we can't gather that
      // from the metadata as yet.
      return FALSE;
    }
    if ($this->getFieldMetadata()[$fieldName]['type'] === 'Custom') {
      // If we remove this early return then we get that extra nuanced goodness
      // and support for the more portable v4 style field names
      // on custom fields - where labels or names can be returned.
      // At present the gap is that the metadata for the label is not accessed
      // and tests failed on the enotice and we don't have a clear plan about
      // v4 style custom tokens - but medium term this IF will probably go.
      return FALSE;
    }
    return (bool) ($this->getFieldMetadata()[$fieldName]['options'] || !empty($this->getFieldMetadata()[$fieldName]['suffixes']));
  }

  /**
   * Get the value for the relevant pseudo field.
   *
   * @param string $realField e.g contribution_status_id
   * @param string $pseudoKey e.g name
   * @param int|string $fieldValue e.g 1
   *
   * @return string
   *   Eg. 'Completed' in the example above.
   *
   * @internal function will likely be protected soon.
   */
  public function getPseudoValue(string $realField, string $pseudoKey, $fieldValue): string {
    if ($pseudoKey === 'name') {
      $fieldValue = (string) CRM_Core_PseudoConstant::getName($this->getBAOName(), $realField, $fieldValue);
    }
    if ($pseudoKey === 'label') {
      $fieldValue = (string) CRM_Core_PseudoConstant::getLabel($this->getBAOName(), $realField, $fieldValue);
    }
    return (string) $fieldValue;
  }

  /**
   * @param \Civi\Token\TokenRow $row
   * @param string $field
   * @return string|int
   */
  protected function getFieldValue(TokenRow $row, string $field) {
    $entityName = $this->getEntityName();
    if (isset($row->context[$entityName][$field])) {
      return $row->context[$entityName][$field];
    }

    $actionSearchResult = $row->context['actionSearchResult'];
    $aliasedField = $this->getEntityAlias() . $field;
    if (isset($actionSearchResult->{$aliasedField})) {
      return $actionSearchResult->{$aliasedField};
    }
    $entityID = $row->context[$this->getEntityIDField()];
    if ($field === 'id') {
      return $entityID;
    }
    return $this->prefetch[$entityID][$field] ?? '';
  }

  /**
   * Class constructor.
   */
  public function __construct() {
    $tokens = $this->getAllTokens();
    parent::__construct($this->getEntityName(), $tokens);
  }

  /**
   * Check if the token processor is active.
   *
   * @param \Civi\Token\TokenProcessor $processor
   *
   * @return bool
   */
  public function checkActive(TokenProcessor $processor) {
    return (!empty($processor->context['actionMapping'])
        // This makes the 'schema context compulsory - which feels accidental
        // since recent discu
      && $processor->context['actionMapping']->getEntity()) || in_array($this->getEntityIDField(), $processor->context['schema']);
  }

  /**
   * Alter action schedule query.
   *
   * @param \Civi\ActionSchedule\Event\MailingQueryEvent $e
   */
  public function alterActionScheduleQuery(MailingQueryEvent $e): void {
    if ($e->mapping->getEntity() !== $this->getExtendableTableName()) {
      return;
    }
    foreach ($this->getReturnFields() as $token) {
      $e->query->select('e.' . $token . ' AS ' . $this->getEntityAlias() . $token);
    }
  }

  /**
   * Get tokens to be suppressed from the widget.
   *
   * Note this is expected to be an interim function. Now we are no
   * longer working around the parent function we can just define them once...
   * with metadata, in a future refactor.
   */
  protected function getHiddenTokens(): array {
    return [];
  }

  /**
   * Get tokens supporting the syntax we are migrating to.
   *
   * In general these are tokens that were not previously supported
   * so we can add them in the preferred way or that we have
   * undertaken some, as yet to be written, db update.
   *
   * See https://lab.civicrm.org/dev/core/-/issues/2650
   *
   * @return string[]
   * @throws \API_Exception
   */
  public function getBasicTokens(): array {
    $return = [];
    foreach ($this->getExposedFields() as $fieldName) {
      // Custom fields are still added v3 style - we want to keep v4 naming 'unpoluted'
      // for now to allow us to consider how to handle names vs labels vs values
      // and other raw vs not raw options.
      if ($this->getFieldMetadata()[$fieldName]['type'] !== 'Custom') {
        $return[$fieldName] = $this->getFieldMetadata()[$fieldName]['title'];
      }
    }
    return $return;
  }

  /**
   * Get entity fields that should be exposed as tokens.
   *
   * @return string[]
   *
   */
  protected function getExposedFields(): array {
    $return = [];
    foreach ($this->getFieldMetadata() as $field) {
      if (!in_array($field['name'], $this->getSkippedFields(), TRUE)) {
        $return[] = $field['name'];
      }
    }
    return $return;
  }

  /**
   * Get entity fields that should not be exposed as tokens.
   *
   * @return string[]
   */
  public function getSkippedFields(): array {
    // tags is offered in 'case' & is one of the only fields that is
    // 'not a real field' offered up by case - seems like an oddity
    // we should skip at the top level for now.
    $fields = ['contact_id', 'tags'];
    if (!CRM_Campaign_BAO_Campaign::isCampaignEnable()) {
      $fields[] = 'campaign_id';
    }
    return $fields;
  }

  /**
   * @return string
   */
  protected function getEntityName(): string {
    return CRM_Core_DAO_AllCoreTables::convertEntityNameToLower($this->getApiEntityName());
  }

  public function getEntityIDField(): string {
    return $this->getEntityName() . 'Id';
  }

  public function prefetch(TokenValueEvent $e): ?array {
    $entityIDs = $e->getTokenProcessor()->getContextValues($this->getEntityIDField());
    if (empty($entityIDs)) {
      return [];
    }
    $select = $this->getPrefetchFields($e);
    $result = (array) civicrm_api4($this->getApiEntityName(), 'get', [
      'checkPermissions' => FALSE,
      // Note custom fields are not yet added - I need to
      // re-do the unit tests to support custom fields first.
      'select' => $select,
      'where' => [['id', 'IN', $entityIDs]],
    ], 'id');
    return $result;
  }

  public function getCurrencyFieldName() {
    return [];
  }

  /**
   * Get the currency to use for formatting money.
   * @param $row
   *
   * @return string
   */
  public function getCurrency($row): string {
    if (!empty($this->getCurrencyFieldName())) {
      return $this->getFieldValue($row, $this->getCurrencyFieldName()[0]);
    }
    return CRM_Core_Config::singleton()->defaultCurrency;
  }

  /**
   * Get the fields required to prefetch the entity.
   *
   * @param \Civi\Token\Event\TokenValueEvent $e
   *
   * @return array
   * @throws \API_Exception
   */
  public function getPrefetchFields(TokenValueEvent $e): array {
    $allTokens = array_keys($this->getAllTokens());
    $requiredFields = array_intersect($this->getActiveTokens($e), $allTokens);
    if (empty($requiredFields)) {
      return [];
    }
    $requiredFields = array_merge($requiredFields, array_intersect($allTokens, array_merge(['id'], $this->getCurrencyFieldName())));
    foreach ($this->getDependencies() as $field => $required) {
      if (in_array($field, $this->getActiveTokens($e), TRUE)) {
        foreach ((array) $required as $key) {
          $requiredFields[] = $key;
        }
      }
    }
    return $requiredFields;
  }

  /**
   * Get fields which need to be returned to render another token.
   *
   * @return array
   */
  public function getDependencies(): array {
    return [];
  }

  /**
   * Get the apiv4 style custom field name.
   *
   * @param int $id
   *
   * @return string
   */
  protected function getCustomFieldName(int $id): string {
    foreach ($this->getFieldMetadata() as $key => $field) {
      if (($field['custom_field_id'] ?? NULL) === $id) {
        return $key;
      }
    }
  }

  /**
   * @param $entityID
   * @param string $field eg. 'custom_1'
   *
   * @return array|string|void|null $mixed
   *
   * @throws \CRM_Core_Exception
   */
  protected function getCustomFieldValue($entityID, string $field) {
    $id = str_replace('custom_', '', $field);
    $value = $this->prefetch[$entityID][$this->getCustomFieldName($id)] ?? NULL;
    if ($value !== NULL) {
      return CRM_Core_BAO_CustomField::displayValue($value, $id);
    }
  }

}
