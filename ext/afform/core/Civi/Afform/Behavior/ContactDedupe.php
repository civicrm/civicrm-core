<?php
namespace Civi\Afform\Behavior;

use Civi\Afform\AbstractBehavior;
use Civi\Afform\Event\AfformSubmitEvent;
use Civi\Api4\Utils\CoreUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use CRM_Afform_ExtensionUtil as E;

/**
 * @service
 * @internal
 */
class ContactDedupe extends AbstractBehavior implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.afform.submit' => ['onAfformSubmit', 101],
    ];
  }

  public static function getEntities():array {
    return \CRM_Contact_BAO_ContactType::basicTypes();
  }

  public static function getTitle():string {
    return E::ts('Duplicate Matching');
  }

  public static function getDescription():string {
    return E::ts('Update existing contact instead of creating a new one based on a dedupe rule.');
  }

  public static function getModes(string $entityName):array {
    $modes = [];
    $dedupeRuleGroups = \Civi\Api4\DedupeRuleGroup::get(FALSE)
      ->addWhere('contact_type', '=', $entityName)
      ->addOrderBy('used', 'DESC')
      ->addOrderBy('title', 'ASC')
      ->execute();
    foreach ($dedupeRuleGroups as $rule) {
      // Use the generic API name for supervised/unsupervised rules as it's more portable
      $name = ($rule['used'] === 'General' ? $rule['name'] : $entityName . '.' . $rule['used']);
      $modes[] = [
        'name' => $name,
        'label' => $rule['title'],
      ];
    }
    return $modes;
  }

  public static function onAfformSubmit(AfformSubmitEvent $event) {
    $entity = $event->getEntity();
    $dedupeMode = $entity['contact-dedupe'] ?? NULL;
    if (!CoreUtil::isContact($entity['type']) || !$dedupeMode) {
      return;
    }
    // Apply dedupe rule if contact isn't already identified
    foreach ($event->records as $index => $record) {
      $supportedJoins = ['Address', 'Email', 'Phone', 'IM'];
      $values = $record['fields'] ?? [];
      foreach ($supportedJoins as $joinEntity) {
        if (!empty($record['joins'][$joinEntity][0])) {
          $values += \CRM_Utils_Array::prefixKeys($record['joins'][$joinEntity][0], strtolower($joinEntity) . '_primary.');
        }
      }
      $match = civicrm_api4($entity['type'], 'getDuplicates', [
        'checkPermissions' => FALSE,
        'values' => $values,
        'dedupeRule' => $dedupeMode,
      ])->first();
      if (!empty($match['id'])) {
        $event->setEntityId($index, $match['id']);
      }
    }
  }

}
