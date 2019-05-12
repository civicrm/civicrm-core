<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Address Utilities
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
   *   If true indicates, the call has been made from mailing label.
   * @param null $tokenFields
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

    if (!$format) {
      $format = Civi::settings()->get('address_format');
    }

    if ($mailing) {
      $format = Civi::settings()->get('mailing_format');
    }

    $formatted = $format;

    $fullPostalCode = CRM_Utils_Array::value('postal_code', $fields);
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
        'contact.display_name' => CRM_Utils_Array::value('display_name', $fields),
        'contact.individual_prefix' => CRM_Utils_Array::value('individual_prefix', $fields),
        'contact.formal_title' => CRM_Utils_Array::value('formal_title', $fields),
        'contact.first_name' => CRM_Utils_Array::value('first_name', $fields),
        'contact.middle_name' => CRM_Utils_Array::value('middle_name', $fields),
        'contact.last_name' => CRM_Utils_Array::value('last_name', $fields),
        'contact.individual_suffix' => CRM_Utils_Array::value('individual_suffix', $fields),
        'contact.address_name' => CRM_Utils_Array::value('address_name', $fields),
        'contact.street_address' => CRM_Utils_Array::value('street_address', $fields),
        'contact.supplemental_address_1' => CRM_Utils_Array::value('supplemental_address_1', $fields),
        'contact.supplemental_address_2' => CRM_Utils_Array::value('supplemental_address_2', $fields),
        'contact.supplemental_address_3' => CRM_Utils_Array::value('supplemental_address_3', $fields),
        'contact.city' => CRM_Utils_Array::value('city', $fields),
        'contact.state_province_name' => CRM_Utils_Array::value('state_province_name', $fields),
        'contact.county' => CRM_Utils_Array::value('county', $fields),
        'contact.state_province' => CRM_Utils_Array::value('state_province', $fields),
        'contact.postal_code' => $fullPostalCode,
        'contact.country' => CRM_Utils_Array::value('country', $fields),
        'contact.world_region' => CRM_Utils_Array::value('world_region', $fields),
        'contact.geo_code_1' => CRM_Utils_Array::value('geo_code_1', $fields),
        'contact.geo_code_2' => CRM_Utils_Array::value('geo_code_2', $fields),
        'contact.current_employer' => CRM_Utils_Array::value('current_employer', $fields),
        'contact.nick_name' => CRM_Utils_Array::value('nick_name', $fields),
        'contact.email' => CRM_Utils_Array::value('email', $fields),
        'contact.im' => CRM_Utils_Array::value('im', $fields),
        'contact.do_not_email' => CRM_Utils_Array::value('do_not_email', $fields),
        'contact.do_not_phone' => CRM_Utils_Array::value('do_not_phone', $fields),
        'contact.do_not_mail' => CRM_Utils_Array::value('do_not_mail', $fields),
        'contact.do_not_sms' => CRM_Utils_Array::value('do_not_sms', $fields),
        'contact.do_not_trade' => CRM_Utils_Array::value('do_not_trade', $fields),
        'contact.job_title' => CRM_Utils_Array::value('job_title', $fields),
        'contact.birth_date' => CRM_Utils_Array::value('birth_date', $fields),
        'contact.gender' => CRM_Utils_Array::value('gender', $fields),
        'contact.is_opt_out' => CRM_Utils_Array::value('is_opt_out', $fields),
        'contact.preferred_mail_format' => CRM_Utils_Array::value('preferred_mail_format', $fields),
        'contact.phone' => CRM_Utils_Array::value('phone', $fields),
        'contact.home_URL' => CRM_Utils_Array::value('home_URL', $fields),
        'contact.contact_source' => CRM_Utils_Array::value('contact_source', $fields),
        'contact.external_identifier' => CRM_Utils_Array::value('external_identifier', $fields),
        'contact.contact_id' => CRM_Utils_Array::value('id', $fields),
        'contact.household_name' => CRM_Utils_Array::value('display_name', $fields),
        'contact.organization_name' => CRM_Utils_Array::value('display_name', $fields),
        'contact.legal_name' => CRM_Utils_Array::value('legal_name', $fields),
        'contact.preferred_communication_method' => CRM_Utils_Array::value('preferred_communication_method', $fields),
        'contact.communication_style' => CRM_Utils_Array::value('communication_style', $fields),
        'contact.addressee' => CRM_Utils_Array::value('addressee_display', $fields),
        'contact.email_greeting' => CRM_Utils_Array::value('email_greeting_display', $fields),
        'contact.postal_greeting' => CRM_Utils_Array::value('postal_greeting_display', $fields),
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
        $replacements["{$token}"] = CRM_Utils_Array::value("{$token}", $fields);
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
   * @param $format
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
      $value = CRM_Utils_Array::value($field, $params);
      $alternateName = 'billing_' . $name . '_id-' . $billingLocationTypeID;
      $alternate2 = 'billing_' . $name . '-' . $billingLocationTypeID;
      if (isset($params[$alternate2]) && !isset($params[$alternateName])) {
        $alternateName = $alternate2;
      }
      //Include values which prepend 'billing_' to country and state_province.
      if (CRM_Utils_Array::value($alternateName, $params)) {
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

}
