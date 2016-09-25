<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * One place to store frequently used values in Select Elements. Note that
 * some of the below elements will be dynamic, so we'll probably have a
 * smart caching scheme on a per domain basis
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */
class CRM_Core_SelectValues {

  /**
   * Yes/No options
   *
   * @return array
   */
  public static function boolean() {
    return array(
      1 => ts('Yes'),
      0 => ts('No'),
    );
  }

  /**
   * Preferred mail format.
   *
   * @return array
   */
  public static function pmf() {
    return array(
      'Both' => ts('Both'),
      'HTML' => ts('HTML'),
      'Text' => ts('Text'),
    );
  }

  /**
   * Privacy options.
   *
   * @return array
   */
  public static function privacy() {
    return array(
      'do_not_phone' => ts('Do not phone'),
      'do_not_email' => ts('Do not email'),
      'do_not_mail' => ts('Do not mail'),
      'do_not_sms' => ts('Do not sms'),
      'do_not_trade' => ts('Do not trade'),
      'is_opt_out' => ts('No bulk emails (User Opt Out)'),
    );
  }

  /**
   * Various pre defined contact super types.
   *
   * @return array
   */
  public static function contactType() {
    static $contactType = NULL;
    if (!$contactType) {
      $contactType = CRM_Contact_BAO_ContactType::basicTypePairs();
    }
    return $contactType;
  }

  /**
   * Various pre defined unit list.
   *
   * @param string $unitType
   * @return array
   */
  public static function unitList($unitType = NULL) {
    $unitList = array(
      'day' => ts('day'),
      'month' => ts('month'),
      'year' => ts('year'),
    );
    if ($unitType == 'duration') {
      $unitList['lifetime'] = ts('lifetime');
    }
    return $unitList;
  }

  /**
   * Membership type unit.
   *
   * @return array
   */
  public static function membershipTypeUnitList() {
    return self::unitList('duration');
  }

  /**
   * Various pre defined period types.
   *
   * @return array
   */
  public static function periodType() {
    return array(
      'rolling' => ts('Rolling'),
      'fixed' => ts('Fixed'),
    );
  }

  /**
   * Various pre defined email selection methods.
   *
   * @return array
   */
  public static function emailSelectMethods() {
    return array(
      'automatic' => ts("Automatic"),
      'location-only' => ts("Only send to email addresses assigned to the specified location"),
      'location-prefer' => ts("Prefer email addresses assigned to the specified location"),
      'location-exclude' => ts("Exclude email addresses assigned to the specified location"),
    );
  }

  /**
   * Various pre defined member visibility options.
   *
   * @return array
   */
  public static function memberVisibility() {
    return array(
      'Public' => ts('Public'),
      'Admin' => ts('Admin'),
    );
  }

  /**
   * Member auto-renew options
   *
   * @return array
   */
  public static function memberAutoRenew() {
    return array(
      ts('No auto-renew option'),
      ts('Give option, but not required'),
      ts('Auto-renew required'),
    );
  }

  /**
   * Various pre defined event dates.
   *
   * @return array
   */
  public static function eventDate() {
    return array(
      'start_date' => ts('start date'),
      'end_date' => ts('end date'),
      'join_date' => ts('member since'),
    );
  }

  /**
   * Custom form field types.
   *
   * @return array
   */
  public static function customHtmlType() {
    return array(
      'Text' => ts('Single-line input field (text or numeric)'),
      'TextArea' => ts('Multi-line text box (textarea)'),
      'Select' => ts('Drop-down (select list)'),
      'Radio' => ts('Radio buttons'),
      'CheckBox' => ts('Checkbox(es)'),
      'Select Date' => ts('Select Date'),
      'File' => ts('File'),
      'Select State/Province' => ts('Select State/Province'),
      'Multi-Select State/Province' => ts('Multi-Select State/Province'),
      'Select Country' => ts('Select Country'),
      'Multi-Select Country' => ts('Multi-Select Country'),
      'RichTextEditor' => ts('Rich Text Editor'),
      'Autocomplete-Select' => ts('Autocomplete-Select'),
      'Multi-Select' => ts('Multi-Select'),
      'AdvMulti-Select' => ts('AdvMulti-Select'),
      'Link' => ts('Link'),
      'ContactReference' => ts('Autocomplete-Select'),
    );
  }

