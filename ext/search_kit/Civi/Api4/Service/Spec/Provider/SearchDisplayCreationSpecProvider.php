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

use Civi\Api4\Service\Spec\RequestSpec;

class SearchDisplayCreationSpecProvider implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $spec->getFieldByName('name')->setRequired(FALSE)->setRequiredIf('empty($values.label)');
    $spec->getFieldByName('label')->setRequired(FALSE)->setRequiredIf('empty($values.name)');
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'SearchDisplay' && $action === 'create';
  }

}
