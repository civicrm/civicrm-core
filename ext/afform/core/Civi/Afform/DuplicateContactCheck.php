<?php
namespace Civi\Afform;

/**
 * Settings for the client-side "similar contacts" check.
 *
 * Mirrors the check on the standard contact form, which is gated on the same
 * setting and compares the same fields.
 */
class DuplicateContactCheck {

  /**
   * Contact fields compared against existing contacts. Email is handled
   * separately, as it comes from a joined Email entity.
   *
   * @var string[]
   */
  const CONTACT_FIELDS = [
    'first_name',
    'last_name',
    'organization_name',
    'household_name',
  ];

  const MIN_LENGTH = 3;

  /**
   * @return array
   */
  public static function getSettings(): array {
    $enabled = (bool) \Civi::settings()->get('contact_ajax_check_similar')
      && \CRM_Core_Permission::check('access CiviCRM');

    if (!$enabled) {
      return ['checkSimilarContacts' => FALSE];
    }

    return [
      'checkSimilarContacts' => TRUE,
      'checkSimilarContactFields' => self::CONTACT_FIELDS,
      'checkSimilarMinLength' => self::MIN_LENGTH,
      'contactEntityTypes' => array_merge(['Contact'], \CRM_Contact_BAO_ContactType::basicTypes()),
    ];
  }

}
