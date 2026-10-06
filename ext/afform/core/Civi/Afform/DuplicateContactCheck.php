<?php
namespace Civi\Afform;

use Civi\Afform\Behavior\ContactDupeCheck;

/**
 * Settings for the client-side "similar contacts" check.
 *
 * Which fields are watched comes from the dedupe rule selected per entity by the
 * ContactDupeCheck behavior, as on the standard contact form.
 */
class DuplicateContactCheck {

  const MIN_LENGTH = 3;

  /**
   * @return array
   */
  public static function getSettings(): array {
    // The lookup itself is ACL-checked; this just avoids firing requests that
    // cannot return anything, e.g. for an anonymous user on a public form.
    if (!\CRM_Core_Permission::check('access CiviCRM')) {
      return ['checkSimilarContacts' => FALSE];
    }

    return [
      'checkSimilarContacts' => TRUE,
      'checkSimilarMinLength' => self::MIN_LENGTH,
      'dedupeRuleFields' => ContactDupeCheck::getRuleFields(),
    ];
  }

}
