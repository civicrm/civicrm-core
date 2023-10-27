<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

/**
 * @service
 * @internal
 */
class QueueRunnerSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  public function modifySpec(RequestSpec $spec): void {
    $field = new FieldSpec('runner', $spec->getEntity(), 'String');
    $field->setLabel(ts('Runner (deprecated)'))
      ->setTitle(ts('Runner (deprecated)'))
      ->setColumnName('payload')
      ->setDescription(ts('Use "Payload" instead'))
      ->setType('Extra');
    $spec->addFieldSpec($field);
  }

  public function applies($entity, $action): bool {
    return $entity === 'Queue' && in_array($action, ['get', 'create', 'update', 'delete']);
  }

}
