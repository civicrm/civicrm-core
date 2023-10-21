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
 * The choice of package/activation determines how it interacts with the 2-3 ext's that already reproduce across domains:
 *
 * 1. Nobody has to opt-in. Bt would multiply #records, unless we  a hard-coded exclusion list for the 2-3.
 * 2. Won't activate unless the site-admin chooses to.
 * 3. Won't activate unless the site-admin chooses to.
 * 4. Each extension needs to opt into the new behavior.
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
    $templates = [];

    // Figure out which entities to copy
    foreach (array_keys($entities) as $entityKey) {
      if ($this->isCopiable($entities[$entityKey])) {
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

}
