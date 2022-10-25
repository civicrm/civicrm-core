<?php
namespace Civi\Afform\Behavior;

use Civi\Afform\AbstractBehavior;
use Civi\Afform\Event\AfformPrefillEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use CRM_Afform_ExtensionUtil as E;

/**
 * @service
 * @internal
 */
class ContactAutofill extends AbstractBehavior implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.afform.prefill' => ['onAfformPrefill', 99],
    ];
  }

  public static function getEntities():array {
    return ['Individual'];
  }

  public static function getTitle():string {
    return E::ts('Autofill');
  }

  public static function getKey():string {
    // Would be contact-autofill but this supports legacy afforms from before this was converted to a behavior
    return 'autofill';
  }

  public static function getDescription():string {
    return E::ts('Automatically identify this contact');
  }

  public static function getModes(string $entityName):array {
    $modes = [];
    $modes[] = [
      'name' => 'user',
      'label' => E::ts('Current User'),
    ];
    return $modes;
  }

  public static function onAfformPrefill(AfformPrefillEvent $event) {
    if ($event->getEntityType() === 'Contact') {
      $entity = $event->getEntity();
      $id = $event->getEntityId();
      // Autofill with current user
      if (!$id && ($entity['autofill'] ?? NULL) === 'user') {
        $id = \CRM_Core_Session::getLoggedInContactID();
        if ($id) {
          $event->getApiRequest()->loadEntity($entity, [$id]);
        }
      }
    }
  }

}