  /**
   * Various pre defined extensions for dynamic properties and groups.
   *
   * @return array
   *
   */
  public static function customGroupExtends() {
    $customGroupExtends = array(
      'Activity' => ts('Activities'),
      'Relationship' => ts('Relationships'),
      'Contribution' => ts('Contributions'),
      'ContributionRecur' => ts('Recurring Contributions'),
      'Group' => ts('Groups'),
      'Membership' => ts('Memberships'),
      'Event' => ts('Events'),
      'Participant' => ts('Participants'),
      'ParticipantRole' => ts('Participants (Role)'),
      'ParticipantEventName' => ts('Participants (Event Name)'),
      'ParticipantEventType' => ts('Participants (Event Type)'),
      'Pledge' => ts('Pledges'),
      'Grant' => ts('Grants'),
      'Address' => ts('Addresses'),
      'Campaign' => ts('Campaigns'),
    );
    $contactTypes = self::contactType();
    $contactTypes = !empty($contactTypes) ? array('Contact' => 'Contacts') + $contactTypes : array();
    $extendObjs = CRM_Core_OptionGroup::values('cg_extend_objects');
    $customGroupExtends = array_merge($contactTypes, $customGroupExtends, $extendObjs);
    return $customGroupExtends;
  }

  /**
   * Styles for displaying the custom data group.
   *
   * @return array
   */
  public static function customGroupStyle() {
    return array(
      'Tab' => ts('Tab'),
      'Inline' => ts('Inline'),
      'Tab with table' => ts('Tab with table'),
    );
  }

  /**
   * For displaying the uf group types.
   *
   * @return array
   */
  public static function ufGroupTypes() {
    $ufGroupType = array(
      'Profile' => ts('Standalone Form or Directory'),
      'Search Profile' => ts('Search Views'),
    );

    if (CRM_Core_Config::singleton()->userSystem->supports_form_extensions) {
      $ufGroupType += array(
        'User Registration' => ts('Drupal User Registration'),
        'User Account' => ts('View/Edit Drupal User Account'),
      );
    }
    return $ufGroupType;
  }

  /**
   * The status of a contact within a group.
   *
   * @return array
   */
  public static function groupContactStatus() {
    return array(
      'Added' => ts('Added'),
      'Removed' => ts('Removed'),
      'Pending' => ts('Pending'),
    );
  }

  /**
   * List of Group Types.
   *
   * @return array
   */
  public static function groupType() {
    return array(
      'query' => ts('Dynamic'),
      'static' => ts('Static'),
    );
  }

  /**
   * Compose the parameters for a date select object.
   *
   * @param string|NULL $type
   *   the type of date
   * @param string|NULL $format
   *   date format (QF format)
   * @param null $minOffset
   * @param null $maxOffset
   *
   * @return array
   *   the date array
   */
  public static function date($type = NULL, $format = NULL, $minOffset = NULL, $maxOffset = NULL) {
    $date = array(
      'addEmptyOption' => TRUE,
      'emptyOptionText' => ts('- select -'),
      'emptyOptionValue' => '',
    );

    if ($format) {
      $date['format'] = $format;
    }
    else {
      if ($type) {
        $dao = new CRM_Core_DAO_PreferencesDate();
        $dao->name = $type;
        if (!$dao->find(TRUE)) {
          CRM_Core_Error::fatal();
        }
      }

      if ($type == 'creditCard') {
        $minOffset = $dao->start;
        $maxOffset = $dao->end;
        $date['format'] = $dao->date_format;
        $date['addEmptyOption'] = TRUE;
        $date['emptyOptionText'] = ts('- select -');
        $date['emptyOptionValue'] = '';
      }

      if (empty($date['format'])) {
        $date['format'] = 'M d';
      }
    }

    $year = date('Y');
    $date['minYear'] = $year - $minOffset;
    $date['maxYear'] = $year + $maxOffset;
    return $date;
  }

  /**
   * Values for UF form visibility options.
   *
   * @return array
   */
  public static function ufVisibility() {
    return array(
      'User and User Admin Only' => ts('User and User Admin Only'),
      'Public Pages' => ts('Expose Publicly'),
      'Public Pages and Listings' => ts('Expose Publicly and for Listings'),
    );
  }

  /**
   * Values for group form visibility options.
   *
   * @return array
   */
  public static function groupVisibility() {
    return array(
      'User and User Admin Only' => ts('User and User Admin Only'),
      'Public Pages' => ts('Public Pages'),
    );
  }

