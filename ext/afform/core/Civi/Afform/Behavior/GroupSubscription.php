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
        'label' => E::ts('Opts in and out'),
        'description' => E::ts('Double optin for sign up'),
      ],
      [
        'name' => 'opt-in',
        'label' => E::ts('Only allows opting in'),
        'description' => E::ts('Double optin for sign up'),
      ],
      [
        'name' => 'auto-add',
        'label' => E::ts('Adds contact on submission'),
      ],
      [
        'name' => 'auto-remove',
        'label' => E::ts('Removes contact on submission'),
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
    if (!in_array($subscriptionMode, ['normal', 'opt-in'], TRUE)) {
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
      $groupsToFill = [];
      foreach (array_keys($subscriptionEntity['fields']) as $fieldName) {
        if (str_starts_with($fieldName, 'group_')) {
          $groupsToFill[] = explode('_', $fieldName)[1];
        }
      }
      if (!$groupsToFill) {
        return;
      }

      // $currentContactGroups = \Civi\Api4\GroupContact::get(FALSE)
      //   ->addSelect('group_id')
      //   ->addWhere('contact_id', '=', $contactId)
      //   ->addWhere('status', '!=', 'Removed')
      //   ->addWhere('group_id', 'IN', $groupsToFill)
      //   ->execute()->column('group_id');

      $groupSubscriptions = \Civi\Api4\GroupSubscription::get(FALSE)
        ->addWhere('contact_id', '=', $cid)
        ->execute()
        ->first();

      // HMM, I got this far and now I think the above logic needs to be moved into the
      // GroupSubscription::get action, but I also think that entity could be standardized a bit
      // more and ought to extend BasicEntity so it has all the expected CRUD actions...
    }
  }

  public static function onAfformSubmit(AfformSubmitEvent $event) {
    if ($event->getEntityType() !== 'GroupSubscription') {
      return;
    }

    $subscriptionEntity = $event->getEntity();
    $subscriptionMode = $subscriptionEntity['group-subscription'];

    $submittedValues = $event->getRecords();
    $submittedValues['subscription-mode'] = $subscriptionMode;
    $event->setRecords($submittedValues);
  }

}
