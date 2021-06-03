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

class AttachmentSpecProvider implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $action = $spec->getAction();

    if ($action == 'create') {
      $spec->getFieldByName('name')->setRequired(TRUE);
      $spec->getFieldByName('mime_type')->setRequired(TRUE);
      $spec->getFieldByName('entity_id')->setRequired(TRUE);
      $spec->getFieldByName('upload_date')->setDefaultValue('now');
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Attachment';
  }

}
