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

  public static function getEntities():array {
    return ['GroupSubscription'];
  }

  public static function getTitle():string {
    return E::ts('Configuration');
  }

  public static function getDescription():string {
    return E::ts('Configue subscription behavior.');
  }

  public static function getDefaultMode(): string {
    return 'normal';
  }

  public static function getModes(string $contactType):array {
    $modes = [
      [
        'name' => 'normal',
        'label' => E::ts('Opt-In & Out'),
        'description' => E::ts('Double optin for sign up'),
      ],
      [
        'name' => 'opt-in',
        'label' => E::ts('Opt-In Only'),
        'description' => E::ts('Double optin for sign up'),
      ],
      [
        'name' => 'auto-add',
        'label' => E::ts('Auto-Add'),
        'description' => E::ts('Adds contact to group(s) on submission'),
      ],
      [
        'name' => 'auto-remove',
        'label' => E::ts('Auto-Remove'),
        'description' => E::ts('Removes contact from group(s) on submission'),
      ],

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
    $subscriptionMode = $subscriptionEntity['group-subscription'];
    if ($subscriptionMode !== 'normal') {
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
      $event->getApiRequest()->loadEntity($subscriptionEntity, [$cid]);
    }
  }

  public static function onAfformSubmit(AfformSubmitEvent $event) {
    if ($event->getEntityType() !== 'GroupSubscription') {
      return;
    }
    $event->stopPropagation();

    $subscriptionEntity = $event->getEntity();
    $subscriptionMode = $subscriptionEntity['group-subscription'];

    $submittedValues = $event->getRecords()[0]['fields'] ?? [];
    // Treat contact_id as multivalued in case contact uses af-repeat on the form
    $contactIds = array_filter((array) ($submittedValues['contact_id'] ?? []));
    unset($submittedValues['contact_id']);

    // Only "normal" mode allows both opt-in & out. In other modes, discard FALSE values.
    if ($subscriptionMode !== 'normal') {
      $submittedValues = array_filter($submittedValues);
    }
    if (!$contactIds || !$submittedValues) {
      // Nothing to do
      return;
    }
    // Invert values in "auto-remove" mode
    if ($subscriptionMode === 'auto-remove') {
      $submittedValues = array_fill_keys(array_keys($submittedValues), FALSE);
    }
    $contactSubscriptions = [];
    foreach ($contactIds as $cid) {
      $submittedValues['contact_id'] = $cid;
      $contactSubscriptions[] = $submittedValues;
    }

    \Civi\Api4\GroupSubscription::save(FALSE)
      ->setRecords($contactSubscriptions)
      ->setDoubleOptin($subscriptionMode === 'normal' || $subscriptionMode === 'opt-in')
      ->execute();
  }

}
