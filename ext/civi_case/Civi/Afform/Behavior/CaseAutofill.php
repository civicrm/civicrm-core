<?php
namespace Civi\Afform\Behavior;

use Civi\Afform\AbstractBehavior;
use Civi\Afform\Event\AfformPrefillEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use CRM_Case_ExtensionUtil as E;

/**
 * @service
 * @internal
 */
class CaseAutofill extends AbstractBehavior implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.afform.prefill' => ['onAfformPrefill', 99],
    ];
  }

  public static function getEntities():array {
    return ['Case'];
  }

  public static function getTitle():string {
    return E::ts('Autofill');
  }

  public static function getDescription():string {
    return E::ts('Automatically identify this case based on the case being viewed when this form is placed on the case summary screen or when email with the link to the form is send from the Case.');
  }

  public static function getModes(string $type):array {
    $modes = [];
    if ($type == 'Case') {
      $modes[] = [
        'name' => 'entity_id',
        'label' => E::ts('Case being Viewed'),
        'description' => E::ts('For use on the case summary page'),
        'icon' => 'fa-folder-open',
      ];
    }
    return $modes;
  }

  public static function onAfformPrefill(AfformPrefillEvent $event): void {
    /* @var \Civi\Api4\Action\Afform\Prefill $apiRequest */
    $apiRequest = $event->getApiRequest();
    if ($event->getEntityType() == 'Case') {
      $entity = $event->getEntity();
      $id = $event->getEntityId();
      $autoFillMode = $entity['case-autofill'] ?? '';
      // Autofill with current entity (e.g. on the case summary screen)
      if (!$id && $autoFillMode === 'entity_id' && $apiRequest->getFillMode() === 'form') {
        $id = $apiRequest->getArgs()['case_id'] ?? NULL;
        if ($id) {
          $apiRequest->loadEntity($entity, [['id' => $id]]);
        }
      }
    }
  }

}
