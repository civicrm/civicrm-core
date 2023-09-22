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

namespace Civi\Api4\Service\Spec\Provider\Generic;

use Civi\Api4\Service\Spec\RequestSpec;

/**
 * @serviceTags spec_provider
 */
interface SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   *
   * @return void
   */
  public function modifySpec(RequestSpec $spec);

  /**
   * @param string $entity
   * @param string $action
   * Optional @param array $values
   *   $values from the api getFields request.
   *   This param works but has not been added to the interface for the sake of backward-compatability.
   *
   * @return bool
   */
  public function applies(string $entity, string $action/*, array $values = []*/);

}