  /**
   * Different type of Mailing Components.
   *
   * @return array
   */
  public static function mailingComponents() {
    return array(
      'Header' => ts('Header'),
      'Footer' => ts('Footer'),
      'Reply' => ts('Reply Auto-responder'),
      'OptOut' => ts('Opt-out Message'),
      'Subscribe' => ts('Subscription Confirmation Request'),
      'Welcome' => ts('Welcome Message'),
      'Unsubscribe' => ts('Unsubscribe Message'),
      'Resubscribe' => ts('Resubscribe Message'),
    );
  }

  /**
   * Get hours.
   *
   * @return array
   */
  public function getHours() {
    $hours = array();
    for ($i = 0; $i <= 6; $i++) {
      $hours[$i] = $i;
    }
    return $hours;
  }

  /**
   * Get minutes.
   *
   * @return array
   */
  public function getMinutes() {
    $minutes = array();
    for ($i = 0; $i < 60; $i = $i + 15) {
      $minutes[$i] = $i;
    }
    return $minutes;
  }

  /**
   * Get the Map Provider.
   *
   * @return array
   *   array of map providers
   */
  public static function mapProvider() {
    static $map = NULL;
    if (!$map) {
      $map = array('' => '- select -') + CRM_Utils_System::getPluginList('templates/CRM/Contact/Form/Task/Map', ".tpl");
    }
    return $map;
  }

  /**
   * Get the Geocoding Providers from available plugins.
   *
   * @return array
   *   array of geocoder providers
   */
  public static function geoProvider() {
    static $geo = NULL;
    if (!$geo) {
      $geo = array('' => '- select -') + CRM_Utils_System::getPluginList('CRM/Utils/Geocode');
    }
    return $geo;
  }

  /**
   * Get the Address Standardization Providers from available plugins.
   *
   * @return array
   *   array of address standardization providers
   */
  public static function addressProvider() {
    static $addr = NULL;
    if (!$addr) {
      $addr = CRM_Utils_System::getPluginList('CRM/Utils/Address', '.php', array('BatchUpdate'));
    }
    return $addr;
  }

  /**
   * Different type of Mailing Tokens.
   *
   * @return array
   */
  public static function mailingTokens() {
    return array(
      '{action.unsubscribe}' => ts('Unsubscribe via email'),
      '{action.unsubscribeUrl}' => ts('Unsubscribe via web page'),
      '{action.resubscribe}' => ts('Resubscribe via email'),
      '{action.resubscribeUrl}' => ts('Resubscribe via web page'),
      '{action.optOut}' => ts('Opt out via email'),
      '{action.optOutUrl}' => ts('Opt out via web page'),
      '{action.forward}' => ts('Forward this email (link)'),
      '{action.reply}' => ts('Reply to this email (link)'),
      '{action.subscribeUrl}' => ts('Subscribe via web page'),
      '{domain.name}' => ts('Domain name'),
      '{domain.address}' => ts('Domain (organization) address'),
      '{domain.phone}' => ts('Domain (organization) phone'),
      '{domain.email}' => ts('Domain (organization) email'),
      '{mailing.name}' => ts('Mailing name'),
      '{mailing.group}' => ts('Mailing group'),
      '{mailing.viewUrl}' => ts('Mailing permalink'),
    );
  }

  /**
   * Different type of Activity Tokens.
   *
   * @return array
   */
  public static function activityTokens() {
    return array(
      '{activity.activity_id}' => ts('Activity ID'),
      '{activity.subject}' => ts('Activity Subject'),
      '{activity.details}' => ts('Activity Details'),
      '{activity.activity_date_time}' => ts('Activity Date Time'),
    );
  }

  /**
   * Different type of Membership Tokens.
   *
   * @return array
   */
  public static function membershipTokens() {
    return array(
      '{membership.id}' => ts('Membership ID'),
      '{membership.status}' => ts('Membership Status'),
      '{membership.type}' => ts('Membership Type'),
      '{membership.start_date}' => ts('Membership Start Date'),
      '{membership.join_date}' => ts('Membership Join Date'),
      '{membership.end_date}' => ts('Membership End Date'),
      '{membership.fee}' => ts('Membership Fee'),
    );
  }

