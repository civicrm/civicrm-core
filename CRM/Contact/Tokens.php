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
   * Tokens defined by the legacy hook.
   *
   * @var array
   */
  protected $hookTokens;

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
    $relatedTokens = array_flip($this->getTokenMappingsForRelatedEntities());
    foreach ($this->getTokenMetadata() as $tokenName => $field) {
      if ($field['audience'] === 'user') {
        $e->register([
          'entity' => $this->entity,
          // Preserve legacy token names. It generally feels like
          // it would be good to switch to the more specific token names
          // but other code paths are still in use which can't handle them.
          'field' => $relatedTokens[$tokenName] ?? $tokenName,
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
      'preferred_mail_format',
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
   * @return \string[][]
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
    if (!array_intersect(array_keys($this->getHookTokens()), array_keys($messageTokens))) {
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
        $row->context['controller']
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
    $messageTokens = $e->getTokenProcessor()->getMessageTokens()['contact'] ?? [];
    if (empty($messageTokens)) {
      return;
    }

    foreach ($e->getRows() as $row) {
      if (empty($row->context['contactId']) && empty($row->context['contact'])) {
        continue;
      }

      unset($swapLocale);
      $swapLocale = empty($row->context['locale']) ? NULL : \CRM_Utils_AutoClean::swapLocale($row->context['locale']);

      if (empty($row->context['contact'])) {
        $row->context['contact'] = $this->getContact($row->context['contactId'], $messageTokens);
      }

      foreach ($messageTokens as $token) {
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
          $row->format('text/html')->tokens('contact', $token, html_entity_decode($row->context['contact'][$token]));
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
   * @return string|int
   */
  protected function getFieldValue(TokenRow $row, string $field) {
    $entityName = 'contact';
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
      if (isset($row->context[$entityName][$possibility])) {
        return $row->context[$entityName][$possibility];
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
    if ($this->tokensMetadata) {
      return $this->tokensMetadata;
    }
    if (Civi::cache('metadata')->has($this->getCacheKey())) {
      return Civi::cache('metadata')->get($this->getCacheKey());
    }
    $this->fieldMetadata = (array) civicrm_api4('Contact', 'getfields', ['checkPermissions' => FALSE], 'name');
    $this->tokensMetadata = $this->getBespokeTokens();
    foreach ($this->fieldMetadata as $field) {
      $this->addFieldToTokenMetadata($field, $this->getExposedFields());
    }

    foreach ($this->getRelatedEntityTokenMetadata() as $entity => $exposedFields) {
      $apiEntity = ($entity === 'openid') ? 'OpenID' : ucfirst($entity);
      $metadata = (array) civicrm_api4($apiEntity, 'getfields', ['checkPermissions' => FALSE], 'name');
      foreach ($metadata as $field) {
        $this->addFieldToTokenMetadata($field, $exposedFields, 'primary_' . $entity);
      }
    }
    // Manually add in the abbreviated state province as that maps to
    // what has traditionally been delivered.
    $this->tokensMetadata['primary_address.state_province_id:abbr'] = $this->tokensMetadata['primary_address.state_province_id:label'];
    $this->tokensMetadata['primary_address.state_province_id:abbr']['name'] = 'state_province_id:abbr';
    $this->tokensMetadata['primary_address.state_province_id:abbr']['audience'] = 'user';
    // Hide the label for now because we are not sure if there are paths
    // where legacy token resolution is in play where this could not be resolved.
    $this->tokensMetadata['primary_address.state_province_id:label']['audience'] = 'sysadmin';
    // Hide this really obscure one. Just cos it annoys me.
    $this->tokensMetadata['primary_address.manual_geo_code:label']['audience'] = 'sysadmin';
    Civi::cache('metadata')->set($this->getCacheKey(), $this->tokensMetadata);
    return $this->tokensMetadata;
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
    foreach ($requiredFields as $field) {
      $fieldSpec = $this->getMetadataForField($field);
      $prefix = '';
      if (isset($fieldSpec['table_name']) && $fieldSpec['table_name'] !== 'civicrm_contact') {
        $tableAlias = str_replace('civicrm_', 'primary_', $fieldSpec['table_name']);
        $joins[$tableAlias] = $fieldSpec['entity'];

        $prefix = $tableAlias . '.';
      }
      if ($fieldSpec['type'] === 'Custom') {
        $customFields['custom_' . $fieldSpec['custom_field_id']] = $fieldSpec['name'];
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
   * Get the array of the return fields from 'get all'.
   *
   * This is the list from the BAO_Query object but copied
   * here to be 'frozen in time'. The goal is to map to apiv4
   * and stop using the legacy call to load the contact.
   *
   * @return array
   */
  protected function getAllContactReturnFields(): array {
    return [
      'image_URL' => 1,
      'legal_identifier' => 1,
      'external_identifier' => 1,
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
      'display_name' => 1,
      'preferred_mail_format' => 1,
      'nick_name' => 1,
      'first_name' => 1,
      'middle_name' => 1,
      'last_name' => 1,
      'prefix_id' => 1,
      'suffix_id' => 1,
      'formal_title' => 1,
      'communication_style_id' => 1,
      'birth_date' => 1,
      'gender_id' => 1,
      'street_address' => 1,
      'supplemental_address_1' => 1,
      'supplemental_address_2' => 1,
      'supplemental_address_3' => 1,
      'city' => 1,
      'postal_code' => 1,
      'postal_code_suffix' => 1,
      'state_province' => 1,
      'country' => 1,
      'world_region' => 1,
      'geo_code_1' => 1,
      'geo_code_2' => 1,
      'email' => 1,
      'on_hold' => 1,
      'phone' => 1,
      'im' => 1,
      'household_name' => 1,
      'organization_name' => 1,
      'deceased_date' => 1,
      'is_deceased' => 1,
      'job_title' => 1,
      'legal_name' => 1,
      'sic_code' => 1,
      'current_employer' => 1,
      'do_not_email' => 1,
      'do_not_mail' => 1,
      'do_not_sms' => 1,
      'do_not_phone' => 1,
      'do_not_trade' => 1,
      'is_opt_out' => 1,
      'contact_is_deleted' => 1,
      'preferred_communication_method' => 1,
      'preferred_language' => 1,
    ];
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
   * Note the original thinking was to migrate to advertising the tokens
   * that more accurately reflect the schema & also add support for e.g
   * billing_address.street_address - which would be hugely useful for workflow
   * message templates.
   *
   * However that feels like a bridge too far for this round
   * since we haven't quite hit the goal of all token processing going through
   * the token processor & we risk advertising tokens that don't work if we get
   * ahead of that process.
   *
   * @return string[]
   */
  protected function getTokenMappingsForRelatedEntities(): array {
    return [
      'on_hold' => 'primary_email.on_hold',
      'on_hold:label' => 'primary_email.on_hold:label',
      'phone_type_id' => 'primary_phone.phone_type_id',
      'phone_type_id:label' => 'primary_phone.phone_type_id:label',
      'current_employer' => 'employer_id.display_name',
      'location_type_id' => 'primary_address.location_type_id',
      'location_type' => 'primary_address.location_type_id:label',
      'location_type_id:label' => 'primary_address.location_type_id:label',
      'street_address' => 'primary_address.street_address',
      'address_id' => 'primary_address.id',
      'address_name' => 'primary_address.name',
      'street_number' => 'primary_address.street_number',
      'street_number_suffix' => 'primary_address.street_number_suffix',
      'street_name' => 'primary_address.street_name',
      'street_unit' => 'primary_address.street_unit',
      'supplemental_address_1' => 'primary_address.supplemental_address_1',
      'supplemental_address_2' => 'primary_address.supplemental_address_2',
      'supplemental_address_3' => 'primary_address.supplemental_address_3',
      'city' => 'primary_address.city',
      'postal_code' => 'primary_address.postal_code',
      'postal_code_suffix' => 'primary_address.postal_code_suffix',
      'geo_code_1' => 'primary_address.geo_code_1',
      'geo_code_2' => 'primary_address.geo_code_2',
      'manual_geo_code' => 'primary_address.manual_geo_code',
      'master_id' => 'primary_address.master_id',
      'county' => 'primary_address.county_id:label',
      'county_id' => 'primary_address.county_id',
      'state_province' => 'primary_address.state_province_id:abbr',
      'state_province_id' => 'primary_address.state_province_id',
      'country' => 'primary_address.country_id:label',
      'country_id' => 'primary_address.country_id',
      'world_region' => 'primary_address.country_id.region_id:name',
      'phone_type' => 'primary_phone.phone_type_id:label',
      'phone' => 'primary_phone.phone',
      'phone_ext' => 'primary_phone.phone_ext',
      'email' => 'primary_email.email',
      'signature_text' => 'primary_email.signature_text',
      'signature_html' => 'primary_email.signature_html',
      'im' => 'primary_im.name',
      'im_provider' => 'primary_im.provider_id',
      'provider_id:label' => 'primary_im.provider_id:label',
      'provider_id' => 'primary_im.provider_id',
      'openid' => 'primary_openid.openid',
      'url' => 'primary_website.url',
    ];
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
      'primary_address.country_id.region_id:name' => [
        'title' => ts('World Region'),
        'name' => 'country_id.region_id.name',
        'type' => 'mapped',
        'api_v3' => 'world_region',
        'options' => NULL,
        'data_type' => 'String',
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
        'audience' => 'sysadmin',
      ],
    ];
  }

  /**
   * Get the tokens defined by the legacy hook.
   *
   * @return array
   */
  protected function getHookTokens(): array {
    if ($this->hookTokens === NULL) {
      if (isset(Civi::$statics[__CLASS__]['hook_tokens'])) {
        $this->hookTokens = Civi::$statics[__CLASS__]['hook_tokens'];
      }
      else {
        $this->hookTokens = [];
        \CRM_Utils_Hook::tokens($this->hookTokens);
        Civi::$statics[__CLASS__]['hook_tokens'] = $this->hookTokens;
      }
    }
    return $this->hookTokens;
  }

}
