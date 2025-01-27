<?php

namespace Civi\Managed;

use Civi\Api4\Domain;
use Civi\Api4\Setting;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * If an extension defines a record that should exist on all domains, replicate it across domains.
 *
 * @service
 * @internal
 */
class MultisiteManaged extends AutoService implements EventSubscriberInterface {

  private $entities = [];
  private $domains;

  public static function getSubscribedEvents() {
    return [
      '&hook_civicrm_managed' => ['generateDomainEntities', -1000],
    ];
  }

  /**
   * @implements \CRM_Utils_Hook::managed()
   * @param array $managedRecords
   */
  public function generateDomainEntities(array &$managedRecords): void {
    $multisiteEnabled = Setting::get(FALSE)
      ->addSelect('is_enabled')
      ->execute()->first();
    if (empty($multisiteEnabled['value'])) {
      return;
    }

    // array_splice needs array keys to be orderly
    $managedRecords = array_values($managedRecords);
    // Replace every single-domain record with one record per domain
    // Walk the array in reverse order so the keys being processed remain stable even as the length changes.
    foreach (array_reverse(array_keys($managedRecords)) as $index) {
      if ($this->isCopiable($managedRecords[$index])) {
        array_splice($managedRecords, $index, 1, $this->makeCopies($managedRecords[$index]));
      }
    }
  }

  protected function makeCopies(array $managedRecord): array {
    $copies = [];
    foreach ($this->getDomains() as $index => $domainId) {
      $copy = $managedRecord;
      // For a smoother transition between enabling/disabling multisite, don't rename the first copy
      if ($index) {
        $copy['name'] .= '_' . $domainId;
      }
      // Add concrete domain_id to the values
      $copy['params']['values']['domain_id'] = $domainId;
      // If matching is enabled, ensure we also match on domain_id
      if (isset($copy['params']['match']) && !in_array('domain_id', $copy['params']['match'])) {
        $copy['params']['match'][] = 'domain_id';
      }
      $copies[] = $copy;
    }
    return $copies;
  }

  /**
   * Check if a managed record is an APIv4 Entity that should exist on all domains.
   *
   * Follows the same logic for determining an entity belongs on multiple domains as `FieldDomainIdSpecProvider`
   * @see \Civi\Api4\Service\Spec\Provider\FieldDomainIdSpecProvider
   *
   * @param array $managedRecord
   * @return bool
   */
  protected function isCopiable(array $managedRecord): bool {
    if ($managedRecord['params']['version'] != 4) {
      return FALSE;
    }
    // Extra guard so that clever extensions (which multiply entities themselves) don't get entities-squared.
    if (is_numeric($managedRecord['params']['values']['domain_id'] ?? NULL) || !empty($managedRecord['params']['values']['domain_id.name'])) {
      \CRM_Core_Error::deprecatedWarning(sprintf('Module "%s" has self-multiplied managed entity "%s" across domains. This is deprecated.', $managedRecord['module'], $managedRecord['name']));
      return FALSE;
    }
    if (!isset($this->entities[$managedRecord['entity']])) {
      try {
        $this->entities[$managedRecord['entity']] = (bool) civicrm_api4($managedRecord['entity'], 'getFields', [
          'checkPermissions' => FALSE,
          'action' => 'create',
          'where' => [
            ['name', '=', 'domain_id'],
            ['default_value', '=', 'current_domain'],
          ],
        ])->count();
      }
      catch (\CRM_Core_Exception $e) {
        $this->entities[$managedRecord['entity']] = FALSE;
      }
    }
    return $this->entities[$managedRecord['entity']];
  }

  private function getDomains(): array {
    if (!isset($this->domains)) {
      $this->domains = Domain::get(FALSE)->addSelect('id')->addOrderBy('id')->execute()->column('id');
    }
    return $this->domains;
  }

}
