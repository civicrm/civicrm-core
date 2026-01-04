<?php
namespace Civi\Afform\Behavior;

use Civi\Afform\Event\AfformEntitySortEvent;
use Civi\Afform\Event\AfformPrefillEvent;
use Civi\API\Event\RespondEvent;
use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use CRM_Case_ExtensionUtil as E;

/**
 * @service
 * @internal
 */
class ContactAutofillBasedOnCase extends AutoService implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.api.respond' => 'onAfformGetBehaviors',
      'hook_civicrm_buildAsset' => 'onBuildAsset',
      'civi.afform.sort.prefill' => 'onAfformSortPrefill',
      'civi.afform.prefill' => ['onAfformPrefill', 100],
    ];
  }

  public static function onAfformGetBehaviors(RespondEvent $event) {
    if ($event->getEntityName() == 'AfformBehavior' && $event->getActionName() == 'get') {
      $response = $event->getResponse();
      foreach ($response as $index => $behavior) {
        if ($behavior['key'] == 'autofill') {
          foreach (\CRM_Contact_BAO_ContactType::basicTypes() as $contactType) {
            if (in_array($contactType, $behavior['entities'])) {
              $response[$index]['modes'][$contactType] = self::getCaseRoleModes($contactType, $behavior['modes'][$contactType]);
            }
          }
        }
      }
      $event->setResponse($response);
    }
  }

  public static function onBuildAsset(GenericHookEvent $event) {
    if ($event->asset == 'angular-modules.json') {
      $json = json_decode($event->content, TRUE);
      if (isset($json['afGuiEditor']['partials']['~/afGuiEditor/behaviors/autofillRelationshipBehavior.html'])) {
        // Add a snippet to the partials in afGuiEditor (provided by the afform admin extension)
        $json['afGuiEditor']['partials']['~/afGuiEditor/behaviors/autofillRelationshipBehavior.html'] .= '<div ng-if="$ctrl.entity.autofill && $ctrl.entity.autofill.indexOf(\'role_on_case:\') === 0"><autofill-case-behavior-form entity="$ctrl.entity" rel-types="behavior.modes" selected-type="$ctrl.entity.autofill"></autofill-case-behavior-form></div>';
      }
      $event->content = json_encode($json);
    }
  }

  public static function onAfformSortPrefill(AfformEntitySortEvent $event): void {
    foreach ($event->getFormDataModel()->getEntities() as $entityName => $entity) {
      $autoFillMode = $entity['autofill'] ?? '';
      $relatedCase = $entity['autofill-case'] ?? NULL;
      if ($relatedCase && str_starts_with($autoFillMode, 'role_on_case:')) {
        $event->addDependency($entityName, $relatedCase);
      }
    }
  }

  public static function onAfformPrefill(AfformPrefillEvent $event): void {
    /* @var \Civi\Api4\Action\Afform\Prefill $apiRequest */
    $apiRequest = $event->getApiRequest();
    if (CoreUtil::isContact($event->getEntityType())) {
      $entity = $event->getEntity();
      $ids = (array) ($apiRequest->getArgs()[$event->getEntityName()] ?? []);
      $autoFillMode = $entity['autofill'] ?? '';
      $relatedCase = $entity['autofill-case'] ?? NULL;
      // Autofill by CiviCase
      if ($relatedCase && str_starts_with($autoFillMode, 'role_on_case:')) {
        $roleType = substr($autoFillMode, strlen('role_on_case:'));
        $relatedEntity = $event->getFormDataModel()->getEntity($relatedCase);
        if ($relatedEntity) {
          $relatedCase = $event->getEntityIds($relatedCase)[0] ?? NULL;
        }
        if ($relatedCase) {
          $relatedIds = [];
          if ($roleType == 'client') {
            $caseContacts = \Civi\Api4\CaseContact::get(FALSE)
              ->addSelect('contact_id')
              ->addWhere('case_id', '=', $relatedCase);
            if (count($ids)) {
              $caseContacts->addWhere('contact_id', 'IN', $ids);
            }
            $caseContacts = $caseContacts->execute();
            foreach ($caseContacts as $caseContact) {
              $relatedIds[] = ['id' => $caseContact['contact_id']];
            }
          }
          else {
            $caseRoles = \Civi\Api4\RelationshipCache::get(FALSE)
              ->addSelect('near_contact_id')
              ->addWhere('case_id', '=', $relatedCase)
              ->addWhere('near_contact_id.is_deleted', '=', FALSE)
              ->addWhere('is_current', '=', TRUE);
            if ($roleType == 'any') {
              $caseRoles->addWhere('orientation', '=', 'b_a');
            }
            else {
              $caseRoles->addWhere('near_relation', '=', $roleType);
            }
            $caseRoles = $caseRoles->execute();
            foreach ($caseRoles as $caseRole) {
              $relatedIds[] = ['id' => $caseRole['near_contact_id']];
            }
          }
          if (count($ids) > 0) {
            // We need to unload the entity.
            // As earlier on (in function the AbstractProcessor:loadEntities) data is loaded
            // because the drop down for autofill contains a value
            // The data loaded is not the correct data because we just fetched the correct data.
            $apiRequest->unloadEntity($entity);
          }
          $apiRequest->loadEntity($entity, $relatedIds);
        }
      }
    }
  }

  /**
   * Get the Case Role modes
   *
   * @param string $contactType
   * @param array $modes
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private static function getCaseRoleModes(string $contactType, array $modes): array {
    $modes[] = [
      'name' => 'role_on_case:client',
      'label' => E::ts('Is client of case'),
      'description' => E::ts('Contact is client on the case'),
      'icon' => 'fa-folder-open',
    ];
    $caseRoles = [];
    $caseTypes = \Civi\Api4\CaseType::get(FALSE)
      ->addSelect('title', 'definition')
      ->addWhere('is_active', '=', TRUE)
      ->setLimit(0)
      ->execute();
    foreach ($caseTypes as $caseType) {
      if (isset($caseType['definition']['caseRoles']) && is_array($caseType['definition']['caseRoles'])) {
        foreach ($caseType['definition']['caseRoles'] as $caseRole) {
          $caseRoles[$caseRole['name']][] = $caseType['title'];
        }
      }
    }
    $relationshipTypes = \Civi\Api4\RelationshipType::get(FALSE)
      ->addSelect('name_a_b', 'name_b_a', 'label_a_b', 'label_b_a', 'description', 'contact_type_a', 'contact_type_b')
      ->addWhere('is_active', '=', TRUE)
      ->addClause('OR', ['contact_type_a', '=', $contactType], ['contact_type_a', 'IS NULL'], ['contact_type_b', '=', $contactType], ['contact_type_b', 'IS NULL'])
      ->execute();
    foreach ($relationshipTypes as $relationshipType) {
      if ($relationshipType['name_a_b']) {
        $caseTypesWithThisRole = $caseRoles[$relationshipType['name_a_b']]
          ?? $caseRoles[$relationshipType['label_a_b']]
          ?? [];
        $isMatchingContactType = empty($relationshipType['contact_type_a']) || $relationshipType['contact_type_a'] === $contactType;
        if ($isMatchingContactType && !empty($caseTypesWithThisRole)) {
          $caseTypes = implode(', ', $caseTypesWithThisRole);
          $modes[] = [
            'name' => 'role_on_case:' . $relationshipType['name_a_b'],
            'label' => E::ts('Case Role is: %1', [1 => $relationshipType['label_a_b']]),
            'description' => E::ts('In use for the following case types: %1', [1 => $caseTypes]),
            'icon' => 'fa-folder-open',
          ];
        }
      }

      if ($relationshipType['name_b_a']) {
        $caseTypesWithThisRole = $caseRoles[$relationshipType['name_b_a']]
          ?? $caseRoles[$relationshipType['label_b_a']]
          ?? [];
        $isMatchingContactType = empty($relationshipType['contact_type_b']) || $relationshipType['contact_type_b'] === $contactType;
        if ($isMatchingContactType && !empty($caseTypesWithThisRole)) {
          $caseTypes = implode(', ', $caseTypesWithThisRole);
          $modes[] = [
            'name' => 'role_on_case:' . $relationshipType['name_b_a'],
            'label' => E::ts('Case Role is: %1', [1 => $relationshipType['label_b_a']]),
            'description' => E::ts('In use for the following case types: %1', [1 => $caseTypes]),
            'icon' => 'fa-folder-open',
          ];
        }
      }
    }
    $modes[] = [
      'name' => 'role_on_case:any',
      'label' => E::ts('Case role is: any'),
      'description' => E::ts('Contact has any role on the case'),
      'icon' => 'fa-folder-open',
    ];
    return $modes;
  }

}
