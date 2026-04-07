<?php
namespace Civi\Afform\Behavior;

use Civi\Afform\AbstractBehavior;
use Civi\Afform\Event\AfformEntitySortEvent;
use Civi\Afform\Event\AfformPrefillEvent;
use Civi\Api4\Utils\CoreUtil;
use Civi\Token\TokenRow;
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
      'civi.afform.sort.prefill' => 'onAfformSortPrefill',
      'civi.afform.prefill' => ['onAfformPrefill', 99],
      '&civi.afform.createToken' => ['onCreateToken', 99],
    ];
  }

  public static function getEntities():array {
    return \CRM_Contact_BAO_ContactType::basicTypes();
  }

  public static function getTitle():string {
    return E::ts('Autofill');
  }

  public static function getKey():string {
    // Would be contact-autofill but this supports legacy afforms from before this was converted to a behavior
    return 'autofill';
  }

  public static function getDescription():string {
    return E::ts('Automatically identify this contact based on logged-in status or relationship to another contact on the form.');
  }

  public static function getTemplate(): ?string {
    return '~/afGuiEditor/behaviors/autofillRelationshipBehavior.html';
  }

  public static function getModes(string $entityName):array {
    $modes = [];
    if ($entityName === 'Individual') {
      $modes[] = [
        'name' => 'user',
        'label' => E::ts('Current User'),
        'description' => E::ts('Auto-select logged-in user'),
        'icon' => 'fa-user-circle',
      ];
    }
    $modes[] = [
      'name' => 'entity_id',
      'label' => E::ts('Contact being Viewed'),
      'description' => E::ts('For use on the contact summary page'),
      'icon' => 'fa-address-card-o',
    ];
    $relationshipTypes = \Civi\Api4\RelationshipType::get(FALSE)
      ->addSelect('name_a_b', 'name_b_a', 'label_a_b', 'label_b_a', 'description', 'contact_type_a', 'contact_type_b')
      ->addWhere('is_active', '=', TRUE)
      ->addClause('OR', ['contact_type_a', '=', $entityName], ['contact_type_a', 'IS NULL'], ['contact_type_b', '=', $entityName], ['contact_type_b', 'IS NULL'])
      ->execute();
    foreach ($relationshipTypes as $relationshipType) {
      if (!$relationshipType['contact_type_a'] || $relationshipType['contact_type_a'] === $entityName) {
        $modes[] = [
          'name' => 'relationship:' . $relationshipType['name_a_b'],
          'label' => $relationshipType['label_a_b'],
          'description' => $relationshipType['description'],
          'icon' => 'fa-handshake-o',
          'contact_type' => $relationshipType['contact_type_b'],
        ];
      }
      if (
        $relationshipType['name_b_a'] && $relationshipType['name_a_b'] != $relationshipType['name_b_a'] &&
        (!$relationshipType['contact_type_b'] || $relationshipType['contact_type_b'] === $entityName)
      ) {
        $modes[] = [
          'name' => 'relationship:' . $relationshipType['name_b_a'],
          'label' => $relationshipType['label_b_a'],
          'description' => $relationshipType['description'],
          'icon' => 'fa-handshake-o',
          'contact_type' => $relationshipType['contact_type_a'],
        ];
      }
    }
    return $modes;
  }

  public static function onAfformSortPrefill(AfformEntitySortEvent $event): void {
    foreach ($event->getFormDataModel()->getEntities() as $entityName => $entity) {
      $autoFillMode = $entity['autofill'] ?? '';
      $relatedContact = $entity['autofill-relationship'] ?? NULL;
      if ($relatedContact && str_starts_with($autoFillMode, 'relationship:')) {
        $event->addDependency($entityName, $relatedContact);
      }
    }
  }

  public static function onAfformPrefill(AfformPrefillEvent $event): void {
    /* @var \Civi\Api4\Action\Afform\Prefill $apiRequest */
    $apiRequest = $event->getApiRequest();
    if (CoreUtil::isContact($event->getEntityType())) {
      $entity = $event->getEntity();
      $id = $event->getEntityId();
      $autoFillMode = $entity['autofill'] ?? '';
      $relatedContact = $entity['autofill-relationship'] ?? NULL;
      // Autofill with current user, but only if this is an "entire form" prefill
      if (!$id && $autoFillMode === 'user' && $apiRequest->getFillMode() === 'form') {
        $id = \CRM_Core_Session::getLoggedInContactID();
        if ($id) {
          $apiRequest->loadEntity($entity, [['id' => $id]]);
        }
      }
      // Autofill with current entity (e.g. on the contact summary screen)
      if (!$id && $autoFillMode === 'entity_id' && $apiRequest->getFillMode() === 'form') {
        $id = $apiRequest->getArgs()['contact_id'] ?? NULL;
        if ($id) {
          $apiRequest->loadEntity($entity, [['id' => $id]]);
        }
      }
      // Autofill by relationship
      if (!$id && $relatedContact && str_starts_with($autoFillMode, 'relationship:')) {
        $relationshipType = substr($autoFillMode, strlen('relationship:'));
        $relatedEntity = $event->getFormDataModel()->getEntity($relatedContact);
        if ($relatedEntity) {
          $relatedContact = $event->getEntityIds($relatedContact)[0] ?? NULL;
        }
        if ($relatedContact) {
          $relations = \Civi\Api4\RelationshipCache::get(FALSE)
            ->addSelect('near_contact_id')
            ->addWhere('near_relation', '=', $relationshipType)
            ->addWhere('far_contact_id', '=', $relatedContact)
            ->addWhere('near_contact_id.is_deleted', '=', FALSE)
            ->addWhere('is_current', '=', TRUE)
            ->execute();
          $relatedIds = [];
          foreach ($relations as $relation) {
            $relatedIds[] = ['id' => $relation['near_contact_id']];
          }
          $apiRequest->loadEntity($entity, $relatedIds);
        }
      }
    }
  }

  public function onCreateToken(TokenRow $row, array &$afformArgs) {
    if (!empty($row->context['contactId'])) {
      $afformArgs['contact_id'] = $row->context['contactId'];
    }
  }

}
