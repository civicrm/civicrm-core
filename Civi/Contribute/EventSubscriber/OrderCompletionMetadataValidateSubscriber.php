<?php
declare(strict_types = 1);

namespace Civi\Contribute\EventSubscriber;

use Civi\Core\Event\PreEvent;
use Civi\Core\Service\AutoSubscriber;

/**
 * Rejects OrderCompletionMetadata writes containing keys we don't yet
 * consume anywhere.
 *
 * The metadata format is deliberately free-form (see
 * Civi\Api4\OrderCompletionMetadata) but that freedom makes it easy for a
 * caller to persist a key that no consumer reads - and, once live, upgrade
 * scripts would have no reliable way to know what to do with unrecognised
 * data sitting in old rows. Keeping this allow-list in sync with the keys
 * actually read (CRM_Contribute_BAO_Contribution::completeOrder() for
 * 'email', Civi\Membership\OrderCompleteSubscriber for 'entity') lets us
 * extend the format later without carrying that risk now.
 */
final class OrderCompletionMetadataValidateSubscriber extends AutoSubscriber {

  private const ALLOWED_KEYS = [
    'email' => ['userMessageText', 'is_send_receipt'],
    'entity' => ['join_date', 'start_date', 'end_date'],
  ];

  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_pre' => 'onPre',
    ];
  }

  public function onPre(PreEvent $event): void {
    if ($event->entity !== 'OrderCompletionMetadata' || !in_array($event->action, ['create', 'edit'], TRUE)) {
      return;
    }
    foreach ((array) ($event->params['metadata'] ?? []) as $topLevelKey => $value) {
      if (!array_key_exists($topLevelKey, self::ALLOWED_KEYS)) {
        throw new \CRM_Core_Exception("Unrecognised OrderCompletionMetadata key '{$topLevelKey}'. Known keys are: " . implode(', ', array_keys(self::ALLOWED_KEYS)));
      }
      $unknownSubKeys = array_diff(array_keys((array) $value), self::ALLOWED_KEYS[$topLevelKey]);
      if ($unknownSubKeys) {
        throw new \CRM_Core_Exception("Unrecognised OrderCompletionMetadata['{$topLevelKey}'] key(s): " . implode(', ', $unknownSubKeys) . '. Known keys are: ' . implode(', ', self::ALLOWED_KEYS[$topLevelKey]));
      }
    }
  }

}