  /**
   * Different type of Event Tokens.
   *
   * @return array
   */
  public static function eventTokens() {
    return array(
      '{event.event_id}' => ts('Event ID'),
      '{event.title}' => ts('Event Title'),
      '{event.start_date}' => ts('Event Start Date'),
      '{event.end_date}' => ts('Event End Date'),
      '{event.event_type}' => ts('Event Type'),
      '{event.summary}' => ts('Event Summary'),
      '{event.contact_email}' => ts('Event Contact Email'),
      '{event.contact_phone}' => ts('Event Contact Phone'),
      '{event.description}' => ts('Event Description'),
      '{event.location}' => ts('Event Location'),
      '{event.fee_amount}' => ts('Event Fees'),
      '{event.info_url}' => ts('Event Info URL'),
      '{event.registration_url}' => ts('Event Registration URL'),
      '{event.balance}' => ts('Event Balance'),
    );
  }

  /**
   * Different type of Event Tokens.
   *
   * @return array
   */
  public static function contributionTokens() {
    return array(
      '{contribution.contribution_id}' => ts('Contribution ID'),
      '{contribution.total_amount}' => ts('Total Amount'),
      '{contribution.fee_amount}' => ts('Fee Amount'),
      '{contribution.net_amount}' => ts('Net Amount'),
      '{contribution.non_deductible_amount}' => ts('Non-deductible Amount'),
      '{contribution.receive_date}' => ts('Contribution Date Received'),
      '{contribution.payment_instrument}' => ts('Payment Method'),
      '{contribution.trxn_id}' => ts('Transaction ID'),
      '{contribution.invoice_id}' => ts('Invoice ID'),
      '{contribution.currency}' => ts('Currency'),
      '{contribution.cancel_date}' => ts('Contribution Cancel Date'),
      '{contribution.cancel_reason}' => ts('Contribution Cancel Reason'),
      '{contribution.receipt_date}' => ts('Receipt Date'),
      '{contribution.thankyou_date}' => ts('Thank You Date'),
      '{contribution.contribution_source}' => ts('Contribution Source'),
      '{contribution.amount_level}' => ts('Amount Level'),
      //'{contribution.contribution_recur_id}' => ts('Contribution Recurring ID'),
      //'{contribution.honor_contact_id}' => ts('Honor Contact ID'),
      '{contribution.contribution_status_id}' => ts('Contribution Status'),
      //'{contribution.honor_type_id}' => ts('Honor Type ID'),
      //'{contribution.address_id}' => ts('Address ID'),
      '{contribution.check_number}' => ts('Check Number'),
      '{contribution.campaign}' => ts('Contribution Campaign'),
    );
  }

  /**
   * Different type of Contact Tokens.
   *
   * @return array
   */
  public static function contactTokens() {
    static $tokens = NULL;
    if (!$tokens) {
      $additionalFields = array(
        'checksum' => array('title' => ts('Checksum')),
        'contact_id' => array('title' => ts('Internal Contact ID')),
      );
      $exportFields = array_merge(CRM_Contact_BAO_Contact::exportableFields(), $additionalFields);

      $values = array_merge(array_keys($exportFields));
      unset($values[0]);

      //FIXME:skipping some tokens for time being.
      $skipTokens = array(
        'is_bulkmail',
        'group',
        'tag',
        'contact_sub_type',
        'note',
        'is_deceased',
        'deceased_date',
        'legal_identifier',
        'contact_sub_type',
        'user_unique_id',
      );

      $customFields = CRM_Core_BAO_CustomField::getFields(array('Individual', 'Address'));
      $legacyTokenNames = array_flip(CRM_Utils_Token::legacyContactTokens());

      foreach ($values as $val) {
        if (in_array($val, $skipTokens)) {
          continue;
        }
        //keys for $tokens should be constant. $token Values are changed for Custom Fields. CRM-3734
        $customFieldId = CRM_Core_BAO_CustomField::getKeyID($val);
        if ($customFieldId) {
          // CRM-15191 - if key is not in $customFields then the field is disabled and should be ignored
          if (!empty($customFields[$customFieldId])) {
            $tokens["{contact.$val}"] = $customFields[$customFieldId]['label'] . " :: " . $customFields[$customFieldId]['groupTitle'];
          }
        }
        else {
          // Support legacy token names
          $tokenName = CRM_Utils_Array::value($val, $legacyTokenNames, $val);
          $tokens["{contact.$tokenName}"] = $exportFields[$val]['title'];
        }
      }

      // Get all the hook tokens too
      $hookTokens = array();
      CRM_Utils_Hook::tokens($hookTokens);
      foreach ($hookTokens as $tokenValues) {
        foreach ($tokenValues as $key => $value) {
          if (is_numeric($key)) {
            $key = $value;
          }
          if (!preg_match('/^\{[^\}]+\}$/', $key)) {
            $key = '{' . $key . '}';
          }
          if (preg_match('/^\{([^\}]+)\}$/', $value, $matches)) {
            $value = $matches[1];
          }
          $tokens[$key] = $value;
        }
      }
    }

    return $tokens;
  }

