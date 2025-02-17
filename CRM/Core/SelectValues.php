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

use Civi\Token\TokenProcessor;

/**
 * One place to store frequently used values in Select Elements. Note that
 * some of the below elements will be dynamic, so we'll probably have a
 * smart caching scheme on a per domain basis
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_SelectValues {

  /**
   * The possible attributes of each item in an option list
   *
   * APIv4 refers to these as "suffixes".
   *
   * @return array
   */
  public static function optionAttributes():array {
    static $attributes;
    $attributes ??= [
      'label' => ts('Label'),
      'name' => ts('Internal Name'),
      'description' => ts('Description'),
      'abbr' => ts('Abbreviation'),
      'icon' => ts('Icon'),
      'color' => ts('Color'),
      'grouping' => ts('Grouping'),
      'url' => ts('Url'),
    ];
    return $attributes;
  }

  /**
   * Yes/No options
   *
   * @return array
   */
  public static function boolean() {
    return [
      1 => ts('Yes'),
      0 => ts('No'),
    ];
  }

  /**
   * Preferred mail format.
   *
   * @return array
   */
  public static function pmf() {
    return [
      'Both' => ts('Both'),
      'HTML' => ts('HTML'),
      'Text' => ts('Text'),
    ];
  }

  /**
   * Privacy options.
   *
   * @return array
   */
  public static function privacy() {
    return [
      'do_not_phone' => ts('Do not phone'),
      'do_not_email' => ts('Do not email'),
      'do_not_mail' => ts('Do not mail'),
      'do_not_sms' => ts('Do not sms'),
      'do_not_trade' => ts('Do not trade'),
      'is_opt_out' => ts('No Bulk Emails (User Opt Out)'),
    ];
  }

  /**
   * Various pre defined contact super types.
   *
   * @return array
   */
  public static function contactType() {
    return CRM_Contact_BAO_ContactType::basicTypePairs();
  }

  /**
   * Various pre defined unit list.
   *
   * @param string $unitType
   * @return array
   */
  public static function unitList($unitType = NULL) {
    $unitList = [
      'day' => ts('day'),
      'month' => ts('month'),
      'year' => ts('year'),
    ];
    if ($unitType === 'duration') {
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
    return [
      'rolling' => ts('Rolling'),
      'fixed' => ts('Fixed'),
    ];
  }

  /**
   * Various pre defined email selection methods.
   *
   * @return array
   */
  public static function emailSelectMethods() {
    return [
      'automatic' => ts('Automatic'),
      'location-only' => ts('Only send to email addresses assigned to the specified location'),
      'location-prefer' => ts('Prefer email addresses assigned to the specified location'),
      'location-exclude' => ts('Exclude email addresses assigned to the specified location'),
    ];
  }

  /**
   * Various pre defined member visibility options.
   *
   * @return array
   */
  public static function memberVisibility() {
    return [
      'Public' => ts('Public'),
      'Admin' => ts('Admin'),
    ];
  }

  /**
   * Member auto-renew options
   *
   * @return array
   */
  public static function memberAutoRenew() {
    return [
      ts('No auto-renew option'),
      ts('Give option, but not required'),
      ts('Auto-renew required'),
    ];
  }

  /**
   * Various pre defined event dates.
   *
   * @return array
   */
  public static function eventDate() {
    return [
      'start_date' => ts('Membership Start Date'),
      'end_date' => ts('Membership Expiration Date'),
      'join_date' => ts('Member Since'),
    ];
  }

  /**
   * Custom form field types.
   *
   * @return array
   */
  public static function customHtmlType() {
    return [
      [
        'id' => 'Text',
        'name' => 'Single-line input field (text or numeric)',
        'label' => ts('Single-line input field (text or numeric)'),
      ],
      [
        'id' => 'TextArea',
        'name' => 'Multi-line text box (textarea)',
        'label' => ts('Multi-line text box (textarea)'),
      ],
      [
        'id' => 'Select',
        'name' => 'Drop-down (select list)',
        'label' => ts('Drop-down (select list)'),
      ],
      [
        'id' => 'Radio',
        'name' => 'Radio buttons',
        'label' => ts('Radio buttons'),
      ],
      [
        'id' => 'CheckBox',
        'name' => 'Checkbox(es)',
        'label' => ts('Checkbox(es)'),
      ],
      [
        'id' => 'Select Date',
        'name' => 'Select Date',
        'label' => ts('Select Date'),
      ],
      [
        'id' => 'File',
        'name' => 'File',
        'label' => ts('File'),
      ],
      [
        'id' => 'RichTextEditor',
        'name' => 'Rich Text Editor',
        'label' => ts('Rich Text Editor'),
      ],
      [
        'id' => 'Autocomplete-Select',
        'name' => 'Autocomplete-Select',
        'label' => ts('Autocomplete-Select'),
      ],
      [
        'id' => 'Link',
        'name' => 'Link',
        'label' => ts('Link'),
      ],
      [
        'id' => 'Hidden',
        'name' => 'Hidden',
        'label' => ts('Hidden'),
      ],
    ];
  }

  /**
   * List of entities to present on the Custom Group form.
   *
   * Includes pseudo-entities for Participant, in order to present sub-types on the form.
   *
   * @return array
   */
  public static function customGroupExtends() {
    $customGroupExtends = array_column(CRM_Core_BAO_CustomGroup::getCustomGroupExtendsOptions(), 'label', 'id');
    // ParticipantRole, ParticipantEventName, etc.
    $pseudoSelectors = CRM_Core_OptionGroup::values('custom_data_type', FALSE, FALSE, FALSE, NULL, 'label', TRUE, FALSE, 'name');
    return array_merge($customGroupExtends, $pseudoSelectors);
  }

  /**
   * Styles for displaying the custom data group.
   *
   * @return array
   */
  public static function customGroupStyle() {
    return [
      'Tab' => ts('Tab'),
      'Inline' => ts('Inline'),
      'Tab with table' => ts('Tab with table'),
    ];
  }

  /**
   * For displaying the uf group types.
   *
   * @return array
   */
  public static function ufGroupTypes() {
    $ufGroupType = [
      'Profile' => ts('Standalone Form or Directory'),
      'Search Profile' => ts('Search Views'),
    ];

    if (CRM_Core_Config::singleton()->userSystem->supports_form_extensions) {
      $ufGroupType += CRM_Core_Config::singleton()->userSystem->getUfGroupTypes();
    }
    return $ufGroupType;
  }

  /**
   * The status of a contact within a group.
   *
   * @return array
   */
  public static function groupContactStatus() {
    return [
      'Added' => ts('Added'),
      'Removed' => ts('Removed'),
      'Pending' => ts('Pending'),
    ];
  }

  /**
   * List of Group Types.
   *
   * @return array
   */
  public static function groupType() {
    return [
      'query' => ts('Dynamic'),
      'static' => ts('Static'),
    ];
  }

  /**
   * Compose the parameters for a date select object.
   *
   * @param string|null $type
   *   the type of date
   * @param string|null $format
   *   date format (QF format)
   * @param null $minOffset
   * @param null $maxOffset
   * @param string $context
   *
   * @return array
   *   the date array
   * @throws CRM_Core_Exception
   */
  public static function date($type = NULL, $format = NULL, $minOffset = NULL, $maxOffset = NULL, $context = 'display') {
    // These options are deprecated. Definitely not used in datepicker. Possibly not even in jcalendar+addDateTime.
    $date = [
      'addEmptyOption' => TRUE,
      'emptyOptionText' => ts('- select -'),
      'emptyOptionValue' => '',
    ];

    if ($format) {
      $date['format'] = $format;
    }
    else {
      if ($type) {
        $dao = new CRM_Core_DAO_PreferencesDate();
        $dao->name = $type;
        if (!$dao->find(TRUE)) {
          throw new CRM_Core_Exception('Date preferences not configured.');
        }
        if (!$maxOffset) {
          $maxOffset = $dao->end;
        }
        if (!$minOffset) {
          $minOffset = $dao->start;
        }

        $date['format'] = $dao->date_format;
        $date['time'] = (bool) $dao->time_format;
      }

      if (empty($date['format'])) {
        if ($context === 'Input') {
          $date['format'] = Civi::settings()->get('dateInputFormat');
        }
        else {
          $date['format'] = 'M d';
        }
      }
    }

    $date['smarty_view_format'] = CRM_Utils_Date::getDateFieldViewFormat($date['format']);
    if (!isset($date['time'])) {
      $date['time'] = FALSE;
    }

    $year = date('Y');
    $date['minYear'] = $year - (int) $minOffset;
    $date['maxYear'] = $year + (int) $maxOffset;
    return $date;
  }

  /**
   * Values for UF form visibility options.
   *
   * @return array
   */
  public static function ufVisibility() {
    return [
      'User and User Admin Only' => ts('User and User Admin Only'),
      'Public Pages' => ts('Public Pages'),
      'Public Pages and Listings' => ts('Public Pages and Listings'),
    ];
  }

  /**
   * Values for group form visibility options.
   *
   * @return array
   */
  public static function groupVisibility() {
    return [
      'User and User Admin Only' => ts('User and User Admin Only'),
      'Public Pages' => ts('Public Pages'),
    ];
  }

  /**
   * Different type of Mailing Components.
   *
   * @return array
   */
  public static function mailingComponents() {
    return [
      'Header' => ts('Header'),
      'Footer' => ts('Footer'),
      'Reply' => ts('Reply Auto-responder'),
      'OptOut' => ts('Opt-out Message'),
      'Subscribe' => ts('Subscription Confirmation Request'),
      'Welcome' => ts('Welcome Message'),
      'Unsubscribe' => ts('Unsubscribe Message'),
      'Resubscribe' => ts('Resubscribe Message'),
    ];
  }

  /**
   * Get hours.
   *
   * @return array
   */
  public function getHours() {
    $hours = [];
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
    $minutes = [];
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
      $map = ['' => ts('- select -')] + CRM_Utils_System::getPluginList('templates/CRM/Contact/Form/Task/Map', ".tpl");
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
      $geo = ['' => ts('- select -')] + CRM_Utils_System::getPluginList('CRM/Utils/Geocode');
    }
    return $geo;
  }

  /**
   * Get options for displaying tax.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public static function taxDisplayOptions() {
    return [
      'Do_not_show' => ts('Do not show breakdown, only show total - i.e %1', [
        1 => CRM_Utils_Money::format(120),
      ]),
      'Inclusive' => ts('Show [tax term] inclusive price - i.e. %1', [
        1 => ts('%1 (includes [tax term] of %2)', [1 => CRM_Utils_Money::format(120), 2 => CRM_Utils_Money::format(20)]),
      ]),
      'Exclusive' => ts('Show [tax term] exclusive price - i.e. %1', [
        1 => ts('%1 + %2 [tax term]', [1 => CRM_Utils_Money::format(120), 2 => CRM_Utils_Money::format(20)]),
      ]),
    ];
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
      $addr = array_merge(['' => ts('- select -')], CRM_Utils_System::getPluginList('CRM/Utils/Address', '.php', ['BatchUpdate']));
    }
    return $addr;
  }

  public static function smsProvider(): array {
    $providers = CRM_SMS_BAO_SmsProvider::getProviders(NULL, NULL, TRUE, 'is_default desc, title');
    $result = [];
    foreach ($providers as $provider) {
      $result[] = [
        'id' => $provider['id'],
        'name' => $provider['name'],
        'label' => $provider['title'],
      ];
    }
    return $result;
  }

  /**
   * Different type of Mailing Tokens.
   *
   * @return array
   */
  public static function mailingTokens() {
    return [
      '{action.unsubscribe}' => ts('Unsubscribe via email'),
      '{action.unsubscribeUrl}' => ts('Unsubscribe via web page'),
      '{action.resubscribe}' => ts('Resubscribe via email'),
      '{action.resubscribeUrl}' => ts('Resubscribe via web page'),
      '{action.optOut}' => ts('Opt out via email'),
      '{action.optOutUrl}' => ts('Opt out via web page'),
      '{action.forward}' => ts('Forward this email (link)'),
      '{action.reply}' => ts('Reply to this email (link)'),
      '{action.subscribeUrl}' => ts('Subscribe via web page'),
      '{mailing.key}' => ts('Mailing key'),
      '{mailing.name}' => ts('Mailing name'),
      '{mailing.group}' => ts('Mailing group'),
      '{mailing.viewUrl}' => ts('Mailing permalink'),
    ] + self::domainTokens();
  }

  /**
   * Domain tokens
   *
   * @return array
   *
   * @deprecated
   */
  public static function domainTokens() {
    $tokenProcessor = new TokenProcessor(Civi::dispatcher(), []);
    return $tokenProcessor->listTokens();
  }

  /**
   * Different type of Activity Tokens.
   *
   * @return array
   */
  public static function activityTokens() {
    return [
      '{activity.activity_id}' => ts('Activity ID'),
      '{activity.subject}' => ts('Activity Subject'),
      '{activity.details}' => ts('Activity Details'),
      '{activity.activity_date_time}' => ts('Activity Date Time'),
    ];
  }

  /**
   * Different type of Membership Tokens.
   *
   * @deprecated
   *
   * @return array
   */
  public static function membershipTokens(): array {
    CRM_Core_Error::deprecatedFunctionWarning('token processor');
    return [
      '{membership.id}' => ts('Membership ID'),
      '{membership.status_id:label}' => ts('Status'),
      '{membership.membership_type_id:label}' => ts('Membership Type'),
      '{membership.start_date}' => ts('Membership Start Date'),
      '{membership.join_date}' => ts('Member Since'),
      '{membership.end_date}' => ts('Membership Expiration Date'),
      '{membership.fee}' => ts('Membership Fee'),
    ];
  }

  /**
   * Different type of Event Tokens.
   *
   * @deprecated
   *
   * @return array
   */
  public static function eventTokens(): array {
    CRM_Core_Error::deprecatedFunctionWarning('token processor');
    $tokenProcessor = new TokenProcessor(Civi::dispatcher(), ['schema' => ['eventId']]);
    $allTokens = $tokenProcessor->listTokens();
    foreach (array_keys($allTokens) as $token) {
      if (str_starts_with($token, '{domain.')) {
        unset($allTokens[$token]);
      }
    }
    return $allTokens;
  }

  /**
   * Different type of Contribution Tokens.
   *
   * @deprecated
   *
   * @return array
   */
  public static function contributionTokens(): array {
    CRM_Core_Error::deprecatedFunctionWarning('use the token processor');
    $tokenProcessor = new TokenProcessor(Civi::dispatcher(), ['schema' => ['contributionId']]);
    $allTokens = $tokenProcessor->listTokens();
    foreach (array_keys($allTokens) as $token) {
      if (str_starts_with($token, '{domain.')) {
        unset($allTokens[$token]);
      }
    }
    return $allTokens;
  }

  /**
   * Different type of Contact Tokens.
   *
   * @deprecated
   *
   * @return array
   */
  public static function contactTokens(): array {
    $tokenProcessor = new TokenProcessor(Civi::dispatcher(), ['schema' => ['contactId']]);
    $allTokens = $tokenProcessor->listTokens();
    foreach (array_keys($allTokens) as $token) {
      if (str_starts_with($token, '{domain.')) {
        unset($allTokens[$token]);
      }
    }
    return $allTokens;
  }

  /**
   * Different type of Participant Tokens.
   *
   * @deprecated
   *
   * @return array
   */
  public static function participantTokens(): array {
    CRM_Core_Error::deprecatedFunctionWarning('user TokenProcessor');
    $tokenProcessor = new TokenProcessor(Civi::dispatcher(), ['schema' => ['participantId']]);
    $allTokens = $tokenProcessor->listTokens();
    foreach (array_keys($allTokens) as $token) {
      if (str_starts_with($token, '{domain.') === 0 || strpos($token, '{event.')) {
        unset($allTokens[$token]);
      }
    }
    return $allTokens;
  }

  /**
   * @param int $caseTypeId
   * @return array
   */
  public static function caseTokens($caseTypeId = NULL) {
    $tokens = [
      '{case.id}' => ts('Case ID'),
      '{case.case_type_id:label}' => ts('Case Type'),
      '{case.subject}' => ts('Case Subject'),
      '{case.start_date}' => ts('Case Start Date'),
      '{case.end_date}' => ts('Case End Date'),
      '{case.details}' => ts('Details'),
      '{case.status_id:label}' => ts('Case Status'),
      '{case.is_deleted:label}' => ts('Case is in the Trash'),
      '{case.created_date}' => ts('Created Date'),
      '{case.modified_date}' => ts('Modified Date'),
    ];

    $customFields = CRM_Core_BAO_CustomField::getFields('Case', FALSE, FALSE, $caseTypeId);
    foreach ($customFields as $id => $field) {
      $tokens["{case.custom_$id}"] = "{$field['label']} :: {$field['groupTitle']}";
    }
    return $tokens;
  }

  /**
   * CiviCRM supported date input formats.
   *
   * @return array
   */
  public static function getDatePluginInputFormats() {
    return [
      'mm/dd/yy' => ts('mm/dd/yy (12/31/2009)'),
      'dd/mm/yy' => ts('dd/mm/yy (31/12/2009)'),
      'yy-mm-dd' => ts('yy-mm-dd (2009-12-31)'),
      'dd-mm-yy' => ts('dd-mm-yy (31-12-2009)'),
      'dd.mm.yy' => ts('dd.mm.yy (31.12.2009)'),
      'M d, yy' => ts('M d, yy (Dec 31, 2009)'),
      'd M yy' => ts('d M yy (31 Dec 2009)'),
      'MM d, yy' => ts('MM d, yy (December 31, 2009)'),
      'd MM yy' => ts('d MM yy (31 December 2009)'),
      'DD, d MM yy' => ts('DD, d MM yy (Thursday, 31 December 2009)'),
      'mm/dd' => ts('mm/dd (12/31)'),
      'dd-mm' => ts('dd-mm (31-12)'),
      'yy-mm' => ts('yy-mm (2009-12)'),
      'M yy' => ts('M yy (Dec 2009)'),
      'yy' => ts('yy (2009)'),
    ];
  }

  /**
   * Time formats.
   *
   * @return array
   */
  public static function getTimeFormats() {
    return [
      '1' => ts('12 Hours'),
      '2' => ts('24 Hours'),
    ];
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
    $numericOptions = [];
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
    return [
      'barcode' => ts('Linear (1D)'),
      'qrcode' => ts('QR code'),
    ];
  }

  /**
   * Dedupe rule types.
   *
   * @return array
   */
  public static function getDedupeRuleTypes() {
    return [
      'Unsupervised' => ts('Unsupervised'),
      'Supervised' => ts('Supervised'),
      'General' => ts('General'),
    ];
  }

  /**
   * Campaign group types.
   *
   * @return array
   */
  public static function getCampaignGroupTypes() {
    return [
      'Include' => ts('Include'),
      'Exclude' => ts('Exclude'),
    ];
  }

  /**
   * Subscription history method.
   *
   * @return array
   */
  public static function getSubscriptionHistoryMethods() {
    return [
      'Admin' => ts('Admin'),
      'Email' => ts('Email'),
      'Form' => ts('Form'),
      'Web' => ts('Web'),
      'API' => ts('API'),
    ];
  }

  /**
   * Premium units.
   *
   * @return array
   */
  public static function getPremiumUnits() {
    return [
      'day' => ts('Day'),
      'week' => ts('Week'),
      'month' => ts('Month'),
      'year' => ts('Year'),
    ];
  }

  /**
   * Get measurement units recognized by the TCPDF package used to create PDF labels.
   *
   * @return array
   *   array of measurement units
   */
  public static function getLayoutUnits(): array {
    return [
      'in' => ts('Inches'),
      'cm' => ts('Centimeters'),
      'mm' => ts('Millimeters'),
      'pt' => ts('Points'),
      'px' => ts('Pixels'),
    ];
  }

  /**
   * Extension types.
   *
   * @return array
   */
  public static function getExtensionTypes() {
    return [
      'payment' => ts('Payment'),
      'search' => ts('Search'),
      'report' => ts('Report'),
      'module' => ts('Module'),
      'sms' => ts('SMS'),
    ];
  }

  /**
   * Job frequency.
   *
   * @return array
   */
  public static function getJobFrequency() {
    return [
      // CRM-17669
      'Yearly' => ts('Yearly'),
      'Quarter' => ts('Quarterly'),
      'Monthly' => ts('Monthly'),
      'Weekly' => ts('Weekly'),

      'Daily' => ts('Daily'),
      'Hourly' => ts('Hourly'),
      'Always' => ts('Every time cron job is run'),
    ];
  }

  /**
   * Search builder operators.
   *
   * @return array
   */
  public static function getSearchBuilderOperators() {
    return [
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
    ];
  }

  /**
   * Profile group types.
   *
   * @return array
   */
  public static function getProfileGroupType() {
    $profileGroupType = [
      'Activity' => ts('Activities'),
      'Contribution' => ts('Contributions'),
      'Membership' => ts('Memberships'),
      'Participant' => ts('Participants'),
    ];
    $contactTypes = self::contactType();
    $contactTypes = !empty($contactTypes) ? ['Contact' => 'Contacts'] + $contactTypes : [];
    $profileGroupType = array_merge($contactTypes, $profileGroupType);

    return $profileGroupType;
  }

  /**
   * Word replacement match type.
   *
   * @return array
   */
  public static function getWordReplacementMatchType() {
    return [
      'exactMatch' => ts('Exact Match'),
      'wildcardMatch' => ts('Wildcard Match'),
    ];
  }

  /**
   * Mailing group types.
   *
   * @return array
   */
  public static function getMailingGroupTypes() {
    return [
      'Include' => ts('Include'),
      'Exclude' => ts('Exclude'),
      'Base' => ts('Base'),
    ];
  }

  /**
   * Mailing Job Status.
   *
   * @return array
   */
  public static function getMailingJobStatus() {
    return [
      'Draft' => ts('Draft'),
      'Scheduled' => ts('Scheduled'),
      'Running' => ts('Running'),
      'Complete' => ts('Complete'),
      'Paused' => ts('Paused'),
      'Canceled' => ts('Canceled'),
    ];
  }

  /**
   * @return array
   */
  public static function billingMode() {
    return [
      [
        'id' => CRM_Core_Payment::BILLING_MODE_FORM,
        'name' => 'form',
        'label' => 'form',
      ],
      [
        'id' => CRM_Core_Payment::BILLING_MODE_BUTTON,
        'name' => 'button',
        'label' => 'button',
      ],
      [
        'id' => CRM_Core_Payment::BILLING_MODE_NOTIFY,
        'name' => 'notify',
        'label' => 'notify',
      ],
    ];
  }

  /**
   * @return array
   */
  public static function contributeMode() {
    return [
      [
        'id' => CRM_Core_Payment::BILLING_MODE_FORM,
        'name' => 'direct',
        'label' => 'direct',
      ],
      [
        'id' => CRM_Core_Payment::BILLING_MODE_BUTTON,
        'name' => 'directIPN',
        'label' => 'directIPN',
      ],
      [
        'id' => CRM_Core_Payment::BILLING_MODE_NOTIFY,
        'name' => 'notify',
        'label' => 'notify',
      ],
    ];
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
    return [
      'minute' => ts('minute', ['plural' => 'minutes', 'count' => $count]),
      'hour' => ts('hour', ['plural' => 'hours', 'count' => $count]),
      'day' => ts('day', ['plural' => 'days', 'count' => $count]),
      'week' => ts('week', ['plural' => 'weeks', 'count' => $count]),
      'month' => ts('month', ['plural' => 'months', 'count' => $count]),
      'year' => ts('year', ['plural' => 'years', 'count' => $count]),
    ];
  }

  /**
   * Relative Date Terms.
   *
   * @return array
   */
  public static function getRelativeDateTerms() {
    return [
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
    ];
  }

  /**
   * Relative Date Units.
   *
   * @return array
   */
  public static function getRelativeDateUnits() {
    return [
      'year' => ts('Years'),
      'fiscal_year' => ts('Fiscal Years'),
      'quarter' => ts('Quarters'),
      'month' => ts('Months'),
      'week' => ts('Weeks'),
      'day' => ts('Days'),
    ];
  }

  /**
   * Exportable document formats.
   *
   * @return array
   */
  public static function documentFormat() {
    return [
      'pdf' => ts('Portable Document Format (.pdf)'),
      'docx' => ts('MS Word (.docx)'),
      'odt' => ts('Open Office (.odt)'),
      'html' => ts('Webpage (.html)'),
    ];
  }

  /**
   * Application type of document.
   *
   * @return array
   */
  public static function documentApplicationType() {
    return [
      'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'odt' => 'application/vnd.oasis.opendocument.text',
    ];
  }

  /**
   * Activity Text options.
   *
   * @return array
   */
  public static function activityTextOptions() {
    return [
      2 => ts('Details Only'),
      3 => ts('Subject Only'),
      6 => ts('Both'),
    ];
  }

  /**
   * Relationship permissions
   *
   * @return array
   */
  public static function getPermissionedRelationshipOptions() {
    return [
      [
        'id' => CRM_Contact_BAO_Relationship::NONE,
        'name' => 'None',
        'label' => ts('None'),
        'icon' => NULL,
      ],
      [
        'id' => CRM_Contact_BAO_Relationship::VIEW,
        'name' => 'View only',
        'label' => ts('View only'),
        'icon' => 'fa-eye',
      ],
      [
        'id' => CRM_Contact_BAO_Relationship::EDIT,
        'name' => 'View and update',
        'label' => ts('View and update'),
        'icon' => 'fa-pencil-square',
      ],
    ];
  }

  /**
   * Get option values for dashboard entries (used for 'how many events to display on dashboard').
   *
   * @return array
   *   Dashboard entries options - in practice [-1 => 'Show All', 10 => 10, 20 => 20, ... 100 => 100].
   */
  public static function getDashboardEntriesCount() {
    $optionValues = [];
    $optionValues[-1] = ts('show all');
    for ($i = 10; $i <= 100; $i += 10) {
      $optionValues[$i] = $i;
    }
    return $optionValues;
  }

  public static function getQuicksearchOptions(): array {
    $includeEmail = Civi::settings()->get('includeEmailInName');
    $options = [
      [
        'key' => 'sort_name',
        'label' => $includeEmail ? ts('Name/Email') : ts('Name'),
      ],
      [
        'key' => 'id',
        'label' => ts('Contact ID'),
      ],
      [
        'key' => 'external_identifier',
        'label' => ts('External ID'),
      ],
      [
        'key' => 'first_name',
        'label' => ts('First Name'),
      ],
      [
        'key' => 'last_name',
        'label' => ts('Last Name'),
      ],
      [
        'key' => 'email_primary.email',
        'label' => ts('Email'),
        'adv_search_legacy' => 'email',
      ],
      [
        'key' => 'phone_primary.phone_numeric',
        'label' => ts('Phone'),
        'adv_search_legacy' => 'phone_numeric',
      ],
      [
        'key' => 'address_primary.street_address',
        'label' => ts('Street Address'),
        'adv_search_legacy' => 'street_address',
      ],
      [
        'key' => 'address_primary.city',
        'label' => ts('City'),
        'adv_search_legacy' => 'city',
      ],
      [
        'key' => 'address_primary.postal_code',
        'label' => ts('Postal Code'),
        'adv_search_legacy' => 'postal_code',
      ],
      [
        'key' => 'employer_id.sort_name',
        'label' => ts('Current Employer'),
      ],
      [
        'key' => 'job_title',
        'label' => ts('Job Title'),
      ],
    ];
    $customGroups = CRM_Core_BAO_CustomGroup::getAll(['extends' => 'Contact', 'is_active' => TRUE], CRM_Core_Permission::VIEW);
    foreach ($customGroups as $group) {
      foreach ($group['fields'] as $field) {
        if (in_array($field['data_type'], ['Date', 'File', 'ContactReference', 'EntityReference'])) {
          continue;
        }
        $options[] = [
          'key' => $group['name'] . '.' . $field['name'] . ($field['option_group_id'] ? ':label' : ''),
          'label' => $group['title'] . ': ' . $field['label'],
          'adv_search_legacy' => 'custom_' . $field['id'],
        ];
      }
    }
    return $options;
  }

  /**
   * Dropdown options for quicksearch in the menu
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function quicksearchOptions() {
    return array_column(self::getQuicksearchOptions(), 'label', 'key');
  }

  /**
   * Get components (translated for display.
   *
   * @return array
   *
   * @throws \Exception
   */
  public static function getComponentSelectValues() {
    $ret = [];
    $components = CRM_Core_Component::getComponents();
    foreach ($components as $name => $object) {
      $ret[$name] = $object->info['translatedName'];
    }

    return $ret;
  }

  /**
   * @return string[]
   */
  public static function fieldSerialization() {
    return [
      CRM_Core_DAO::SERIALIZE_NONE => 'none',
      CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND => 'separator_bookend',
      CRM_Core_DAO::SERIALIZE_SEPARATOR_TRIMMED => 'separator_trimmed',
      CRM_Core_DAO::SERIALIZE_JSON => 'json',
      CRM_Core_DAO::SERIALIZE_PHP => 'php',
      CRM_Core_DAO::SERIALIZE_COMMA => 'comma',
    ];
  }

  /**
   * @return array
   */
  public static function navigationMenuSeparator() {
    return [
      ts('None'),
      ts('After menu element'),
      ts('Before menu element'),
    ];
  }

  /**
   * @return array
   */
  public static function relationshipOrientation() {
    return [
      'a_b' => ts('A to B'),
      'b_a' => ts('B to A'),
    ];
  }

  /**
   * @return array
   */
  public static function andOr() {
    return [
      'AND' => ts('And'),
      'OR' => ts('Or'),
    ];
  }

  public static function beforeAfter() {
    return [
      'before' => ts('Before'),
      'after' => ts('After'),
    ];
  }

  /**
   * Columns from the option_value table which may or may not be used by each option_group.
   *
   * This is a subset of the full list of optionAttributes
   * @see self::optionAttributes()
   *
   * Note: Value is not listed here as it is not optional.
   *
   * @return string[]
   */
  public static function optionValueFields() {
    return [
      'name' => 'name',
      'label' => 'label',
      'description' => 'description',
      'icon' => 'icon',
      'color' => 'color',
      'grouping' => 'grouping',
    ];
  }

  /**
   * Callback for Role.permissions pseudoconstant values.
   *
   * Permissions for Civi Standalone, not used by CMS-based systems.
   *
   * @return array
   */
  public static function permissions() {
    $perms = $options = [];
    \CRM_Utils_Hook::permissionList($perms);

    foreach ($perms as $machineName => $details) {
      if (!empty($details['is_active'])) {
        $options[$machineName] = $details['title'];
      }
    }
    return $options;
  }

  /**
   * @return array
   *   Array(string $machineName => string $label).
   */
  public static function getPDFLoggingOptions() {
    return [
      'none' => ts('Do not record'),
      'multiple' => ts('Multiple activities (one per contact)'),
      'combined' => ts('One combined activity'),
      'combined-attached' => ts('One combined activity plus one file attachment'),
      // 'multiple-attached' <== not worth the work
    ];
  }

}
