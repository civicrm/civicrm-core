<?php

namespace Civi\Foo;

use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * If an extension defines a managed `Navigation` record, then replicate it across domains.
 *
 * Decision/Question: How to package/activate this functionality, eg
 *
 * 1. Package the subscriber as standard behavior in core
 * 2. Package the subscriber as optional behavior in core (eg setting `multisite_entity_sync`)
 * 3. Package the subscriber in an extension (`multsite` or `multisitemgd`)
 * 4. Package the subscriber as a mixin that extensions may toggle (`<mixin>mgd@2.0</mixin>`)
 *
 * This variant has an extra guard so that clever extensions (which multiply entities themselves) don't get entities-squared.
 *
 * @service
 * @internal
 */
class MultisiteManaged extends AutoService implements EventSubscriberInterface {

  protected $entities = ['Navigation', 'Dashboard'];

  public static function getSubscribedEvents() {
    return [
      '&hook_civicrm_managed' => ['generateDomainEntities', -1000],
    ];
  }

  public function generateDomainEntities(array &$entities): void {
    $selfMultipliedModules = $this->findSelfMultipliedModules($entities);
    foreach ($selfMultipliedModules as $module) {
      \CRM_Core_Error::deprecatedWarning(sprintf('Module (%s) has self-multiplied some records across domains. This is deprecated.', $module));
    }

    $templates = [];

    // Figure out which entities to copy
    foreach (array_keys($entities) as $entityKey) {
      if ($this->isCopiable($entities[$entityKey]) && !in_array($entities[$entityKey]['module'], $selfMultipliedModules)) {
        $templates[] = $entities[$entityKey];
        unset($entities[$entityKey]);
      }
    }

    // Make the real entities, one for each domain
    foreach ($templates as $template) {
      foreach ($this->makeCopies($template) as $entity) {
        $entities[] = $entity;
      }
    }
  }

  protected function makeCopies(array $entity): array {
    $copies = [];
    $domains = \Civi\Api4\Domain::get(FALSE)->addSelect('id')->execute();
    foreach ($domains as $domain) {
      $copy = $entity;
      $copy['name'] = $entity['name'] . '_' . $domain['id'];
      $copy['params']['values']['domain_id'] = $domain['id'];
      $copies[] = $copy;
    }
    return $copies;
  }

  protected function isCopiable(array $entity) {
    return in_array($entity['entity'], $this->entities) && ($entity['params']['version'] ?? 3) == 4;
  }

  protected function findSelfMultipliedModules(array $entities): array {
    $moduleDomains = [];
    foreach ($entities as $entity) {
      if ($this->isCopiable($entity) && !empty($entity['params']['values']['domain_id'])) {
        $moduleDomains[$entity['module']][] = $entity['params']['values']['domain_id'];
      }
    }
    $results = [];
    foreach ($moduleDomains as $module => $domains) {
      if (count(array_unique($domains)) > 1) {
        $results[] = $module;
      }
    }
    return $results;
  }

}