  /**
   * Different type of Participant Tokens.
   *
   * @return array
   */
  public static function participantTokens() {
    static $tokens = NULL;
    if (!$tokens) {
      $exportFields = CRM_Event_BAO_Participant::exportableFields();

      $values = array_merge(array_keys($exportFields));
      unset($values[0]);

      // skipping some tokens for time being.
      $skipTokens = array(
        'event_id',
        'participant_is_pay_later',
        'participant_is_test',
        'participant_contact_id',
        'participant_fee_currency',
        'participant_campaign_id',
        'participant_status',
        'participant_discount_name',
      );

      $customFields = CRM_Core_BAO_CustomField::getFields('Participant');

      foreach ($values as $key => $val) {
        if (in_array($val, $skipTokens)) {
          continue;
        }
        //keys for $tokens should be constant. $token Values are changed for Custom Fields. CRM-3734
        if ($customFieldId = CRM_Core_BAO_CustomField::getKeyID($val)) {
          $tokens["{participant.$val}"] = !empty($customFields[$customFieldId]) ? $customFields[$customFieldId]['label'] . " :: " . $customFields[$customFieldId]['groupTitle'] : '';
        }
        else {
          $tokens["{participant.$val}"] = $exportFields[$val]['title'];
        }
      }
    }
    return $tokens;
  }

  /**
   * @param int $caseTypeId
   * @return array
   */
  public static function caseTokens($caseTypeId = NULL) {
    static $tokens = NULL;
    if (!$tokens) {
      foreach (CRM_Case_BAO_Case::fields() as $field) {
        $tokens["{case.{$field['name']}}"] = $field['title'];
      }

      $customFields = CRM_Core_BAO_CustomField::getFields('Case', FALSE, FALSE, $caseTypeId);
      foreach ($customFields as $id => $field) {
        $tokens["{case.custom_$id}"] = "{$field['label']} :: {$field['groupTitle']}";
      }
    }
    return $tokens;
  }

  /**
   * CiviCRM supported date input formats.
   *
   * @return array
   */
  public static function getDatePluginInputFormats() {
    $dateInputFormats = array(
      "mm/dd/yy" => ts('mm/dd/yyyy (12/31/2009)'),
      "dd/mm/yy" => ts('dd/mm/yyyy (31/12/2009)'),
      "yy-mm-dd" => ts('yyyy-mm-dd (2009-12-31)'),
      "dd-mm-yy" => ts('dd-mm-yyyy (31-12-2009)'),
      'dd.mm.yy' => ts('dd.mm.yyyy (31.12.2009)'),
      "M d, yy" => ts('M d, yyyy (Dec 31, 2009)'),
      'd M yy' => ts('d M yyyy (31 Dec 2009)'),
      "MM d, yy" => ts('MM d, yyyy (December 31, 2009)'),
      'd MM yy' => ts('d MM yyyy (31 December 2009)'),
      "DD, d MM yy" => ts('DD, d MM yyyy (Thursday, 31 December 2009)'),
      "mm/dd" => ts('mm/dd (12/31)'),
      "dd-mm" => ts('dd-mm (31-12)'),
      "yy-mm" => ts('yyyy-mm (2009-12)'),
      'M yy' => ts('M yyyy (Dec 2009)'),
      "yy" => ts('yyyy (2009)'),
    );

    /*
    Year greater than 2000 get wrong result for following format
    echo date( 'Y-m-d', strtotime( '7 Nov, 2001') );
    echo date( 'Y-m-d', strtotime( '7 November, 2001') );
    Return current year
    expected :: 2001-11-07
    output   :: 2009-11-07
    However
    echo date( 'Y-m-d', strtotime( 'Nov 7, 2001') );
    echo date( 'Y-m-d', strtotime( 'November 7, 2001') );
    gives proper result
     */

    return $dateInputFormats;
  }

