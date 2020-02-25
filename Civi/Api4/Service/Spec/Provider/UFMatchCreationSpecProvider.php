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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */


namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\RequestSpec;

class UFMatchCreationSpecProvider implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $spec->getFieldByName('domain_id')->setRequired(FALSE);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'UFMatch' && $action === 'create';
  }

}
