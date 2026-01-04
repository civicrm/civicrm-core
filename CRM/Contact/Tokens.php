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

use Civi\Api4\Contact;
use Civi\Token\Event\TokenRegisterEvent;
use Civi\Token\Event\TokenValueEvent;
use Civi\Token\TokenProcessor;
use Civi\Token\TokenRow;

/**
 * Class CRM_Contact_Tokens
 *
 * Generate "contact.*" tokens.
 */
class CRM_Contact_Tokens extends CRM_Core_EntityTokens {

  /**
   * Get the entity name for api v4 calls.
   *
   * @return string
   */
  protected function getApiEntityName(): string {
    return 'Contact';
  }

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    return [
      'civi.token.eval' => [
        ['evaluateLegacyHookTokens', 500],
        ['onEvaluate'],
      ],
      'civi.token.list' => 'registerTokens',
    ];
  }

  /**
   * Register the declared tokens.
   *
   * @param \Civi\Token\Event\TokenRegisterEvent $e
   *   The registration event. Add new tokens using register().
   *
   * @throws \CRM_Core_Exception
   */
  public function registerTokens(TokenRegisterEvent $e): void {
    if (!$this->checkActive($e->getTokenProcessor())) {
      return;
    }
    foreach ($this->getTokenMetadata() as $tokenName => $field) {
      if ($field['audience'] === 'user') {
        $e->register([
          'entity' => $this->entity,
          // We advertise the new-style token names - but support legacy ones.
          'field' => $tokenName,
          'label' => $field['title'],
        ]);
      }
    }
    foreach ($this->getLegacyHookTokens() as $legacyHookToken) {
      $e->register([
        'entity' => $legacyHookToken['category'],
        'field' => $legacyHookToken['name'],
        'label' => $legacyHookToken['label'],
      ]);
    }
  }

  /**
   * Determine whether this token-handler should be used with
   * the given processor.
   *
   * To short-circuit token-processing in irrelevant contexts,
   * override this.
   *
   * @param \Civi\Token\TokenProcessor $processor
   * @return bool
   */
  public function checkActive(TokenProcessor $processor): bool {
    return in_array($this->getEntityIDField(), $processor->context['schema'], TRUE);
  }

  /**
   * @return string
   */
  protected function getEntityIDField(): string {
    return 'contactId';
  }

  /**
   * Get functions declared using the legacy hook.
   *
   * Note that these only extend the contact entity (
   * ie they are based on having a contact ID which they.
   * may or may not use, but they don't have other
   * entity IDs.)
   *
   * @return array
   */
  protected function getLegacyHookTokens(): array {
    $tokens = [];

    foreach ($this->getHookTokens() as $tokenValues) {
      foreach ($tokenValues as $key => $value) {
        if (is_numeric($key)) {
          // This appears to be an attempt to compensate for
          // inconsistencies described in https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_tokenValues/#example
          // in effect there is a suggestion that
          // Send an Email" and "CiviMail" send different parameters to the tokenValues hook
          // As of now 'send an email' renders hooks through this class.
          // CiviMail it depends on the use or otherwise of flexmailer.
          $key = $value;
        }
        if (preg_match('/^\{([^\}]+)\}$/', $value, $matches)) {
          $value = $matches[1];
        }
        $keyParts = explode('.', $key);
        $tokens[$key] = [
          'category' => $keyParts[0],
          'name' => $keyParts[1],
          'label' => $value,
        ];
      }
    }
    return $tokens;
  }

  /**
   * Get all tokens advertised as contact tokens.
   *
   * @return string[]
   */
  protected function getExposedFields(): array {
    return [
      'contact_type',
      'do_not_email',
      'do_not_phone',
      'do_not_mail',
      'do_not_sms',
      'do_not_trade',
      'is_opt_out',
      'external_identifier',
      'sort_name',
      'display_name',
      'nick_name',
      'image_URL',
      'preferred_communication_method',
      'preferred_language',
      'hash',
      'source',
      'first_name',
      'middle_name',
      'last_name',
      'prefix_id',
      'suffix_id',
      'formal_title',
      'communication_style_id',
      'job_title',
      'gender_id',
      'birth_date',
      'deceased_date',
      'employer_id',
      'is_deleted',
      'created_date',
      'modified_date',
      'addressee_display',
      'email_greeting_display',
      'postal_greeting_display',
      'id',
    ];
  }

  /**
   * Get the fields exposed from related entities.
   *
   * @return string[][]
   */
  protected function getRelatedEntityTokenMetadata(): array {
    return [
      'address' => [
        'location_type_id',
        'id',
        'street_address',
        'street_number',
        'street_number_suffix',
        'street_name',
        'street_unit',
        'supplemental_address_1',
        'supplemental_address_2',
        'supplemental_address_3',
        'city',
        'postal_code_suffix',
        'postal_code',
        'manual_geo_code',
        'geo_code_1',
        'geo_code_2',
        'name',
        'master_id',
        'county_id',
        'state_province_id',
        'country_id',
      ],
      'phone' => ['phone', 'phone_ext', 'phone_type_id'],
      'email' => ['email', 'signature_html', 'signature_text', 'on_hold'],
      'website' => ['url'],
      'openid' => ['openid'],
      'im' => ['name', 'provider_id'],
    ];
  }

  /**
   * Load token data from legacy hooks.
   *
   * While our goal is for people to move towards implementing
   * toke processors the old-style hooks can extend contact
   * token data.
   *
   * When that is happening we need to load the full contact record
   * to send to the hooks (not great for performance but the
   * fix is to move away from implementing legacy style hooks).
   *
   * Consistent with prior behaviour we only load the contact it it
   * is already loaded. In that scenario we also load any extra fields
   * that might be wanted for the contact tokens.
   *
   * @param \Civi\Token\Event\TokenValueEvent $e
   *
   * @throws \CRM_Core_Exception
   */
  public function evaluateLegacyHookTokens(TokenValueEvent $e): void {
    $messageTokens = $e->getTokenProcessor()->getMessageTokens();
    if (empty($messageTokens) || !array_intersect(array_keys($this->getHookTokens()), array_keys($messageTokens))) {
      return;
    }

    foreach ($e->getRows() as $row) {
      if (empty($row->context['contactId'])) {
        continue;
      }
      unset($swapLocale);
      $swapLocale = empty($row->context['locale']) ? NULL : \CRM_Utils_AutoClean::swapLocale($row->context['locale']);
      if (empty($row->context['contact'])) {
        // If we don't have the contact already load it now, getting full
        // details for hooks and anything the contact token resolution might
        // want later.
        $row->context['contact'] = $this->getContact($row->context['contactId'], $messageTokens['contact'] ?? [], TRUE);
      }
      $contactArray = [$row->context['contactId'] => $row->context['contact']];
      \CRM_Utils_Hook::tokenValues($contactArray,
        [$row->context['contactId']],
        empty($row->context['mailingJobId']) ? NULL : $row->context['mailingJobId'],
        $messageTokens,
        $row->context['controller'],
        TRUE
      );
      foreach ($this->getHookTokens() as $category => $hookToken) {
        if (!empty($messageTokens[$category])) {
          foreach (array_keys($hookToken) as $tokenName) {
            $tokenPartOnly = str_replace($category . '.', '', $tokenName);
            if (in_array($tokenPartOnly, $messageTokens[$category], TRUE)) {
              $row->format('text/html')
                ->tokens($category, str_replace($category . '.', '', $tokenName), $contactArray[$row->context['contactId']][$tokenName] ?? ($contactArray[$row->context['contactId']][$category . '.' . $tokenName] ?? ''));
            }
          }
        }
      }
    }
  }

  /**
   * Load token data.
   *
   * @param \Civi\Token\Event\TokenValueEvent $e
   *
   * @throws TokenException
   * @throws \CRM_Core_Exception
   */
  public function onEvaluate(TokenValueEvent $e) {
    $this->activeTokens = $e->getTokenProcessor()->getMessageTokens()['contact'] ?? [];
    if (empty($this->activeTokens)) {
      return;
    }

    foreach ($e->getRows() as $row) {
      if (empty($row->context['contactId']) && empty($row->context['contact'])) {
        continue;
      }

      unset($swapLocale);
      $swapLocale = empty($row->context['locale']) ? NULL : \CRM_Utils_AutoClean::swapLocale($row->context['locale']);

      if (empty($row->context['contact'])) {
        $row->context['contact'] = $this->getContact($row->context['contactId'], $this->activeTokens);
      }

      foreach ($this->activeTokens as $token) {
        if ($token === 'checksum') {
          $cs = \CRM_Contact_BAO_Contact_Utils::generateChecksum($row->context['contactId'],
            NULL,
            NULL,
            $row->context['hash'] ?? NULL
          );
          $row->format('text/html')
            ->tokens('contact', $token, "cs={$cs}");
        }
        elseif ($token === 'signature_html') {
          $row->format('text/html')->tokens('contact', $token, html_entity_decode($this->getFieldValue($row, $token)));
        }
        else {
          parent::evaluateToken($row, $this->entity, $token, $row->context['contact']);
        }
      }
    }
  }

  /**
   * Get the field value.
   *
   * @param \Civi\Token\TokenRow $row
   * @param string $field
   *
   * @return string|int
   * @throws \CRM_Core_Exception
   */
  protected function getFieldValue(TokenRow $row, string $field) {
    $entityName = 'contact';
    $contact = $row->context['contact'];
    if (isset($this->getDeprecatedTokens()[$field])) {
      // Check the non-deprecated location first, fall back to deprecated
      // this is important for the greetings because - they are weird in the query object.
      $possibilities = [$this->getDeprecatedTokens()[$field], $field];
    }
    else {
      $possibilities = [$field];
      if (in_array($field, $this->getDeprecatedTokens(), TRUE)) {
        $possibilities[] = array_search($field, $this->getDeprecatedTokens(), TRUE);
      }
    }

    foreach ($possibilities as $possibility) {
      if (isset($contact[$possibility])) {
        return $contact[$possibility];
      }
      if ($this->isPseudoField($possibility)) {
        // If we have a name or label field & already have the id loaded then we can
        // evaluate from that rather than query again.
        $split = explode(':', $possibility);
        if (array_key_exists($split[0], $contact)) {
          return $row->context['contact'][$possibility] = $this->getPseudoValue($split[0], $split[1], $contact[$split[0]]);
        }
      }
    }

    $contactID = $this->getFieldValue($row, 'id');
    if ($contactID) {
      $missingFields = array_diff_key(array_fill_keys($this->activeTokens, TRUE), $contact);
      $row->context['contact'] = array_merge($this->getContact($contactID, array_keys($missingFields)), $contact);
      if (isset($row->context[$entityName][$field])) {
        return $row->context[$entityName][$field];
      }
    }
    return '';
  }

  /**
   * Get the metadata for the available fields.
   *
   * @return array
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  protected function getTokenMetadata(): array {
    if (Civi::cache('metadata')->has($this->getCacheKey())) {
      return Civi::cache('metadata')->get($this->getCacheKey());
    }
    $this->fieldMetadata = (array) civicrm_api4('Contact', 'getfields', ['checkPermissions' => FALSE], 'name');
    $tokensMetadata = $this->getBespokeTokens();
    foreach ($this->fieldMetadata as $field) {
      $this->addFieldToTokenMetadata($tokensMetadata, $field, $this->getExposedFields());
    }

    foreach ($this->getRelatedEntityTokenMetadata() as $entity => $exposedFields) {
      $apiEntity = ($entity === 'openid') ? 'OpenID' : ucfirst($entity);
      if ($apiEntity === 'Im') {
        $apiEntity = 'IM';
      }
      $metadata = (array) civicrm_api4($apiEntity, 'getfields', ['checkPermissions' => FALSE], 'name');
      foreach ($metadata as $field) {
        if ($entity === 'website') {
          // It's not the primary - it's 'just one of them' - so the name is _first not _primary
          $field['name'] = 'website_first.' . $field['name'];
          $this->addFieldToTokenMetadata($tokensMetadata, $field, $exposedFields, 'website_first');
        }
        else {
          $field['name'] = $entity . '_primary.' . $field['name'];
          $this->addFieldToTokenMetadata($tokensMetadata, $field, $exposedFields, $entity . '_primary');
          $field['label'] .= ' (' . ts('Billing') . ')';
          // Set audience to sysadmin in case adding them to UI annoys people. If people ask to see this
          // in the UI we could set to 'user'.
          $field['audience'] = 'sysadmin';
          $field['name'] = str_replace('_primary.', '_billing.', $field['name']);
          $this->addFieldToTokenMetadata($tokensMetadata, $field, $exposedFields, $entity . '_billing');
        }
      }
    }
    // Manually add in the abbreviated state province as that maps to
    // what has traditionally been delivered.
    $tokensMetadata['address_primary.state_province_id:abbr'] = $tokensMetadata['address_primary.state_province_id:label'];
    $tokensMetadata['address_primary.state_province_id:abbr']['name'] = 'address_primary.state_province_id:abbr';
    $tokensMetadata['address_primary.state_province_id:abbr']['audience'] = 'user';
    $tokensMetadata['address_billing.state_province_id:abbr'] = $tokensMetadata['address_billing.state_province_id:label'];;
    $tokensMetadata['address_billing.state_province_id:abbr']['name'] = 'address_billing.state_province_id:abbr';

    // Hide the label for now because we are not sure if there are paths
    // where legacy token resolution is in play where this could not be resolved.
    $tokensMetadata['address_primary.state_province_id:label']['audience'] = 'sysadmin';
    // Hide this really obscure one. Just cos it annoys me.
    $tokensMetadata['address_primary.manual_geo_code:label']['audience'] = 'sysadmin';
    $tokensMetadata['openid_primary.openid']['audience'] = 'sysadmin';
    Civi::cache('metadata')->set($this->getCacheKey(), $tokensMetadata);
    return $tokensMetadata;
  }

  /**
   * Get the contact for the row.
   *
   * @param int $contactId
   * @param array $requiredFields
   * @param bool $getAll
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getContact(int $contactId, array $requiredFields, bool $getAll = FALSE): array {
    $returnProperties = [];
    if (in_array('checksum', $requiredFields, TRUE)) {
      $returnProperties[] = 'hash';
    }
    foreach ($this->getTokenMappingsForRelatedEntities() as $oldName => $newName) {
      if (in_array($oldName, $requiredFields, TRUE)) {
        $returnProperties[] = $newName;
      }
    }
    $joins = [];
    $customFields = [];
    $billingFields = [];
    foreach ($requiredFields as $field) {
      if (str_contains($field, '_billing.')) {
        // Make sure we have enough data to fall back to primary.
        $billingEntity = explode('.', $field)[0];
        $billingFields[$field] = $billingEntity;
        $extraFields = [$billingEntity . '.id', str_replace('_billing.', '_primary.', $field)];
        foreach ($extraFields as $extraField) {
          if (!in_array($extraField, $requiredFields, TRUE)) {
            $requiredFields[] = $extraField;
          }
        }
      }
    }
    foreach ($requiredFields as $field) {
      $fieldSpec = $this->getMetadataForField($field);
      $prefix = '';
      if (isset($fieldSpec['table_name']) && $fieldSpec['table_name'] !== 'civicrm_contact') {
        if ($fieldSpec['table_name'] === 'civicrm_website') {
          $tableAlias = 'website_first';
          $joins[$tableAlias] = $fieldSpec['entity'];
        }
        if ($fieldSpec['table_name'] === 'civicrm_openid') {
          // We could start to deprecate this one maybe..... I've made it un-advertised.
          $tableAlias = 'openid_primary';
          $joins[$tableAlias] = $fieldSpec['entity'];
        }
        if ($fieldSpec['type'] === 'Custom') {
          $customFields['custom_' . $fieldSpec['custom_field_id']] = $fieldSpec['name'];
        }
      }
      $returnProperties[] = $prefix . $this->getMetadataForField($field)['name'];
    }

    if ($getAll) {
      $returnProperties = array_merge(['*', 'custom.*'], $this->getDeprecatedTokens(), $this->getTokenMappingsForRelatedEntities());
    }

    $contactApi = Contact::get($this->checkPermissions)
      ->setSelect($returnProperties)->addWhere('id', '=', $contactId);
    foreach ($joins as $alias => $joinEntity) {
      $contactApi->addJoin($joinEntity . ' AS ' . $alias,
        'LEFT',
        ['id', '=', $alias . '.contact_id'],
        // For website the fact we use 'first' is the deduplication.
        ($joinEntity !== 'Website' ? [$alias . '.is_primary', '=', 1] : []));
    }
    $contact = $contactApi->execute()->first();
    if (!$contact) {
      // This is probably a test-only situation where tokens are retrieved for a
      // fake contact id - check `testReplaceGreetingTokens`
      return [];
    }
    foreach ($this->getEmptyBillingEntities($billingFields, $contact) as $billingEntityFields) {
      foreach ($billingEntityFields as $billingField) {
        $contact[$billingField] = $contact[str_replace('_billing.', '_primary.', $billingField)];
      }
    }

    foreach ($this->getDeprecatedTokens() as $apiv3Name => $fieldName) {
      // it would be set already with the right value for a greeting token
      // the query object returns the db value for email_greeting_display
      // and a numeric value for email_greeting if you put email_greeting
      // in the return properties.
      if (!isset($contact[$apiv3Name]) && array_key_exists($fieldName, $contact)) {
        $contact[$apiv3Name] = $contact[$fieldName];
      }
    }
    foreach ($this->getTokenMappingsForRelatedEntities() as $oldName => $newName) {
      if (isset($contact[$newName])) {
        $contact[$oldName] = $contact[$newName];
      }
    }

    //update value of custom field token
    foreach ($customFields as $apiv3Name => $fieldName) {
      $value = $contact[$fieldName];
      if ($this->getMetadataForField($apiv3Name)['data_type'] === 'Boolean') {
        $value = (int) $value;
      }
      $contact[$apiv3Name] = \CRM_Core_BAO_CustomField::displayValue($value, \CRM_Core_BAO_CustomField::getKeyID($apiv3Name));
    }

    return $contact;
  }

  /**
   * These tokens still work but we don't advertise them.
   *
   * We can remove from the following places
   * - scheduled reminders
   * - add to 'blocked' on pdf letter & email
   *
   * & then at some point start issuing warnings for them
   * but contact tokens are pretty central so it might be
   * a bit drawn out.
   *
   * @return string[]
   *   Keys are deprecated tokens and values are their replacements.
   */
  protected function getDeprecatedTokens(): array {
    return [
      'individual_prefix' => 'prefix_id:label',
      'individual_suffix' => 'suffix_id:label',
      'contact_type' => 'contact_type:label',
      'gender' => 'gender_id:label',
      'communication_style' => 'communication_style_id:label',
      'preferred_communication_method' => 'preferred_communication_method:label',
      'email_greeting' => 'email_greeting_display',
      'postal_greeting' => 'postal_greeting_display',
      'addressee' => 'addressee_display',
      'contact_id' => 'id',
      'contact_source' => 'source',
      'contact_is_deleted' => 'is_deleted',
      'current_employer_id' => 'employer_id',
    ];
  }

  /**
   * Get the tokens that are accessed by joining onto a related entity.
   *
   * This is an array of legacy style tokens mapped to the new style - so that
   * discontinued tokens still work (although they are no longer advertised).
   *
   * There are three types of legacy tokens
   * - apiv3 style - e.g {contact.email}
   * - ad hoc - hey cos it's CiviCRM
   * - 'wrong' apiv4 style - ie I thought we would do 'primary_address' but we did
   *   'address_primary' - these were added as the 'real token names' but not
   *   advertised & likely never adopted so handling them for a while is a
   *   conservative approach.
   *
   * The new type maps to the v4 api.
   *
   * @return string[]
   */
  protected function getTokenMappingsForRelatedEntities(): array {
    $legacyFieldMapping = [
      'on_hold' => 'email_primary.on_hold:label',
      'phone_type_id' => 'phone_primary.phone_type_id',
      'phone_type_id:label' => 'phone_primary.phone_type_id:label',
      'phone_type' => 'phone_primary.phone_type_id:label',
      'phone' => 'phone_primary.phone',
      'primary_phone.phone' => 'phone_primary.phone',
      'phone_ext' => 'phone_primary.phone_ext',
      'primary_phone.phone_ext' => 'phone_primary.phone_ext',
      'current_employer' => 'employer_id.display_name',
      'location_type_id' => 'address_primary.location_type_id',
      'location_type' => 'address_primary.location_type_id:label',
      'location_type_id:label' => 'address_primary.location_type_id:label',
      'street_address' => 'address_primary.street_address',
      'address_id' => 'address_primary.id',
      'address_name' => 'address_primary.name',
      'street_number' => 'address_primary.street_number',
      'street_number_suffix' => 'address_primary.street_number_suffix',
      'street_name' => 'address_primary.street_name',
      'street_unit' => 'address_primary.street_unit',
      'supplemental_address_1' => 'address_primary.supplemental_address_1',
      'supplemental_address_2' => 'address_primary.supplemental_address_2',
      'supplemental_address_3' => 'address_primary.supplemental_address_3',
      'city' => 'address_primary.city',
      'postal_code' => 'address_primary.postal_code',
      'postal_code_suffix' => 'address_primary.postal_code_suffix',
      'geo_code_1' => 'address_primary.geo_code_1',
      'geo_code_2' => 'address_primary.geo_code_2',
      'manual_geo_code' => 'address_primary.manual_geo_code',
      'master_id' => 'address_primary.master_id',
      'county' => 'address_primary.county_id:label',
      'county_id' => 'address_primary.county_id',
      'state_province' => 'address_primary.state_province_id:abbr',
      'state_province_id' => 'address_primary.state_province_id',
      'country' => 'address_primary.country_id:label',
      'country_id' => 'address_primary.country_id',
      'world_region' => 'address_primary.country_id.region_id:name',
      'email' => 'email_primary.email',
      'signature_text' => 'email_primary.signature_text',
      'signature_html' => 'email_primary.signature_html',
      'im' => 'im_primary.name',
      'im_provider' => 'im_primary.provider_id:label',
      'openid' => 'openid_primary.openid',
      'url' => 'website_first.url',
    ];
    foreach ($legacyFieldMapping as $fieldName) {
      // Add in our briefly-used 'primary_address' variants.
      // ie add 'primary_email.email' => 'email_primary.email'
      // so allow the former to be mapped to the latter.
      // We can deprecate these out later as they were likely never adopted.
      $oldPrimaryName = str_replace(
        ['email_primary', 'im_primary', 'phone_primary', 'address_primary', 'openid_primary', 'website_first'],
        ['primary_email', 'primary_im', 'primary_phone', 'primary_address', 'primary_openid', 'primary_website'],
        $fieldName);
      if ($oldPrimaryName !== $fieldName) {
        $legacyFieldMapping[$oldPrimaryName] = $fieldName;
      }
    }
    return $legacyFieldMapping;
  }

  /**
   * Get calculated or otherwise 'special', tokens.
   *
   * @return array[]
   */
  protected function getBespokeTokens(): array {
    return [
      'checksum' => [
        'title' => ts('Checksum'),
        'name' => 'checksum',
        'type' => 'calculated',
        'options' => NULL,
        'data_type' => 'String',
        'input_type' => NULL,
        'audience' => 'user',
      ],
      'employer_id.display_name' => [
        'title' => ts('Current Employer'),
        'name' => 'employer_id.display_name',
        'type' => 'mapped',
        'api_v3' => 'current_employer',
        'options' => NULL,
        'data_type' => 'String',
        'audience' => 'user',
      ],
      'address_primary.country_id.region_id:name' => [
        'title' => ts('World Region'),
        'name' => 'address_primary.country_id.region_id:name',
        'type' => 'mapped',
        'api_v3' => 'world_region',
        'options' => NULL,
        'data_type' => 'String',
        'input_type' => 'Text',
        'advertised_name' => 'world_region',
        'audience' => 'user',
      ],
      // this gets forced out if we specify individual fields
      'organization_name' => [
        'title' => ts('Organization name'),
        'name' => 'organization_name',
        'type' => 'Field',
        'options' => NULL,
        'data_type' => 'String',
        'input_type' => 'Text',
        'audience' => 'sysadmin',
      ],
      // this gets forced out if we specify individual fields
      'household_name' => [
        'title' => ts('Household name'),
        'name' => 'household_name',
        'type' => 'Field',
        'options' => NULL,
        'data_type' => 'String',
        'input_type' => 'Text',
        'audience' => 'sysadmin',
      ],
    ];
  }

  /**
   * Get the array of related billing entities that are empty.
   *
   * The billing tokens fall back to the primary address tokens as we cannot rely
   * on all contacts having an address with is_billing set and the code historically has
   * treated billing addresses as 'get the best billing address', despite the failure
   * to set the fields.
   *
   * Here we figure out the entities where swapping in the primary fields makes sense.
   * This is the case when there is no billing address at all, but we don't want to 'supplement'
   * a partial billing address with data from a possibly-completely-different primary address.
   *
   * @param array $billingFields
   * @param array $contact
   *
   * @return array
   */
  private function getEmptyBillingEntities(array $billingFields, array $contact): array {
    $billingEntitiesToReplaceWithPrimary = [];
    foreach ($billingFields as $billingField => $billingEntity) {
      // In most cases it is enough to check the 'id' is not present but it is possible
      // that a partial address is passed in in preview mode - in which case
      // we need to treat the entire address as 'usable'.
      if (empty($contact[$billingField]) && empty($contact[$billingEntity . '.id'])) {
        $billingEntitiesToReplaceWithPrimary[$billingEntity][] = $billingField;
      }
      else {
        unset($billingEntitiesToReplaceWithPrimary[$billingEntity]);
      }
    }
    return $billingEntitiesToReplaceWithPrimary;
  }

  /**
   * Get the tokens defined by the legacy hook.
   *
   * @return array
   */
  protected function getHookTokens(): array {
    if (isset(Civi::$statics[__CLASS__]['hook_tokens'])) {
      return Civi::$statics[__CLASS__]['hook_tokens'];
    }
    $tokens = [];
    \CRM_Utils_Hook::tokens($tokens, TRUE);
    Civi::$statics[__CLASS__]['hook_tokens'] = $tokens;
    return $tokens;
  }

}