  /**
   * Map date plugin and actual format that is used by PHP.
   *
   * @return array
   */
  public static function datePluginToPHPFormats() {
    $dateInputFormats = array(
      "mm/dd/yy" => 'm/d/Y',
      "dd/mm/yy" => 'd/m/Y',
      "yy-mm-dd" => 'Y-m-d',
      "dd-mm-yy" => 'd-m-Y',
      "dd.mm.yy" => 'd.m.Y',
      "M d, yy" => 'M j, Y',
      "d M yy" => 'j M Y',
      "MM d, yy" => 'F j, Y',
      "d MM yy" => 'j F Y',
      "DD, d MM yy" => 'l, j F Y',
      "mm/dd" => 'm/d',
      "dd-mm" => 'd-m',
      "yy-mm" => 'Y-m',
      "M yy" => 'M Y',
      "yy" => 'Y',
    );
    return $dateInputFormats;
  }

  /**
   * Time formats.
   *
   * @return array
   */
  public static function getTimeFormats() {
    return array(
      '1' => ts('12 Hours'),
      '2' => ts('24 Hours'),
    );
  }

  /**
   * Get numeric options.
   *
   * @param int $start
   * @param int $end
   *
   * @return array
   */
  public static function getNumericOptions($start = 0, $end = 10) {
    $numericOptions = array();
    for ($i = $start; $i <= $end; $i++) {
      $numericOptions[$i] = $i;
    }
    return $numericOptions;
  }

  /**
   * Barcode types.
   *
   * @return array
   */
  public static function getBarcodeTypes() {
    return array(
      'barcode' => ts('Linear (1D)'),
      'qrcode' => ts('QR code'),
    );
  }

  /**
   * Dedupe rule types.
   *
   * @return array
   */
  public static function getDedupeRuleTypes() {
    return array(
      'Unsupervised' => ts('Unsupervised'),
      'Supervised' => ts('Supervised'),
      'General' => ts('General'),
    );
  }

  /**
   * Campaign group types.
   *
   * @return array
   */
  public static function getCampaignGroupTypes() {
    return array(
      'Include' => ts('Include'),
      'Exclude' => ts('Exclude'),
    );
  }

  /**
   * Subscription history method.
   *
   * @return array
   */
  public static function getSubscriptionHistoryMethods() {
    return array(
      'Admin' => ts('Admin'),
      'Email' => ts('Email'),
      'Web' => ts('Web'),
      'API' => ts('API'),
    );
  }

  /**
   * Premium units.
   *
   * @return array
   */
  public static function getPremiumUnits() {
    return array(
      'day' => ts('Day'),
      'week' => ts('Week'),
      'month' => ts('Month'),
      'year' => ts('Year'),
    );
  }

  /**
   * Extension types.
   *
   * @return array
   */
  public static function getExtensionTypes() {
    return array(
      'payment' => ts('Payment'),
      'search' => ts('Search'),
      'report' => ts('Report'),
      'module' => ts('Module'),
      'sms' => ts('SMS'),
    );
  }

  /**
   * Job frequency.
   *
   * @return array
   */
  public static function getJobFrequency() {
    return array(
      // CRM-17669
      'Yearly' => ts('Yearly'),
      'Quarter' => ts('Quarterly'),
      'Monthly' => ts('Monthly'),
      'Weekly' => ts('Weekly'),

      'Daily' => ts('Daily'),
      'Hourly' => ts('Hourly'),
      'Always' => ts('Every time cron job is run'),
    );
  }

  /**
   * Search builder operators.
   *
   * @return array
   */
  public static function getSearchBuilderOperators($fieldType = NULL) {
    $builderOperators = array(
      '=' => '=',
      '!=' => '≠',
      '>' => '>',
      '<' => '<',
      '>=' => '≥',
      '<=' => '≤',
      'IN' => ts('In'),
      'NOT IN' => ts('Not In'),
      'LIKE' => ts('Like'),
      'NOT LIKE' => ts('Not Like'),
      'RLIKE' => ts('Regex'),
      'IS EMPTY' => ts('Is Empty'),
      'IS NOT EMPTY' => ts('Not Empty'),
      'IS NULL' => ts('Is Null'),
      'IS NOT NULL' => ts('Not Null'),
    );
    if ($fieldType) {
      switch ($fieldType) {
        case CRM_Utils_Type::T_STRING:
          unset($builderOperators['>']);
          unset($builderOperators['<']);
          unset($builderOperators['>=']);
          unset($builderOperators['<=']);
          break;
      }
    }
    return $builderOperators;
  }

