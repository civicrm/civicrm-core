<?php
namespace Civi\Afform\Behavior;

use Civi\Afform\AbstractBehavior;
use Civi\Afform\Event\AfformEntitySortEvent;
use Civi\Afform\Event\AfformPrefillEvent;
use Civi\Afform\Event\AfformSubmitEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use CRM_Afform_ExtensionUtil as E;

/**
 * @service
 * @internal
 */
class GroupSubscription extends AbstractBehavior implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.afform.sort.prefill' => 'onAfformSortPrefill',
      'civi.afform.prefill' => ['onAfformPrefill', 99],
      'civi.afform.submit' => ['onAfformSubmit', 101],
    ];
  }

  public static function getEntities(): array {
    return ['GroupSubscription'];
  }

  public static function getTitle(): string {
    return E::ts('Email Verification');
  }

  public static function getDescription(): string {
    if (!\CRM_Core_Component::isEnabled('CiviMail')) {
      return E::ts('Email verification is not available because CiviMail is disabled.');
    }
    return E::ts("Verify the contact's email by sending them a link to confirm their subscription (recommended for public forms, requires an email input on the form).");
  }

  public static function getDefaultMode(): string {
    return \CRM_Core_Component::isEnabled('CiviMail') ? 'double-opt-in' : 'no-confirm';
  }

  public static function getModes(string $contactType): array {
    $modes = [];
    if (\CRM_Core_Component::isEnabled('CiviMail')) {
      $modes[] = [
        'name' => 'double-opt-in',
        'label' => E::ts('Send Confirmation Email'),
        'description' => E::ts('Double opt-in for sign up'),
      ];
    }
    $modes[] = [
      'name' => 'no-confirm',
      'label' => E::ts('No Confirmation'),
      'description' => E::ts('Contact added to group immediately'),
    ];
    return $modes;
  }

  public static function onAfformSortPrefill(AfformEntitySortEvent $event): void {
    $formEntities = $event->getFormDataModel()->getEntities();
    foreach ($formEntities as $entityName => $entity) {
      if ($entity['type'] === 'GroupSubscription') {
        if (isset($formEntities[$entity['data']['contact_id']])) {
          $event->addDependency($entityName, $entity['data']['contact_id']);
        }
      }
    }
  }

  public static function onAfformPrefill(AfformPrefillEvent $event): void {
    if ($event->getEntityType() !== 'GroupSubscription') {
      return;
    }
    $subscriptionEntity = $event->getEntity();
    if (empty($subscriptionEntity['actions']['update'])) {
      return;
    }
    $contact = $subscriptionEntity['data']['contact_id'];
    if ($contact === 'user_contact_id') {
      $cid = \CRM_Core_Session::getLoggedInContactID();
    }
    elseif ($contact && \CRM_Utils_Rule::positiveInteger($contact)) {
      $cid = $contact;
    }
    else {
      $cid = $event->getEntityIds($contact)[0] ?? NULL;
    }
    if ($cid) {
      $event->getApiRequest()->loadEntity($subscriptionEntity, [['contact_id' => $cid]]);
    }
  }

  public static function onAfformSubmit(AfformSubmitEvent $event) {
    if ($event->getEntityType() !== 'GroupSubscription') {
      return;
    }
    $event->stopPropagation();

    $subscriptionEntity = $event->getEntity();
    $subscriptionMode = $subscriptionEntity['group-subscription'];
    $optInAllowed = !empty($subscriptionEntity['actions']['create']);
    $optOutAllowed = !empty($subscriptionEntity['actions']['update']);
    if (!$optOutAllowed && !$optInAllowed) {
      // Read-only, nothing to do
      return;
    }

    $submittedValues = $event->getRecords()[0]['fields'] ?? [];
    // Treat contact_id as multivalued in case contact uses af-repeat on the form
    $contactIds = array_filter((array) ($submittedValues['contact_id'] ?? []));
    unset($submittedValues['contact_id']);

    // If opt-out is not allowed, discard FALSE values.
    if (!$optOutAllowed) {
      $submittedValues = array_filter($submittedValues);
    }
    if (!$contactIds || !$submittedValues) {
      // Nothing to do
      return;
    }
    $contactSubscriptions = [];
    foreach ($contactIds as $cid) {
      $submittedValues['contact_id'] = $cid;
      $contactSubscriptions[] = $submittedValues;
    }

    \Civi\Api4\GroupSubscription::save(FALSE)
      ->setRecords($contactSubscriptions)
      ->setMethod('Form')
      ->setTracking(\CRM_Utils_System::ipAddress(FALSE))
      ->setDoubleOptin($subscriptionMode === 'double-opt-in')
      ->execute();
  }

}
