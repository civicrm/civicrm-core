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

class AttachmentSpecProvider implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $action = $spec->getAction();

    if ($action == 'create') {
      $spec->getFieldByName('mime_type')->setRequired(TRUE);
    }
    if ($action == 'update') {
      $spec->getFieldByName('id')->setRequired(TRUE);
    }
    if ($action == 'get') {
      foreach (\CRM_Core_BAO_Attachment::pseudoFields() as $fieldName => $field) {
        $specField = new FieldSpec($fieldName, $spec->getEntity(), 'String');
        $specField->setPseudo(TRUE);
        $specField->setTitle($field['title']);
        $specField->setDescription(($field['description'] ?? ''));
        $specField->setRequired(($field['required'] ?? FALSE));
        $spec->addFieldSpec($specField);
      }
    }
    else {
      $spec->getFieldByName('entity_id')->setRequired(TRUE);
      $spec->getFieldByName('upload_date')->setDefaultValue('now');
      $spec->getFieldByName('entity_table')->setRequiredIf('empty($values.id) && empty($values.field_name)');
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Attachment';
  }

}
