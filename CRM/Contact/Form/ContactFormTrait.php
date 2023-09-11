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
trait CRM_Contact_Form_ContactFormTrait {

  use EntityLookupTrait;

  /**
   * Get a value for the contact being acted on in the form.
   *
   * This can be called from any point in the form flow and if
   * the contact can not yet be determined it will return NULL.
   *
   * @throws \CRM_Core_Exception
   */
  public function getContactValue($fieldName) {
    if ($this->isDefined('Contact')) {
      return $this->lookup('Contact', $fieldName);
    }
    $id = $this->getContactID();
    if ($id) {
      $this->define('Contact', 'Contact', ['id' => $id]);
      return $this->lookup('Contact', $fieldName);
    }
    return NULL;
  }

  /**
   * Get the contact ID.
   *
   * Override this for more complex retrieval as required by the form.
   *
   * @return int|null
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function getContactID(): ?int {
    $id = (int) CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    return $id ?: NULL;
  }

}
