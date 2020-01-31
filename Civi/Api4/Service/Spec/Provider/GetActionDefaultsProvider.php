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

class GetActionDefaultsProvider implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    // Exclude deleted records from api Get by default
    $isDeletedField = $spec->getFieldByName('is_deleted');
    if ($isDeletedField) {
      $isDeletedField->setDefaultValue('0');
    }

    // Exclude test records from api Get by default
    $isTestField = $spec->getFieldByName('is_test');
    if ($isTestField) {
      $isTestField->setDefaultValue('0');
    }

    $isTemplateField = $spec->getFieldByName('is_template');
    if ($isTemplateField) {
      $isTemplateField->setDefaultValue('0');
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $action === 'get';
  }

}
