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
 */


namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Api4\Service\Spec\SpecFormatter;

class AttachmentCreationSpecProvider implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $spec->getFieldByName('mime_type')->setRequired(TRUE);
    $spec->getFieldByName('entity_id')->setRequired(TRUE);
    $spec->getFieldByName('upload_date')->setDefaultValue('now');
    //$spec->getFieldByName('id')->setRequiredIf('empty($values.entity_table) && empty($values.field_name)');
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Attachment' && $action === 'create';
  }

}
