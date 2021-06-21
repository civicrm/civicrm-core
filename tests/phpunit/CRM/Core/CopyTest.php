<?php

/**
 * @group headless
 */
class CRM_Core_CopyTest extends CiviUnitTestCase {

  /**
   * Has the test class been verified as 'getsafe'.
   *
   * If a class is getsafe it means that where
   * callApiSuccess is called 'return' is specified or 'return' =>'id'
   * can be added by that function. This is part of getting away
   * from open-ended get calls.
   *
   * @var bool
   */
  protected $isGetSafe = TRUE;

  use CRMTraits_Custom_CustomDataTrait;

  public function testEventCopy(): void {

    $this->createCustomGroupWithFieldOfType(['extends' => 'Event']);
    $event = $this->eventCreate([$this->getCustomFieldName('text') => 'blah']);
    $eventId = $event['id'];
    $eventRes = $event['values'][$eventId];
    $params[$this->getCustomFieldName('text') . '_1'] = 'blah';
    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
      $eventId,
      'Event'
    );
    $eventCopy = CRM_Event_BAO_Event::copy($eventId);

    $identicalParams = [
      'summary',
      'description',
      'event_type_id',
      'is_public',
      'start_date',
      'end_date',
      'is_online_registration',
      'registration_start_date',
      'registration_end_date',
      'max_participants',
      'event_full_text',
      'is_monetary',
      'is_active',
      'is_show_location',
      'is_email_confirm',
      $this->getCustomFieldName('text'),
    ];

    // same format for better comparison
    $eventData = $this->callAPISuccessGetSingle('Event', ['id' => $eventId, 'return' => array_merge($identicalParams, ['title'])]);
    $eventCopy = $this->callAPISuccessGetSingle('Event', ['id' => $eventCopy->id, 'return' => array_merge($identicalParams, ['title'])]);

    foreach ($identicalParams as $name) {
      $this->assertEquals($eventCopy[$name], $eventData[$name], "{$name} should be equals between source and copy");
    }

    $this->assertEquals($eventCopy['title'], 'Copy of ' . $eventRes['title']);

  }

  public function testI18nEventCopy() {

    $locales = ['en_US', 'fr_CA', 'nl_NL'];

    $this->enableMultilingual();
    CRM_Core_I18n_Schema::addLocale('fr_CA', 'en_US');
    CRM_Core_I18n_Schema::addLocale('nl_NL', 'en_US');

    CRM_Core_I18n::singleton()->setLocale('en_US');

    $event = $this->eventCreate();
    $eventId = $event['id'];
    $eventData = civicrm_api3('Event', 'getsingle', ['id' => $eventId]);

    // change localizable fields
    $locParams = [
      'summary',
      'description',
      'event_full_text',
      'registration_link_text',
      'fee_label',
      'intro_text',
      'footer_text',
      'confirm_title',
      'confirm_text',
      'confirm_footer_text',
      'confirm_email_text',
      'confirm_from_name',
      'thankyou_title',
      'thankyou_text',
      'thankyou_footer_text',
      'pay_later_text',
      'pay_later_receipt',
      'initial_amount_label',
      'initial_amount_help_text',
    ];

    // init in case it's not defined
    foreach ($locParams as $field) {
      $eventData[$field] = $eventData[$field] ?? '';
    }

    // differencing the data in original content for each locales
    foreach ($locales as $locale) {
      CRM_Core_I18n::singleton()->setLocale($locale);
      $locSuffix = " ({$locale})";
      $ploc = ['id' => $eventId];
      foreach ($locParams as $field) {
        $ploc[$field] = $eventData[$field] . $locSuffix;
      }

      $this->callAPISuccess('Event', 'create', $ploc);
    }

    // now that the data is different, do the copy
    CRM_Core_I18n::singleton()->setLocale('en_US');
    $eventCopy = CRM_Event_BAO_Event::copy($eventId);
    $eventCopyId = $eventCopy->id;

    // define the fields that doesn't change
    $identicalParams = [
      'event_type_id',
      'is_public',
      'start_date',
      'end_date',
      'is_online_registration',
      'registration_start_date',
      'registration_end_date',
      'max_participants',
      'is_monetary',
      'is_active',
      'is_show_location',
      'is_email_confirm',
    ];

    // check the data on the copy
    foreach ($locales as $locale) {
      CRM_Core_I18n::singleton()->setLocale($locale);
      $locSuffix = " ({$locale})";
      $eventCopy = civicrm_api3('Event', 'getsingle', ['id' => $eventCopyId]);

      // title is special
      $this->assertEquals($eventCopy['title'], 'Copy of ' . $eventData['title']);

      // other fields
      $this->compareLocalizedCopy($eventData, $eventCopy, $locParams, $identicalParams, $locSuffix);
    }

    // reset to en_US only
    CRM_Core_I18n::singleton()->setLocale('en_US');
    CRM_Core_I18n_Schema::makeSinglelingual('en_US');

  }

  protected function compareLocalizedCopy($source, $dest, $locParams, $identicalParams, $locSuffix) {

    foreach ($identicalParams as $name) {
      $this->assertEquals($dest[$name], $source[$name], "{$name} should be equals between source and copy");
    }
    foreach ($locParams as $name) {
      $this->assertEquals($dest[$name], $source[$name] . $locSuffix, "copy of {$name} is not properly localized");
    }

  }

}
