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
   * @param bool $microformat
   *   If true indicates, the address to be built in hcard-microformat standard.
   * @param bool $mailing
   *   Should ALWAYS be false.
   * @param string[] $tokenFields
   *
   * @return string
   *   formatted address string
   *
   */
  public static function format(
    $fields,
    $format = NULL,
    $microformat = FALSE,
    $mailing = FALSE,
    $tokenFields = NULL
  ) {
    static $config = NULL;
    $mailing = FALSE;

    if (!$format) {
      $format = Civi::settings()->get('address_format');
    }

    if ($mailing) {
      $format = Civi::settings()->get('mailing_format');
    }

    $formatted = $format;

    $fullPostalCode = $fields['postal_code'] ?? NULL;
    if (!empty($fields['postal_code_suffix'])) {
      $fullPostalCode .= "-$fields[postal_code_suffix]";
    }

    // make sure that some of the fields do have values
    $emptyFields = [
      'supplemental_address_1',
      'supplemental_address_2',
      'supplemental_address_3',
      'state_province_name',
      'county',
    ];
    foreach ($emptyFields as $f) {
      if (!isset($fields[$f])) {
        $fields[$f] = NULL;
      }
    }

    //CRM-16876 Display countries in all caps when in mailing mode.
    if ($mailing && !empty($fields['country'])) {
      if (Civi::settings()->get('hideCountryMailingLabels')) {
        $domain = CRM_Core_BAO_Domain::getDomain();
        $domainLocation = CRM_Core_BAO_Location::getValues(['contact_id' => $domain->contact_id]);
        $domainAddress = $domainLocation['address'][1];
        $domainCountryId = $domainAddress['country_id'];
        if ($fields['country'] == CRM_Core_PseudoConstant::country($domainCountryId)) {
          $fields['country'] = NULL;
        }
        else {
          //Capitalization display on uppercase to contries with special characters
          $fields['country'] = mb_convert_case($fields['country'], MB_CASE_UPPER, "UTF-8");
        }
      }
      else {
        $fields['country'] = mb_convert_case($fields['country'], MB_CASE_UPPER, "UTF-8");
      }
    }

    if (!$microformat) {
      // replacements in case of Individual Name Format
      $replacements = [
        'contact.display_name' => $fields['display_name'] ?? NULL,
        'contact.individual_prefix' => $fields['individual_prefix'] ?? NULL,
        'contact.formal_title' => $fields['formal_title'] ?? NULL,
        'contact.first_name' => $fields['first_name'] ?? NULL,
        'contact.middle_name' => $fields['middle_name'] ?? NULL,
        'contact.last_name' => $fields['last_name'] ?? NULL,
        'contact.individual_suffix' => $fields['individual_suffix'] ?? NULL,
        'contact.address_name' => $fields['address_name'] ?? NULL,
        'contact.street_address' => $fields['street_address'] ?? NULL,
        'contact.supplemental_address_1' => $fields['supplemental_address_1'] ?? NULL,
        'contact.supplemental_address_2' => $fields['supplemental_address_2'] ?? NULL,
        'contact.supplemental_address_3' => $fields['supplemental_address_3'] ?? NULL,
        'contact.city' => $fields['city'] ?? NULL,
        'contact.state_province_name' => $fields['state_province_name'] ?? NULL,
        'contact.county' => $fields['county'] ?? NULL,
        'contact.state_province' => $fields['state_province'] ?? NULL,
        'contact.postal_code' => $fullPostalCode,
        'contact.country' => $fields['country'] ?? NULL,
        'contact.world_region' => $fields['world_region'] ?? NULL,
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
        'contact.preferred_mail_format' => $fields['preferred_mail_format'] ?? NULL,
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
    }
    else {
      $replacements = [
        'contact.address_name' => "<span class=\"address-name\">" . $fields['address_name'] . "</span>",
        'contact.street_address' => "<span class=\"street-address\">" . $fields['street_address'] . "</span>",
        'contact.supplemental_address_1' => "<span class=\"extended-address\">" . $fields['supplemental_address_1'] . "</span>",
        'contact.supplemental_address_2' => $fields['supplemental_address_2'],
        'contact.supplemental_address_3' => $fields['supplemental_address_3'],
        'contact.city' => "<span class=\"locality\">" . $fields['city'] . "</span>",
        'contact.state_province_name' => "<span class=\"region\">" . $fields['state_province_name'] . "</span>",
        'contact.county' => "<span class=\"region\">" . $fields['county'],
        'contact.state_province' => "<span class=\"region\">" . $fields['state_province'] . "</span>",
        'contact.postal_code' => "<span class=\"postal-code\">" . $fullPostalCode . "</span>",
        'contact.country' => "<span class=\"country-name\">" . $fields['country'] . "</span>",
        'contact.world_region' => "<span class=\"region\">" . $fields['world_region'] . "</span>",
      ];

      // erase all empty ones, so we dont get blank lines
      foreach (array_keys($replacements) as $key) {
        $exactKey = substr($key, 0, 8) == 'contact.' ? substr($key, 8) : $key;
        if ($key != 'contact.postal_code' &&
          CRM_Utils_Array::value($exactKey, $fields) == NULL
        ) {
          $replacements[$key] = '';
        }
      }
      if (empty($fullPostalCode)) {
        $replacements['contact.postal_code'] = '';
      }
    }

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

    // drop any {...} constructs from lines' ends
    if (!$microformat) {
      $formatted = "\n$formatted\n";
    }
    else {
      if ($microformat == 1) {
        $formatted = "\n<div class=\"location vcard\"><span class=\"adr\">\n$formatted</span></div>\n";
      }
      else {
        $formatted = "\n<div class=\"vcard\"><span class=\"adr\">$formatted</span></div>\n";
      }
    }

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

    if (!$microformat) {
      $finalFormatted = $formatted;
    }
    else {
      // remove \n from each line and only add at the end
      // this hack solves formatting issue, when we convert nl2br
      $lines = [];
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
    }
    return $finalFormatted;
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
   *   The address fields.
   * @param string $format
   *   The desired address format.
   * @param bool $microformat
   *   If true indicates, the address to be built in hcard-microformat standard.
   * @param bool $mailing
   *   If true indicates, the call has been made from mailing label.
   * @param null $tokenFields
   *
   * @return string
   *   formatted address string
   *
   */
  public static function formatMailingLabel(
    $fields,
    $format = NULL,
    $microformat = FALSE,
    $mailing = FALSE,
    $tokenFields = NULL
  ) {
    static $config = NULL;

    $format = Civi::settings()->get('mailing_format');

    $formatted = $format;

    $fullPostalCode = $fields['postal_code'] ?? NULL;
    if (!empty($fields['postal_code_suffix'])) {
      $fullPostalCode .= "-$fields[postal_code_suffix]";
    }

    // make sure that some of the fields do have values
    $emptyFields = [
      'supplemental_address_1',
      'supplemental_address_2',
      'supplemental_address_3',
      'state_province_name',
      'county',
    ];
    foreach ($emptyFields as $f) {
      if (!isset($fields[$f])) {
        $fields[$f] = NULL;
      }
    }

    //CRM-16876 Display countries in all caps when in mailing mode.
    if (!empty($fields['country'])) {
      if (Civi::settings()->get('hideCountryMailingLabels')) {
        $domain = CRM_Core_BAO_Domain::getDomain();
        $domainLocation = CRM_Core_BAO_Location::getValues(['contact_id' => $domain->contact_id]);
        $domainAddress = $domainLocation['address'][1];
        $domainCountryId = $domainAddress['country_id'];
        if ($fields['country'] == CRM_Core_PseudoConstant::country($domainCountryId)) {
          $fields['country'] = NULL;
        }
        else {
          //Capitalization display on uppercase to contries with special characters
          $fields['country'] = mb_convert_case($fields['country'], MB_CASE_UPPER, "UTF-8");
        }
      }
      else {
        $fields['country'] = mb_convert_case($fields['country'], MB_CASE_UPPER, "UTF-8");
      }
    }

    // replacements in case of Individual Name Format
    $replacements = [
      'contact.display_name' => $fields['display_name'] ?? NULL,
      'contact.individual_prefix' => $fields['individual_prefix'] ?? NULL,
      'contact.formal_title' => $fields['formal_title'] ?? NULL,
      'contact.first_name' => $fields['first_name'] ?? NULL,
      'contact.middle_name' => $fields['middle_name'] ?? NULL,
      'contact.last_name' => $fields['last_name'] ?? NULL,
      'contact.individual_suffix' => $fields['individual_suffix'] ?? NULL,
      'contact.address_name' => $fields['address_name'] ?? NULL,
      'contact.street_address' => $fields['street_address'] ?? NULL,
      'contact.supplemental_address_1' => $fields['supplemental_address_1'] ?? NULL,
      'contact.supplemental_address_2' => $fields['supplemental_address_2'] ?? NULL,
      'contact.supplemental_address_3' => $fields['supplemental_address_3'] ?? NULL,
      'contact.city' => $fields['city'] ?? NULL,
      'contact.state_province_name' => $fields['state_province_name'] ?? NULL,
      'contact.county' => $fields['county'] ?? NULL,
      'contact.state_province' => $fields['state_province'] ?? NULL,
      'contact.postal_code' => $fullPostalCode,
      'contact.country' => $fields['country'] ?? NULL,
      'contact.world_region' => $fields['world_region'] ?? NULL,
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
      'contact.preferred_mail_format' => $fields['preferred_mail_format'] ?? NULL,
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
    if (stristr($formatted, 'custom_')) {
      $customToken = array_keys($fields);
      foreach ($customToken as $value) {
        if (substr($value, 0, 7) == 'custom_') {
          $replacements["contact.{$value}"] = $fields["{$value}"];
        }
      }
    }

    // also sub all token fields
    if ($tokenFields) {
      foreach ($tokenFields as $token) {
        $replacements["{$token}"] = $fields["{$token}"] ?? NULL;
      }
    }

    // for every token, replace {fooTOKENbar} with fooVALUEbar if
    // the value is not empty, otherwise drop the whole {fooTOKENbar}
    foreach ($replacements as $token => $value) {
      if ($value && is_string($value) || is_numeric($value)) {
        $formatted = preg_replace("/{([^{}]*)\b{$token}\b([^{}]*)}/u", "\${1}{$value}\${2}", $formatted);
      }
      else {
        $formatted = preg_replace("/{[^{}]*\b{$token}\b[^{}]*}/u", '', $formatted);
      }
    }

    // drop any {...} constructs from lines' ends
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

    $finalFormatted = $formatted;
    return $finalFormatted;
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
   * @param int $billingLocationTypeID
   *
   * @return string
   */
  public static function getFormattedBillingAddressFieldsFromParameters($params, $billingLocationTypeID) {
    $addressParts = [
      "street_address" => "billing_street_address-{$billingLocationTypeID}",
      "city" => "billing_city-{$billingLocationTypeID}",
      "postal_code" => "billing_postal_code-{$billingLocationTypeID}",
      "state_province" => "state_province-{$billingLocationTypeID}",
      "country" => "country-{$billingLocationTypeID}",
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
      if (is_numeric($value) && ($name == 'state_province' || $name == 'country')) {
        if ($name == 'state_province') {
          $addressFields[$name] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($value);
          $addressFields[$name . '_name'] = CRM_Core_PseudoConstant::stateProvince($value);
        }
        if ($name == 'country') {
          $addressFields[$name] = CRM_Core_PseudoConstant::countryIsoCode($value);
        }
      }
      else {
        $addressFields[$name] = $value;
      }
    }
    return CRM_Utils_Address::format($addressFields);
  }

  /**
   * @return string
   */
  public static function getDefaultDistanceUnit() {
    $countryDefault = Civi::settings()->get('defaultContactCountry');
    // US, UK use miles. Everything else is Km
    return ($countryDefault == '1228' || $countryDefault == '1226') ? 'miles' : 'km';
  }

}
