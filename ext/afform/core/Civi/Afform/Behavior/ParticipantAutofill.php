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
class ParticipantAutofill extends AbstractBehavior {

  public static function getEntities(): array {
    return ['Participant'];
  }

  public static function getTitle():string {
    return E::ts('Autofill');
  }

  public static function getDescription(): string {
    return E::ts('Automatically identify this participant.');
  }

  public static function getModes(string $entityName): array {
    $modes = [];
    if ($entityName === 'Participant') {
      $modes[] = [
        'name' => 'entity_id',
        'label' => E::ts('Participant being Viewed'),
        'description' => E::ts('For use on the participant page'),
        'icon' => 'fa-tasks',
      ];
    }
    return $modes;
  }

  #[CiviEventListener(event: 'civi.afform.prefill', priority: 99)]
  public static function onAfformPrefill(AfformPrefillEvent $event): void {
    /* @var \Civi\Api4\Action\Afform\Prefill $apiRequest */
    $apiRequest = $event->getApiRequest();
    if ($event->getEntityType() === 'Participant') {
      $entity = $event->getEntity();
      $id = $event->getEntityId();
      $autoFillMode = $entity['participant-autofill'] ?? '';
      // Autofill with current entity (e.g. on the participant page)
      if (!$id && $autoFillMode === 'entity_id' && $apiRequest->getFillMode() === 'form') {
        $id = $apiRequest->getArgs()['participant_id'] ?? NULL;
        if ($id) {
          $apiRequest->loadEntity($entity, [['id' => $id]]);
        }
      }
    }
  }

  #[CiviEventListener(event: '&civi.afform.createToken', priority: 99)]
  public function onCreateToken(TokenRow $row, array &$afformArgs) {
    if (!empty($row->context['participantId'])) {
      $afformArgs['participant_id'] = $row->context['participantId'];
    }
  }

}
