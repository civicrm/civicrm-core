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
use Brick\Money\Money;
use Brick\Math\RoundingMode;

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
   * Metadata about all tokens.
   *
   * @var array
   */
  protected $tokensMetadata = [];
  /**
   * @var array
   */
  protected $prefetch = [];

  /**
   * Should permissions be checked when loading tokens.
   *
   * @var bool
   */
  protected $checkPermissions = FALSE;

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
    foreach ($this->getTokenMetadata() as $tokenName => $field) {
      if ($field['audience'] === 'user') {
        $e->register([
          'entity' => $this->entity,
          'field' => $tokenName,
          'label' => $field['title'],
        ]);
      }
    }
  }

  /**
   * Get the metadata about the available tokens
   *
   * @return array
   */
  protected function getTokenMetadata(): array {
    $cacheKey = $this->getCacheKey();
    if (!Civi::cache('metadata')->has($cacheKey)) {
      $tokensMetadata = $this->getBespokeTokens();
      $tokensMetadata = array_merge($tokensMetadata, $this->getRelatedTokens());
      foreach ($this->getFieldMetadata() as $field) {
        $this->addFieldToTokenMetadata($tokensMetadata, $field, $this->getExposedFields());
      }
      foreach ($this->getHiddenTokens() as $name) {
        $tokensMetadata[$name]['audience'] = 'hidden';
      }
      Civi::cache('metadata')->set($cacheKey, $tokensMetadata);
    }
    return Civi::cache('metadata')->get($cacheKey);
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
      $fieldValue = implode(', ', $fieldValue);
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
      $currency = $this->getCurrency($row) ?: \Civi::settings()->get('defaultCurrency');
      if (empty($fieldValue) && !is_numeric($fieldValue)) {
        $fieldValue = 0;
      }

      return $row->format('text/plain')->tokens($entity, $field,
        Money::of($fieldValue, $currency, NULL, RoundingMode::HALF_UP));

    }
    if ($this->isDateField($field)) {
      try {
        return $row->format('text/plain')
          ->tokens($entity, $field, ($fieldValue ? new DateTime($fieldValue) : $fieldValue));
      }
      catch (Exception $e) {
        Civi::log()->info('invalid date token');
      }
    }
    if ($this->isHTMLTextField($field)) {
      return $row->format('text/html')->tokens($entity, $field, (string) $fieldValue);
    }
    $row->format('text/plain')->tokens($entity, $field, (string) $fieldValue);
  }

  /**
   * Is the text stored in html format.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  public function isHTMLTextField(string $fieldName): bool {
    return ($this->getMetadataForField($fieldName)['input_type'] ?? NULL) === 'RichTextEditor';
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
   * Get an array of fields to be requested.
   *
   * @todo this function should look up tokenMetadata that
   * is already loaded.
   *
   * @return string[]
   */
  protected function getReturnFields(): array {
    return array_keys($this->getBasicTokens());
  }

  /**
   * Is the given field a boolean field.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  protected function isBooleanField(string $fieldName): bool {
    return $this->getMetadataForField($fieldName)['data_type'] === 'Boolean';
  }

  /**
   * Is the given field a date field.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  protected function isDateField(string $fieldName): bool {
    return in_array($this->getMetadataForField($fieldName)['data_type'], ['Timestamp', 'Date'], TRUE);
  }

  /**
   * Is the given field a pseudo field.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  protected function isPseudoField(string $fieldName): bool {
    return str_contains($fieldName, ':');
  }

  /**
   * Is the given field a custom field.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  protected function isCustomField(string $fieldName) : bool {
    return (bool) \CRM_Core_BAO_CustomField::getKeyID($fieldName);
  }

  /**
   * Is the given field a date field.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  protected function isMoneyField(string $fieldName): bool {
    return $this->getMetadataForField($fieldName)['data_type'] === 'Money';
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
      catch (CRM_Core_Exception $e) {
        $this->fieldMetadata = [];
      }
    }
    return $this->fieldMetadata;
  }

  /**
   * Get any tokens with custom calculation.
   */
  protected function getBespokeTokens(): array {
    return [];
  }

  /**
   * Get related entity tokens.
   */
  protected function getRelatedTokens(): array {
    return [];
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
  protected function getPseudoValue(string $realField, string $pseudoKey, $fieldValue): string {
    $bao = CRM_Core_DAO_AllCoreTables::getDAONameForEntity($this->getMetadataForField($realField)['entity']);
    if ($pseudoKey === 'name') {
      // There is a theoretical possibility fieldValue could be an array but
      // specifically for preferred communication type - but real world usage
      // hitting this is unlikely & the unexpectation is unclear so commenting,
      // rather than adding handling.
      $fieldValue = (string) CRM_Core_PseudoConstant::getName($bao, $realField, $fieldValue);
    }
    if ($pseudoKey === 'label') {
      $newValue = [];
      // Preferred communication method is an array that would resolve to (e.g) 'Phone, Email'
      foreach ((array) $fieldValue as $individualValue) {
        $newValue[] = CRM_Core_PseudoConstant::getLabel($bao, $realField, $individualValue);
      }
      $fieldValue = implode(', ', $newValue);
    }
    if ($pseudoKey === 'abbr' && $realField === 'state_province_id') {
      // hack alert - currently only supported for state.
      $fieldValue = (string) CRM_Core_PseudoConstant::stateProvinceAbbreviation($fieldValue);
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
    parent::__construct($this->getEntityName(), []);
  }

  /**
   * Check if the token processor is active.
   *
   * @param \Civi\Token\TokenProcessor $processor
   *
   * @return bool
   */
  public function checkActive(TokenProcessor $processor) {
    $isEntityEnabled = in_array($this->getApiEntityName(), array_keys(\Civi::service('action_object_provider')->getEntities()));
    if (!$isEntityEnabled) {
      return FALSE;
    }
    return ((!empty($processor->context['actionMapping'])
        // This makes the 'schema context compulsory - which feels accidental
      && $processor->context['actionMapping']->getEntityName()) || in_array($this->getEntityIDField(), $processor->context['schema']));
  }

  /**
   * Alter action schedule query.
   *
   * @param \Civi\ActionSchedule\Event\MailingQueryEvent $e
   */
  public function alterActionScheduleQuery(MailingQueryEvent $e): void {
    if ($e->mapping->getEntityTable($e->actionSchedule) !== $this->getExtendableTableName()) {
      return;
    }
    $e->query->select('e.id AS tokenContext_' . $this->getEntityIDField());
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
   * @todo remove this function & use the metadata that is loaded.
   *
   * @return string[]
   * @throws \CRM_Core_Exception
   */
  protected function getBasicTokens(): array {
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
  protected function getSkippedFields(): array {
    // tags is offered in 'case' & is one of the only fields that is
    // 'not a real field' offered up by case - seems like an oddity
    // we should skip at the top level for now.
    $fields = ['tags'];
    if (!CRM_Core_Component::isEnabled('CiviCampaign')) {
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

  protected function getEntityIDField(): string {
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

  protected function getCurrencyFieldName() {
    return [];
  }

  /**
   * Get the currency to use for formatting money.
   * @param $row
   *
   * @return string
   */
  protected function getCurrency($row): string {
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
   * @throws \CRM_Core_Exception
   */
  public function getPrefetchFields(TokenValueEvent $e): array {
    $allTokens = array_keys($this->getTokenMetadata());
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
  protected function getDependencies(): array {
    return [];
  }

  /**
   * Get the apiv4 style custom field name.
   *
   * @param int $id
   *
   * @return string
   *
   * @throws \CRM_Core_Exception
   */
  protected function getCustomFieldName(int $id): string {
    foreach ($this->getTokenMetadata() as $key => $field) {
      if (($field['custom_field_id'] ?? NULL) === $id) {
        return $key;
      }
    }
    throw new CRM_Core_Exception(
      "A custom field with the ID {$id} does not exist"
    );
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
    try {
      $value = $this->prefetch[$entityID][$this->getCustomFieldName($id)] ?? '';
      if ($value !== NULL) {
        return CRM_Core_BAO_CustomField::displayValue($value, $id);
      }
    }
    catch (CRM_Core_Exception $exception) {
      return NULL;
    }
  }

  /**
   * Get the metadata for the field.
   *
   * @param string $fieldName
   *
   * @return array
   */
  protected function getMetadataForField($fieldName): array {
    if (isset($this->getTokenMetadata()[$fieldName])) {
      return $this->getTokenMetadata()[$fieldName];
    }
    if (isset($this->getTokenMappingsForRelatedEntities()[$fieldName])) {
      return $this->getTokenMetadata()[$this->getTokenMappingsForRelatedEntities()[$fieldName]];
    }
    return $this->getTokenMetadata()[$this->getDeprecatedTokens()[$fieldName]] ?? [];
  }

  /**
   * Get token mappings for related entities - specifically the contact entity.
   *
   * This function exists to help manage the way contact tokens is structured
   * of an query-object style result set that needs to be mapped to apiv4.
   *
   * The end goal is likely to be to advertised tokens that better map to api
   * v4 and deprecate the existing ones but that is a long-term migration.
   *
   * @return array
   */
  protected function getTokenMappingsForRelatedEntities(): array {
    return [];
  }

  /**
   * Get array of deprecated tokens and the new token they map to.
   *
   * @return array
   */
  protected function getDeprecatedTokens(): array {
    return [];
  }

  /**
   * Get any overrides for token metadata.
   *
   * This is most obviously used for setting the audience, which
   * will affect widget-presence.
   *
   * @return \string[][]
   */
  protected function getTokenMetadataOverrides(): array {
    return [];
  }

  /**
   * To handle variable tokens, override this function and return the active tokens.
   *
   * @param \Civi\Token\Event\TokenValueEvent $e
   *
   * @return mixed
   */
  public function getActiveTokens(TokenValueEvent $e) {
    $messageTokens = $e->getTokenProcessor()->getMessageTokens();
    if (!isset($messageTokens[$this->entity])) {
      return FALSE;
    }
    return array_intersect($messageTokens[$this->entity], array_keys($this->getTokenMetadata()));
  }

  /**
   * Add the token to the metadata based on the field spec.
   *
   * @param array $tokensMetadata
   * @param array $field
   * @param array $exposedFields
   * @param string $prefix
   */
  protected function addFieldToTokenMetadata(array &$tokensMetadata, array $field, array $exposedFields, string $prefix = ''): void {
    $isExposed = in_array(str_replace($prefix . '.', '', $field['name']), $exposedFields, TRUE);
    if ($field['type'] !== 'Custom' && !$isExposed) {
      return;
    }
    $field['audience'] ??= 'user';
    if ($field['name'] === 'contact_id') {
      // Since {contact.id} is almost always present don't confuse users
      // by also adding (e.g {participant.contact_id)
      $field['audience'] = 'sysadmin';
    }
    if (!empty($this->getTokenMetadataOverrides()[$field['name']])) {
      $field = array_merge($field, $this->getTokenMetadataOverrides()[$field['name']]);
    }
    if ($field['type'] === 'Custom') {
      // Convert to apiv3 style for now. Later we can add v4 with
      // portable naming & support for labels/ dates etc so let's leave
      // the space open for that.
      // Not the existing QuickForm widget has handling for the custom field
      // format based on the title using this syntax.
      $parts = explode(': ', $field['label']);
      $field['title'] = "{$parts[1]} :: {$parts[0]}";
      $tokenName = 'custom_' . $field['custom_field_id'];
      $tokensMetadata[$tokenName] = $field;
      return;
    }
    $tokenName = $field['name'];
    // Presumably this line can not be reached unless isExposed = TRUE.
    if ($isExposed) {
      if (
        ($field['options'] || !empty($field['suffixes']))
        // At the time of writing currency didn't have a label option - this may have changed.
        && !in_array($field['name'], $this->getCurrencyFieldName(), TRUE)
      ) {
        $tokensMetadata[$tokenName . ':label'] = $tokensMetadata[$tokenName . ':name'] = $field;
        $fieldLabel = $field['input_attrs']['label'] ?? $field['label'];
        $tokensMetadata[$tokenName . ':label']['name'] = $field['name'] . ':label';
        $tokensMetadata[$tokenName . ':name']['name'] = $field['name'] . ':name';
        $tokensMetadata[$tokenName . ':name']['audience'] = 'sysadmin';
        $tokensMetadata[$tokenName . ':label']['title'] = $fieldLabel;
        $tokensMetadata[$tokenName . ':name']['title'] = ts('Machine name') . ': ' . $fieldLabel;
        $field['audience'] = 'sysadmin';
      }
      if ($field['data_type'] === 'Boolean') {
        $tokensMetadata[$tokenName . ':label'] = $field;
        $tokensMetadata[$tokenName . ':label']['name'] = $field['name'] . ':label';
        $field['audience'] = 'sysadmin';
      }
      $tokensMetadata[$tokenName] = $field;
    }
  }

  /**
   * Get a cache key appropriate to the current usage.
   *
   * @return string
   */
  protected function getCacheKey(): string {
    $cacheKey = __CLASS__ . 'token_metadata' . $this->getApiEntityName() . CRM_Core_Config::domainID() . '_' . CRM_Core_I18n::getLocale();
    if ($this->checkPermissions) {
      $cacheKey .= '__' . CRM_Core_Session::getLoggedInContactID();
    }
    return $cacheKey;
  }

  /**
   * Get metadata for tokens for a related entity joined by a field on the main entity.
   *
   * @param string $entity
   * @param string $joinField
   * @param array $tokenList
   * @param array $hiddenTokens
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getRelatedTokensForEntity(string $entity, string $joinField, array $tokenList, $hiddenTokens = []): array {
    if (!array_key_exists($entity, \Civi::service('action_object_provider')->getEntities())) {
      return [];
    }
    $apiParams = ['checkPermissions' => FALSE];
    if ($tokenList !== ['*']) {
      $apiParams['where'] = [['name', 'IN', $tokenList]];
    }
    $relatedTokens = civicrm_api4($entity, 'getFields', $apiParams);
    $tokens = [];
    foreach ($relatedTokens as $relatedToken) {
      $tokens[$joinField . '.' . $relatedToken['name']] = [
        'title' => $relatedToken['title'],
        'name' => $joinField . '.' . $relatedToken['name'],
        'type' => 'mapped',
        'data_type' => $relatedToken['data_type'],
        'input_type' => $relatedToken['input_type'],
        'audience' => in_array($relatedToken['name'], $hiddenTokens, TRUE) ? 'hidden' : 'user',
      ];
    }
    return $tokens;
  }

}
