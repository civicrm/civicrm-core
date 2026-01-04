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

use Civi\Api4\Country;
use Civi\Token\TokenProcessor;

/**
 * Address Utilities
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Address {

  /**
   * Format an address string from address fields and a format string.
   *
   * Format an address basing on the address fields provided.
   * Use Setting's address_format if there's no format specified.
   *
   * This function is also used to generate a contact's display_name and
   * sort_name.
   *
   * @param array $fields
   *   The address fields.
   * @param string $format
   *   The desired address format.
   * @param false $ignore
   *   Should always be false.
   * @param false $unused
   *   Should ALWAYS be false.
   * @param string[] $tokenFields
   *
   * @return string
   *   formatted address string
   *
   */
  public static function format(
    array $fields,
    $format = NULL,
    $ignore = FALSE,
    $unused = FALSE,
    $tokenFields = NULL
  ) {
    if ($format) {
      CRM_Core_Error::deprecatedWarning('passing format is deprecated, use the token processor directly');
    }
    if (!$format) {
      $format = Civi::settings()->get('address_format');
    }
    $formatted = $format;

    $fullPostalCode = $fields['postal_code'] ?? NULL;
    if (!empty($fields['postal_code_suffix'])) {
      $fullPostalCode .= '-' . $fields['postal_code_suffix'];
    }
    self::addLabelFields($fields, str_contains($formatted, '{country_id.world_region'));

    $replacements = [
      'contact.display_name' => $fields['display_name'] ?? NULL,
      'contact.formal_title' => $fields['formal_title'] ?? NULL,
      'contact.first_name' => $fields['first_name'] ?? NULL,
      'contact.middle_name' => $fields['middle_name'] ?? NULL,
      'contact.last_name' => $fields['last_name'] ?? NULL,
      'contact.individual_prefix' => $fields['prefix_id:label'] ?? ($fields['individual_prefix'] ?? NULL),
      'contact.prefix_id:label' => $fields['prefix_id:label'] ?? ($fields['individual_prefix'] ?? NULL),
      'contact.individual_suffix' => $fields['suffix_id:label'] ?? ($fields['individual_suffix'] ?? NULL),
      'contact.suffix_id:label' => $fields['suffix_id:label'] ?? ($fields['individual_suffix'] ?? NULL),
      'contact.address_name' => $fields['name'] ?? $fields['address_name'] ?? NULL,
      'contact.street_address' => $fields['street_address'] ?? NULL,
      'contact.supplemental_address_1' => $fields['supplemental_address_1'] ?? NULL,
      'contact.supplemental_address_2' => $fields['supplemental_address_2'] ?? NULL,
      'contact.supplemental_address_3' => $fields['supplemental_address_3'] ?? NULL,
      'contact.city' => $fields['city'] ?? NULL,
      'contact.state_province_name' => $fields['state_province_id:label'] ?? ($fields['state_province_name'] ?? NULL),
      'contact.county' => $fields['county_id:label'] ?? ($fields['county'] ?? NULL),
      'contact.state_province' => $fields['state_province_id:abbr'] ?? ($fields['state_province'] ?? NULL),
      'contact.postal_code' => $fullPostalCode,
      'contact.country' => $fields['country_id:label'] ?? ($fields['country'] ?? NULL),
      'contact.world_region' => $fields['country_id.world_region:label'] ?? $fields['world_region'] ?? NULL,
      'contact.geo_code_1' => $fields['geo_code_1'] ?? NULL,
      'contact.geo_code_2' => $fields['geo_code_2'] ?? NULL,
      'contact.current_employer' => $fields['current_employer'] ?? NULL,
      'contact.nick_name' => $fields['nick_name'] ?? NULL,
      'contact.email' => $fields['email'] ?? NULL,
      'contact.im' => $fields['im'] ?? NULL,
      'contact.do_not_email' => $fields['do_not_email'] ?? NULL,
      'contact.do_not_phone' => $fields['do_not_phone'] ?? NULL,
      'contact.do_not_mail' => $fields['do_not_mail'] ?? NULL,
      'contact.do_not_sms' => $fields['do_not_sms'] ?? NULL,
      'contact.do_not_trade' => $fields['do_not_trade'] ?? NULL,
      'contact.job_title' => $fields['job_title'] ?? NULL,
      'contact.birth_date' => $fields['birth_date'] ?? NULL,
      'contact.gender' => $fields['gender'] ?? NULL,
      'contact.is_opt_out' => $fields['is_opt_out'] ?? NULL,
      'contact.phone' => $fields['phone'] ?? NULL,
      'contact.home_URL' => $fields['home_URL'] ?? NULL,
      'contact.contact_source' => $fields['contact_source'] ?? NULL,
      'contact.external_identifier' => $fields['external_identifier'] ?? NULL,
      'contact.contact_id' => $fields['id'] ?? NULL,
      'contact.household_name' => $fields['household_name'] ?? NULL,
      'contact.organization_name' => $fields['organization_name'] ?? NULL,
      'contact.legal_name' => $fields['legal_name'] ?? NULL,
      'contact.preferred_communication_method' => $fields['preferred_communication_method'] ?? NULL,
      'contact.communication_style' => $fields['communication_style'] ?? NULL,
      'contact.addressee' => $fields['addressee_display'] ?? NULL,
      'contact.email_greeting' => $fields['email_greeting_display'] ?? NULL,
      'contact.postal_greeting' => $fields['postal_greeting_display'] ?? NULL,
    ];

    // replacements in case of Custom Token
    if (stristr(($formatted ?? ''), 'custom_')) {
      $customToken = array_keys($fields);
      foreach ($customToken as $value) {
        if (substr($value, 0, 7) == 'custom_') {
          $replacements["contact.{$value}"] = $fields["{$value}"];
        }
      }
    }

    // also sub all token fields
    if ($tokenFields) {
      CRM_Core_Error::deprecatedWarning('passing tokenFields is deprecated, use the token processor directly');
      foreach ($tokenFields as $token) {
        $replacements["{$token}"] = $fields["{$token}"] ?? NULL;
      }
    }

    // for every token, replace {fooTOKENbar} with fooVALUEbar if
    // the value is not empty, otherwise drop the whole {fooTOKENbar}
    foreach ($replacements as $token => $value) {
      if ($value && is_string($value) || is_numeric($value)) {
        $formatted = preg_replace("/{([^{}]*)\b{$token}\b([^{}]*)}/u", "\${1}{$value}\${2}", ($formatted ?? ''));
      }
      else {
        $formatted = preg_replace("/{[^{}]*\b{$token}\b[^{}]*}/u", '', ($formatted ?? ''));
      }
    }

    $formatted = "\n$formatted\n";

    $formatted = preg_replace('/\n{[^{}]*}/u', "\n", $formatted);
    $formatted = preg_replace('/{[^{}]*}\n/u', "\n", $formatted);

    // if there are any 'sibling' {...} constructs, replace them with the
    // contents of the first one; for example, when there's no state_province:
    // 1. {city}{, }{state_province}{ }{postal_code}
    // 2. San Francisco{, }{ }12345
    // 3. San Francisco, 12345
    $formatted = preg_replace('/{([^{}]*)}({[^{}]*})+/u', '\1', $formatted);

    // drop any remaining curly braces leaving their contents
    $formatted = str_replace(['{', '}'], '', $formatted);

    // drop any empty lines left after the replacements
    $formatted = preg_replace('/^[ \t]*[\r\n]+/m', '', $formatted);
    return $formatted;
  }

  /**
   * Format an address in the vcard format.
   *
   * @param array $address
   *   The address fields - this should be at minimum the fields that would be
   *   retrieved from an apiv4 Contact.get call. It could be augmented with other fields
   *   (e.g if country_id.world_region:label is already known).
   *
   * @return string
   *   formatted address string
   */
  public static function formatVCard($address): string {
    $formatted = Civi::settings()->get('address_format');

    $fullPostalCode = $address['postal_code'] ?? NULL;
    if (!empty($address['postal_code_suffix'])) {
      $fullPostalCode .= '-' . $address['postal_code_suffix'];
    }
    // @todo remove these - they are only here to allow this function to keep working while
    // the calling function is refactored.
    $legacyFieldConversions = [
      'address_name' => 'name',
      'state' => 'state_province_id:label',
      'state_province_name' => 'state_province_id:label',
      'state_province' => 'state_province_id:abbr',
      'county' => 'county_id:label',
      'country' => 'country_id:label',
      'world_region' => 'country_id.world_region_id:label',
    ];
    foreach ($legacyFieldConversions as $old => $new) {
      if (isset($address[$old])) {
        $address[$new] = $address[$old];
        unset($address[$old]);
      }
    }
    self::addLabelFields($address, str_contains($formatted, '{country_id.world_region'));

    // @todo - this could all be handled as tokens as a Token Processor tokens class.
    $replacements = [
      'contact.address_name' => empty($address['name']) ? '' : "<span class=\"address-name\">" . $address['name'] . "</span>",
      'contact.street_address' => empty($address['street_address']) ? '' : "<span class=\"street-address\">" . $address['street_address'] . "</span>",
      'contact.supplemental_address_1' => empty($address['supplemental_address_1']) ? '' : "<span class=\"extended-address\">" . $address['supplemental_address_1'] . "</span>",
      'contact.supplemental_address_2' => $address['supplemental_address_2'] ?? '',
      'contact.supplemental_address_3' => $address['supplemental_address_3'] ?? '',
      'contact.city' => empty($address['city']) ? '' : "<span class=\"locality\">" . $address['city'] . "</span>",
      'contact.state_province_name' => empty($address['state_province_id:label']) ? '' : "<span class=\"region\">" . $address['state_province_id:label'] . "</span>",
      'contact.county' => empty($address['county_id:label']) ? '' : "<span class=\"region\">" . $address['county_id:label'] . "</span>",
      'contact.state_province' => empty($address['state_province_id:abbr']) ? '' : "<span class=\"region\">" . $address['state_province_id:abbr'] . "</span>",
      'contact.postal_code' => !$fullPostalCode ? '' : "<span class=\"postal-code\">" . $fullPostalCode . "</span>",
      'contact.country' => empty($address['country_id:label']) ? '' : "<span class=\"country-name\">" . $address['country_id:label'] . "</span>",
      'contact.world_region' => empty($address['country_id.world_region_id:label']) ? '' : "<span class=\"region\">" . $address['country_id.world_region_id:label'] . "</span>",
    ];

    // replacements in case of Custom Token
    if (stristr(($formatted ?? ''), 'custom_')) {
      $customToken = array_keys($address);
      foreach ($customToken as $value) {
        if (substr($value, 0, 7) == 'custom_') {
          $replacements["contact.{$value}"] = $address["{$value}"];
        }
      }
    }

    // for every token, replace {fooTOKENbar} with fooVALUEbar if
    // the value is not empty, otherwise drop the whole {fooTOKENbar}
    foreach ($replacements as $token => $value) {
      if ($value && is_string($value) || is_numeric($value)) {
        $formatted = preg_replace("/{([^{}]*)\b{$token}\b([^{}]*)}/u", "\${1}{$value}\${2}", ($formatted ?? ''));
      }
      else {
        $formatted = preg_replace("/{[^{}]*\b{$token}\b[^{}]*}/u", '', ($formatted ?? ''));
      }
    }

    $formatted = "\n<div class=\"location vcard\"><span class=\"adr\">\n$formatted</span></div>\n";

    $formatted = preg_replace('/\n{[^{}]*}/u', "\n", $formatted);
    $formatted = preg_replace('/{[^{}]*}\n/u', "\n", $formatted);

    // if there are any 'sibling' {...} constructs, replace them with the
    // contents of the first one; for example, when there's no state_province:
    // 1. {city}{, }{state_province}{ }{postal_code}
    // 2. San Francisco{, }{ }12345
    // 3. San Francisco, 12345
    $formatted = preg_replace('/{([^{}]*)}({[^{}]*})+/u', '\1', $formatted);

    // drop any remaining curly braces leaving their contents
    $formatted = str_replace(['{', '}'], '', $formatted);

    // drop any empty lines left after the replacements
    $formatted = preg_replace('/^[ \t]*[\r\n]+/m', '', $formatted);
    // remove \n from each line and only add at the end
    // this hack solves formatting issue, when we convert nl2br
    $count = 1;
    $finalFormatted = NULL;
    $formattedArray = explode("\n", $formatted);
    $formattedArray = array_filter($formattedArray);

    foreach ($formattedArray as $line) {
      $line = trim($line);
      if ($line) {
        if ($count > 1 && $count < count($formattedArray)) {
          $line = "$line\n";
        }
        $finalFormatted .= $line;
        $count++;
      }
    }
    return $finalFormatted ?? '';
  }

  /**
   * Format a mailing label.
   *
   * @internal
   *
   * This function is split off from format() which is doing too much for cleanup.
   *
   * It is ONLY called from 2 label task classes and MUST NOT be called from
   * anywhere else as it is changing.
   *
   * @param array $fields
   *
   * @return string
   *   formatted address string
   *
   */
  public static function formatMailingLabel($fields) {
    if (!empty($fields['postal_code_suffix']) && !empty($fields['postal_code'])) {
      $fields['postal_code'] .= "-$fields[postal_code_suffix]";
    }

    //CRM-16876 Display countries in all caps when in mailing mode.
    if ($fields['country_id:label']) {
      if ($fields['country_id:label'] === self::getDomainCountryToHide()) {
        $fields['country'] = NULL;
      }
      else {
        $fields['country'] = mb_convert_case($fields['country_id:label'], MB_CASE_UPPER, 'UTF-8');
      }
    }

    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'class' => __CLASS__,
      'schema' => ['contactId'],
    ]);
    $tokenProcessor->addRow(['contactId' => $fields['id'] ?? 0, 'contact' => $fields]);
    $tokenProcessor->addMessage('label', Civi::settings()->get('mailing_format'), 'text/plain');
    $tokenProcessor->evaluate();
    $text = $tokenProcessor->getRow(0)->render('label');

    // drop any empty lines left after the replacements
    return preg_replace('/^[ \t]*[\r\n]+/m', '', $text);
  }

  /**
   * @param string $format
   *
   * @return array
   */
  public static function sequence($format) {
    // also compute and store the address sequence
    $addressSequence = [
      'address_name',
      'street_address',
      'supplemental_address_1',
      'supplemental_address_2',
      'supplemental_address_3',
      'city',
      'county',
      'state_province',
      'postal_code',
      'country',
    ];

    // get the field sequence from the format
    $newSequence = [];
    foreach ($addressSequence as $field) {
      if (substr_count($format, $field)) {
        $newSequence[strpos($format, $field)] = $field;
      }
    }
    ksort($newSequence);

    // add the addressSequence fields that are missing in the addressFormat
    // to the end of the list, so that (for example) if state_province is not
    // specified in the addressFormat it's still in the address-editing form
    $newSequence = array_merge($newSequence, $addressSequence);
    $newSequence = array_unique($newSequence);
    return $newSequence;
  }

  /**
   * Extract the billing fields from the form submission and format them for display.
   *
   * @param array $params
   *
   * @return string
   */
  public static function getFormattedBillingAddressFieldsFromParameters(array $params): string {
    $billingLocationTypeID = CRM_Core_BAO_LocationType::getBilling();
    $addressParts = [
      'street_address' => "billing_street_address-{$billingLocationTypeID}",
      'city' => "billing_city-{$billingLocationTypeID}",
      'postal_code' => "billing_postal_code-{$billingLocationTypeID}",
      'state_province' => "state_province-{$billingLocationTypeID}",
      'country' => "country-{$billingLocationTypeID}",
    ];

    $addressFields = [];
    foreach ($addressParts as $name => $field) {
      $value = $params[$field] ?? NULL;
      $alternateName = 'billing_' . $name . '_id-' . $billingLocationTypeID;
      $alternate2 = 'billing_' . $name . '-' . $billingLocationTypeID;
      if (isset($params[$alternate2]) && !isset($params[$alternateName])) {
        $alternateName = $alternate2;
      }
      //Include values which prepend 'billing_' to country and state_province.
      if (!empty($params[$alternateName])) {
        if (empty($value) || !is_numeric($value)) {
          $value = $params[$alternateName];
        }
      }
      if (is_numeric($value) && ($name === 'state_province' || $name === 'country')) {
        if ($name === 'state_province') {
          $addressFields[$name] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($value);
          $addressFields[$name . '_name'] = CRM_Core_PseudoConstant::stateProvince($value);
        }
        if ($name === 'country') {
          $addressFields[$name] = CRM_Core_PseudoConstant::countryIsoCode($value);
        }
      }
      else {
        $addressFields[$name] = $value;
      }
    }
    return (string) CRM_Utils_Address::format($addressFields);
  }

  /**
   * @return string
   */
  public static function getDefaultDistanceUnit() {
    $countryDefault = Civi::settings()->get('defaultContactCountry');
    // US, UK use miles. Everything else is Km
    return ($countryDefault == '1228' || $countryDefault == '1226') ? 'miles' : 'km';
  }

  /**
   * @param array $address
   * @param bool $isResolveWorldRegion
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  private static function addLabelFields(array &$address, bool $isResolveWorldRegion): void {
    if (isset($address['country_id'])) {
      $address['country_id:label'] = CRM_Core_PseudoConstant::getLabel('CRM_Core_BAO_Address', 'country_id', $address['country_id']);
      if ($isResolveWorldRegion && !isset($address['country_id.world_region_id:label'])) {
        $address['country_id.world_region_id:label'] = Country::get(FALSE)
          ->addWhere('id', '=', $address['country_id'])
          ->addSelect('region_id:label')
          ->execute()->first()['region_id:label'] ?? '';
      }
    }
    if (isset($address['county_id'])) {
      $address['county_id:label'] = CRM_Core_PseudoConstant::getLabel('CRM_Core_BAO_Address', 'county_id', $address['county_id']);
    }
    if (isset($address['state_province_id'])) {
      $address['state_province_id:label'] = CRM_Core_PseudoConstant::getLabel('CRM_Core_BAO_Address', 'state_province_id', $address['state_province_id']);
      $address['state_province_id:abbr'] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($address['state_province_id']);
    }
  }

  /**
   * Check if we should remove the domain contact's country from mailing labels and return it or null if not
   *
   * @return string | null
   * @throws \CRM_Core_Exception
   */
  private static function getDomainCountryToHide() {
    if (Civi::settings()->get('hideCountryMailingLabels')) {
      $domain = CRM_Core_BAO_Domain::getDomain();
      $domainLocation = CRM_Core_BAO_Location::getValues(['contact_id' => $domain->contact_id]);
      $domainAddress = $domainLocation['address'][1];
      $domainCountryId = $domainAddress['country_id'];
      return CRM_Core_PseudoConstant::country($domainCountryId);
    }
    else {
      return NULL;
    }
  }

}
