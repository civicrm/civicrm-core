<?php
namespace Civi\Token;

use Civi\Token\Event\TokenRegisterEvent;
use Civi\Token\Event\TokenRenderEvent;
use Civi\Token\Event\TokenValueEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class TokenCompatSubscriber
 * @package Civi\Token
 *
 * This class provides a compatibility layer for using CRM_Utils_Token
 * helpers within TokenProcessor.
 *
 * THIS IS NOT A GOOD EXAMPLE TO EMULATE. The class exists to two
 * bridge two different designs. CRM_Utils_Token has some
 * undesirable elements (like iterative token substitution).
 * However, if you're refactor CRM_Utils_Token or improve the
 * bridge, then it makes sense to update this class.
 */
class TokenCompatSubscriber implements EventSubscriberInterface {

  protected $entity = 'contact';

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    return [
      'civi.token.eval' => [
        ['setupSmartyAliases', 1000],
        ['evaluateLegacyHookTokens', 500],
        ['onEvaluate'],
      ],
      'civi.token.render' => 'onRender',
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
    foreach (array_merge($this->getContactTokens(), $this->getCustomFieldTokens()) as $name => $label) {
      $e->register([
        'entity' => $this->entity,
        'field' => $name,
        'label' => $label,
      ]);
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
  public function checkActive(\Civi\Token\TokenProcessor $processor) {
    return in_array($this->getEntityIDField(), $processor->context['schema'], TRUE);
  }

  /**
   * @return string
   */
  public function getEntityIDField(): string {
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
  public function getLegacyHookTokens(): array {
    $tokens = [];
    $hookTokens = [];
    \CRM_Utils_Hook::tokens($hookTokens);
    foreach ($hookTokens as $tokenValues) {
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
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getCustomFieldTokens(): array {
    $tokens = [];
    $customFields = \CRM_Core_BAO_CustomField::getFields(['Individual', 'Address', 'Contact']);
    foreach ($customFields as $customField) {
      $tokens['custom_' . $customField['id']] = $customField['label'] . " :: " . $customField['groupTitle'];
    }
    return $tokens;
  }

  /**
   * Get all tokens advertised as contact tokens.
   *
   * @return string[]
   */
  public function getContactTokens(): array {
    return [
      'contact_type' => 'Contact Type',
      'do_not_email' => 'Do Not Email',
      'do_not_phone' => 'Do Not Phone',
      'do_not_mail' => 'Do Not Mail',
      'do_not_sms' => 'Do Not Sms',
      'do_not_trade' => 'Do Not Trade',
      'is_opt_out' => 'No Bulk Emails (User Opt Out)',
      'external_identifier' => 'External Identifier',
      'sort_name' => 'Sort Name',
      'display_name' => 'Display Name',
      'nick_name' => 'Nickname',
      'image_URL' => 'Image Url',
      'preferred_communication_method' => 'Preferred Communication Method',
      'preferred_language' => 'Preferred Language',
      'preferred_mail_format' => 'Preferred Mail Format',
      'hash' => 'Contact Hash',
      'contact_source' => 'Contact Source',
      'first_name' => 'First Name',
      'middle_name' => 'Middle Name',
      'last_name' => 'Last Name',
      'individual_prefix' => 'Individual Prefix',
      'individual_suffix' => 'Individual Suffix',
      'formal_title' => 'Formal Title',
      'communication_style' => 'Communication Style',
      'job_title' => 'Job Title',
      'gender' => 'Gender ID',
      'birth_date' => 'Birth Date',
      'current_employer_id' => 'Current Employer ID',
      'contact_is_deleted' => 'Contact is in Trash',
      'created_date' => 'Created Date',
      'modified_date' => 'Modified Date',
      'addressee' => 'Addressee',
      'email_greeting' => 'Email Greeting',
      'postal_greeting' => 'Postal Greeting',
      'current_employer' => 'Current Employer',
      'location_type' => 'Location Type',
      'address_id' => 'Address ID',
      'street_address' => 'Street Address',
      'street_number' => 'Street Number',
      'street_number_suffix' => 'Street Number Suffix',
      'street_name' => 'Street Name',
      'street_unit' => 'Street Unit',
      'supplemental_address_1' => 'Supplemental Address 1',
      'supplemental_address_2' => 'Supplemental Address 2',
      'supplemental_address_3' => 'Supplemental Address 3',
      'city' => 'City',
      'postal_code_suffix' => 'Postal Code Suffix',
      'postal_code' => 'Postal Code',
      'geo_code_1' => 'Latitude',
      'geo_code_2' => 'Longitude',
      'manual_geo_code' => 'Is Manually Geocoded',
      'address_name' => 'Address Name',
      'master_id' => 'Master Address ID',
      'county' => 'County',
      'state_province' => 'State',
      'country' => 'Country',
      'phone' => 'Phone',
      'phone_ext' => 'Phone Extension',
      'phone_type_id' => 'Phone Type ID',
      'phone_type' => 'Phone Type',
      'email' => 'Email',
      'on_hold' => 'On Hold',
      'signature_text' => 'Signature Text',
      'signature_html' => 'Signature Html',
      'im_provider' => 'IM Provider',
      'im' => 'IM Screen Name',
      'openid' => 'OpenID',
      'world_region' => 'World Region',
      'url' => 'Website',
      'checksum' => 'Checksum',
      'contact_id' => 'Internal Contact ID',
    ];
  }

  /**
   * Interpret the variable `$context['smartyTokenAlias']` (e.g. `mySmartyField' => `tkn_entity.tkn_field`).
   *
   * We need to ensure that any tokens like `{tkn_entity.tkn_field}` are hydrated, so
   * we pretend that they are in use.
   *
   * @param \Civi\Token\Event\TokenValueEvent $e
   */
  public function setupSmartyAliases(TokenValueEvent $e) {
    $aliasedTokens = [];
    foreach ($e->getRows() as $row) {
      $aliasedTokens = array_unique(array_merge($aliasedTokens,
        array_values($row->context['smartyTokenAlias'] ?? [])));
    }

    $fakeMessage = implode('', array_map(function ($f) {
      return '{' . $f . '}';
    }, $aliasedTokens));

    $proc = $e->getTokenProcessor();
    $proc->addMessage('TokenCompatSubscriber.aliases', $fakeMessage, 'text/plain');
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
   * @throws TokenException
   */
  public function evaluateLegacyHookTokens(TokenValueEvent $e): void {
    $messageTokens = $e->getTokenProcessor()->getMessageTokens();
    $hookTokens = array_intersect(\CRM_Utils_Token::getTokenCategories(), array_keys($messageTokens));
    if (empty($hookTokens)) {
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
      foreach ($hookTokens as $hookToken) {
        foreach ($messageTokens[$hookToken] as $tokenName) {
          $row->format('text/html')->tokens($hookToken, $tokenName, $contactArray[$row->context['contactId']]["{$hookToken}.{$tokenName}"] ?? '');
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
      if (empty($row->context['contactId'])) {
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
        else {
          $row->format('text/html')
            ->tokens('contact', $token, $row->context['contact'][$token] ?? '');
        }
      }
    }
  }

  /**
   * Apply the various CRM_Utils_Token helpers.
   *
   * @param \Civi\Token\Event\TokenRenderEvent $e
   *
   * @throws \CRM_Core_Exception
   */
  public function onRender(TokenRenderEvent $e): void {
    $isHtml = ($e->message['format'] === 'text/html');
    $useSmarty = !empty($e->context['smarty']);

    if (!empty($e->context['contact'])) {
      // @todo - remove this - it simply removes the last unresolved tokens before
      // they break smarty.
      // historically it was only called when context['contact'] so that is
      // retained but it only works because it's almost always true.
      $remainingTokens = array_keys(\CRM_Utils_Token::getTokens($e->string));
      if (!empty($remainingTokens)) {
        $e->string = \CRM_Utils_Token::replaceHookTokens($e->string, $e->context['contact'], $remainingTokens);
      }
    }

    if ($useSmarty) {
      $smartyVars = [];
      foreach ($e->context['smartyTokenAlias'] ?? [] as $smartyName => $tokenName) {
        // Note: $e->row->tokens resolves event-based tokens (eg CRM_*_Tokens). But if the target token relies on the
        // above bits (replaceGreetingTokens=>replaceContactTokens=>replaceHookTokens) then this lookup isn't sufficient.
        $smartyVars[$smartyName] = \CRM_Utils_Array::pathGet($e->row->tokens, explode('.', $tokenName));
      }
      \CRM_Core_Smarty::singleton()->pushScope($smartyVars);
      try {
        $e->string = \CRM_Utils_String::parseOneOffStringThroughSmarty($e->string);
      }
      finally {
        \CRM_Core_Smarty::singleton()->popScope();
      }
    }
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
    $returnProperties = array_fill_keys($requiredFields, 1);
    $mappedFields = [
      'email_greeting' => 'email_greeting_display',
      'postal_greeting' => 'postal_greeting_display',
      'addressee' => 'addressee_display',
    ];
    if (!empty($returnProperties['checksum'])) {
      $returnProperties['hash'] = 1;
    }

    foreach ($mappedFields as $tokenName => $realName) {
      if (in_array($tokenName, $requiredFields, TRUE)) {
        $returnProperties[$realName] = 1;
      }
    }
    if ($getAll) {
      $returnProperties = array_merge($this->getAllContactReturnFields(), $returnProperties);
    }

    $params = [
      ['contact_id', '=', $contactId, 0, 0],
    ];
    // @todo - map the parameters to apiv4 instead....
    [$contact] = \CRM_Contact_BAO_Query::apiQuery($params, $returnProperties ?? NULL);
    //CRM-4524
    $contact = reset($contact);
    foreach ($mappedFields as $tokenName => $realName) {
      $contact[$tokenName] = $contact[$realName] ?? '';
    }

    //update value of custom field token
    foreach ($requiredFields as $token) {
      if (\CRM_Core_BAO_CustomField::getKeyID($token)) {
        $contact[$token] = \CRM_Core_BAO_CustomField::displayValue($contact[$token], \CRM_Core_BAO_CustomField::getKeyID($token));
      }
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

}
