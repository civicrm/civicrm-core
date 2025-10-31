<?php
namespace Civi\Afform\Behavior;

use Civi\Afform\AbstractBehavior;
use Civi\Afform\Event\AfformPrefillEvent;
use Civi\Token\TokenRow;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use CRM_Afform_ExtensionUtil as E;

/**
 * @service
 * @internal
 */
class ActivityAutofill extends AbstractBehavior implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.afform.prefill' => ['onAfformPrefill', 99],
      '&civi.afform.createToken' => ['onCreateToken', 99],
    ];
  }

  public static function getEntities(): array {
    return ['Activity'];
  }

  public static function getTitle():string {
    return E::ts('Autofill');
  }

  public static function getDescription(): string {
    return E::ts('Automatically identify this activity.');
  }

  public static function getModes(string $type): array {
    $modes = [];
    if ($type == 'Activity') {
      $modes[] = [
        'name' => 'entity_id',
        'label' => E::ts('Activity being Viewed'),
        'description' => E::ts('For use on the activity page'),
        'icon' => 'fa-tasks',
      ];
    }
    return $modes;
  }

  public static function onAfformPrefill(AfformPrefillEvent $event): void {
    /* @var \Civi\Api4\Action\Afform\Prefill $apiRequest */
    $apiRequest = $event->getApiRequest();
    if ($event->getEntityType() == 'Activity') {
      $entity = $event->getEntity();
      $id = $event->getEntityId();
      $autoFillMode = $entity['activity-autofill'] ?? '';
      // Autofill with current entity (e.g. on the activity page)
      if (!$id && $autoFillMode === 'entity_id' && $apiRequest->getFillMode() === 'form') {
        $id = $apiRequest->getArgs()['activity_id'] ?? NULL;
        if ($id) {
          $apiRequest->loadEntity($entity, [['id' => $id]]);
        }
      }
    }
  }

  public function onCreateToken(TokenRow $row, array &$afformArgs) {
    if (!empty($row->context['activityId'])) {
      $afformArgs['activity_id'] = $row->context['activityId'];
    }
  }

}
