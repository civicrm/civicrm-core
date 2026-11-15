<?php
namespace Civi\Afform\Behavior;

use Civi\Core\Attribute\CiviEventListener;
use Civi\Afform\AbstractBehavior;
use Civi\Afform\Event\AfformPrefillEvent;
use Civi\Token\TokenRow;
use CRM_Afform_ExtensionUtil as E;

/**
 * @service
 * @internal
 */
class EventAutofill extends AbstractBehavior {

  public static function getEntities(): array {
    return ['Event'];
  }

  public static function getTitle():string {
    return E::ts('Autofill');
  }

  public static function getDescription(): string {
    return E::ts('Automatically identify this event.');
  }

  public static function getModes(string $entityName): array {
    $modes = [];
    if ($entityName === 'Event') {
      $modes[] = [
        'name' => 'entity_id',
        'label' => E::ts('Event being Viewed'),
        'description' => E::ts('For use on the event page'),
        'icon' => 'fa-tasks',
      ];
    }
    return $modes;
  }

  #[CiviEventListener(event: 'civi.afform.prefill', priority: 99)]
  public static function onAfformPrefill(AfformPrefillEvent $event): void {
    /* @var \Civi\Api4\Action\Afform\Prefill $apiRequest */
    $apiRequest = $event->getApiRequest();
    if ($event->getEntityType() === 'Event') {
      $entity = $event->getEntity();
      $id = $event->getEntityId();
      $autoFillMode = $entity['event-autofill'] ?? '';
      // Autofill with current entity (e.g. on the event page)
      if (!$id && $autoFillMode === 'entity_id' && $apiRequest->getFillMode() === 'form') {
        $id = $apiRequest->getArgs()['event_id'] ?? NULL;
        if ($id) {
          $apiRequest->loadEntity($entity, [['id' => $id]]);
        }
      }
    }
  }

  #[CiviEventListener(event: '&civi.afform.createToken', priority: 99)]
  public function onCreateToken(TokenRow $row, array &$afformArgs) {
    if (!empty($row->context['eventId'])) {
      $afformArgs['event_id'] = $row->context['eventId'];
    }
  }

}
