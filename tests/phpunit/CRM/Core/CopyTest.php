<?php

/**
 * @group headless
 */
class CRM_Core_CopyTest extends CiviUnitTestCase {

  public function testEventCopy() {

    $event = $this->eventCreate();
    $eventId = $event['id'];
    $eventRes = $event['values'][$eventId];
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
    ];

    foreach ($identicalParams as $name) {
      $this->assertEquals($eventCopy->$name, $eventRes[$name]);
    }

    $this->assertEquals($eventCopy->title, 'Copy of ' . $eventRes['title']);

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

      $res = $this->callAPISuccess('Event', 'create', $ploc);
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
      $this->assertEquals($dest[$name], $source[$name]);
    }
    foreach ($locParams as $name) {
      $this->assertEquals($dest[$name], $source[$name] . $locSuffix);
    }

  }

}
