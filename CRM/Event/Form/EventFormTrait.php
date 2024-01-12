<?php

use Civi\API\EntityLookupTrait;

/**
 * Trait implements getContactValue + overridable getContactID functions.
 *
 * These are commonly used on forms - although getContactID() would often
 * be overridden. By using these functions it is not necessary to know
 * if the Contact ID has already been defined as `getContactID()` will retrieve
 * them form the values available (unless it is yet to be created).
 */
trait CRM_Event_Form_EventFormTrait {

  use EntityLookupTrait;

  /**
   * Get the value for a field relating to the event.
   *
   * All values returned in apiv4 format. Escaping may be required.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @param string $fieldName
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  public function getEventValue(string $fieldName) {
    if ($this->isDefined('Event')) {
      if ($fieldName === 'available_spaces') {
        // This is temporary. Apiv4 returns available_spaces as a calculated field.
        // However, there is not total parity between it and the old function.
        // That needs to be worked through - per https://lab.civicrm.org/dev/core/-/issues/4907
        // In order to allow the forms to switch over to the 'final' signature we
        // re-direct to the old function for now.
        return $this->getAvailableSpaces();
      }
      return $this->lookup('Event', $fieldName);
    }
    $id = $this->getEventID();
    if ($id) {
      $this->define('Event', 'Event', ['id' => $id]);
      return $this->lookup('Event', $fieldName);
    }
    return NULL;
  }

  /**
   * Get the selected Event ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function getEventID(): ?int {
    throw new CRM_Core_Exception('`getEventID` must be implemented');
  }

  /**
   * Get id of participant being acted on.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   */
  public function getParticipantID(): ?int {
    throw new CRM_Core_Exception('`getParticipantID` must be implemented');
  }

  /**
   * Get a value from the participant being acted on.
   *
   * All values returned in apiv4 format. Escaping may be required.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @param string $fieldName
   *
   * @return mixed
   *
   * @throws \CRM_Core_Exception
   */
  public function getParticipantValue(string $fieldName) {
    if ($this->isDefined('Participant')) {
      return $this->lookup('Participant', $fieldName);
    }
    $id = $this->getParticipantID();
    if ($id) {
      $this->define('Participant', 'Participant', ['id' => $id]);
      return $this->lookup('Participant', $fieldName);
    }
    return NULL;
  }

  /**
   * Get the number of available spaces in the given event.
   *
   * @internal this is a transitional function - eventually it will be removed
   * and the api will handle it.
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  protected function getAvailableSpaces(): int {
    $availableSpaces = CRM_Event_BAO_Participant::eventFull($this->getEventID(),
      TRUE,
      $this->getEventValue('has_waitlist')
    );
    return is_numeric($availableSpaces) ? (int) $availableSpaces : 0;
  }

}
