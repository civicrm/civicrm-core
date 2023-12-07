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

/**
 * @service
 * @internal
 */
class GetActionDefaultsProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    // Exclude deleted records from api Get by default
    $isDeletedField = $spec->getFieldByName('is_deleted');
    if ($isDeletedField) {
      $isDeletedField->setDefaultValue(FALSE);
    }

    // Exclude test records from api Get by default
    $isTestField = $spec->getFieldByName('is_test');
    if ($isTestField) {
      $isTestField->setDefaultValue(FALSE);
    }

    $isTemplateField = $spec->getFieldByName('is_template');
    if ($isTemplateField) {
      $isTemplateField->setDefaultValue(FALSE);
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $action === 'get';
  }

}
