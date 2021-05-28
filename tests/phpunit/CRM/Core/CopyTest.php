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

    $otherLocale = 'fr_CA';
    $locSuffix = " ({$otherLocale})";

    $this->enableMultilingual();
    CRM_Core_I18n_Schema::addLocale($otherLocale, 'en_US');

    CRM_Core_I18n::singleton()->setLocale('en_US');

    $event = $this->eventCreate();
    $eventId = $event['id'];
    $eventData = civicrm_api3('Event', 'getsingle', ['id' => $eventId]);

    // change localizable fields
    $locParams = [
      'summary',
      'description',
    ];

    CRM_Core_I18n::singleton()->setLocale($otherLocale);
    $ploc = ['id' => $eventId];
    foreach ($locParams as $field) {
      $ploc[$field] = $eventData[$field] . $locSuffix;
    }
    $this->callAPISuccess('Event', 'create', $ploc);

    CRM_Core_I18n::singleton()->setLocale('en_US');
    $eventCopy = CRM_Event_BAO_Event::copy($eventId);
    $eventCopyId = $eventCopy->id;

    $identicalParams = [
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

    // en_US should be as the original
    CRM_Core_I18n::singleton()->setLocale('en_US');
    $eventCopy = civicrm_api3('Event', 'getsingle', ['id' => $eventCopyId]);
    foreach ($identicalParams as $name) {
      $this->assertEquals($eventCopy[$name], $eventData[$name]);
    }
    // title is special
    $this->assertEquals($eventCopy['title'], 'Copy of ' . $eventData['title']);

    // localized fields should be different
    CRM_Core_I18n::singleton()->setLocale($otherLocale);
    $eventCopy = civicrm_api3('Event', 'getsingle', ['id' => $eventCopyId]);
    foreach ($identicalParams as $name) {
      $this->assertEquals($eventCopy[$name], $eventData[$name]);
    }
    foreach ($locParams as $name) {
      $this->assertEquals($eventCopy[$name], $eventData[$name] . $locSuffix);
    }
    // title is special
    $this->assertEquals($eventCopy['title'], 'Copy of ' . $eventData['title']);

    // reset to en_US only
    CRM_Core_I18n::singleton()->setLocale('en_US');
    CRM_Core_I18n_Schema::makeSinglelingual('en_US');

  }

}