  /**
   * Profile group types.
   *
   * @return array
   */
  public static function getProfileGroupType() {
    $profileGroupType = array(
      'Activity' => ts('Activities'),
      'Contribution' => ts('Contributions'),
      'Membership' => ts('Memberships'),
      'Participant' => ts('Participants'),
    );
    $contactTypes = self::contactType();
    $contactTypes = !empty($contactTypes) ? array('Contact' => 'Contacts') + $contactTypes : array();
    $profileGroupType = array_merge($contactTypes, $profileGroupType);

    return $profileGroupType;
  }


  /**
   * Word replacement match type.
   *
   * @return array
   */
  public static function getWordReplacementMatchType() {
    return array(
      'exactMatch' => ts('Exact Match'),
      'wildcardMatch' => ts('Wildcard Match'),
    );
  }

  /**
   * Mailing group types.
   *
   * @return array
   */
  public static function getMailingGroupTypes() {
    return array(
      'Include' => ts('Include'),
      'Exclude' => ts('Exclude'),
      'Base' => ts('Base'),
    );
  }

  /**
   * Mailing Job Status.
   *
   * @return array
   */
  public static function getMailingJobStatus() {
    return array(
      'Scheduled' => ts('Scheduled'),
      'Running' => ts('Running'),
      'Complete' => ts('Complete'),
      'Paused' => ts('Paused'),
      'Canceled' => ts('Canceled'),
    );
  }

  /**
   * @return array
   */
  public static function billingMode() {
    return array(
      CRM_Core_Payment::BILLING_MODE_FORM => 'form',
      CRM_Core_Payment::BILLING_MODE_BUTTON => 'button',
      CRM_Core_Payment::BILLING_MODE_NOTIFY => 'notify',
    );
  }

  /**
   * Frequency unit for schedule reminders.
   *
   * @param int $count
   *   For pluralization
   * @return array
   */
  public static function getRecurringFrequencyUnits($count = 1) {
    // @todo this used to refer to the 'recur_frequency_unit' option_values which
    // is for recurring payments and probably not good to re-use for recurring entities.
    // If something other than a hard-coded list is desired, add a new option_group.
    return array(
      'hour' => ts('hour', array('plural' => 'hours', 'count' => $count)),
      'day' => ts('day', array('plural' => 'days', 'count' => $count)),
      'week' => ts('week', array('plural' => 'weeks', 'count' => $count)),
      'month' => ts('month', array('plural' => 'months', 'count' => $count)),
      'year' => ts('year', array('plural' => 'years', 'count' => $count)),
    );
  }

  /**
   * Relative Date Terms.
   *
   * @return array
   */
  public static function getRelativeDateTerms() {
    return array(
      'previous' => ts('Previous'),
      'previous_2' => ts('Previous 2'),
      'previous_before' => ts('Prior to Previous'),
      'before_previous' => ts('All Prior to Previous'),
      'earlier' => ts('To End of Previous'),
      'greater_previous' => ts('From End of Previous'),
      'greater' => ts('From Start Of Current'),
      'current' => ts('Current'),
      'ending_3' => ts('Last 3'),
      'ending_2' => ts('Last 2'),
      'ending' => ts('Last'),
      'this' => ts('This'),
      'starting' => ts('Upcoming'),
      'less' => ts('To End of'),
      'next' => ts('Next'),
    );
  }

  /**
   * Relative Date Units.
   *
   * @return array
   */
  public static function getRelativeDateUnits() {
    return array(
      'year' => ts('Years'),
      'fiscal_year' => ts('Fiscal Years'),
      'quarter' => ts('Quarters'),
      'month' => ts('Months'),
      'week' => ts('Weeks'),
      'day' => ts('Days'),
    );
  }

  /**
   * Exportable document formats.
   *
   * @return array
   */
  public static function documentFormat() {
    return array(
      'pdf' => ts('Portable Document Format (.pdf)'),
      'docx' => ts('MS Word (.docx)'),
      'odt' => ts('Open Office (.odt)'),
      'html' => ts('Webpage (.html)'),
    );
  }

  /**
   * Application type of document.
   *
   * @return array
   */
  public static function documentApplicationType() {
    return array(
      'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'odt' => 'application/vnd.oasis.opendocument.text',
    );
  }

}
