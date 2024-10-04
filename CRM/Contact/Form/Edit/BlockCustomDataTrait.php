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

/**
 * Form helper class to add custom data fields to location blocks.
 *
 * @internal not supported for use outside core - if you do use it ensure your
 * code has adequate unit test cover.
 */
trait CRM_Contact_Form_Edit_BlockCustomDataTrait {

  /**
   * @var array
   */
  protected array $customFieldBlocks = [];

  /**
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   */
  protected function addCustomDataFieldBlock(string $entity, int $blockNumber, array $filters = []): void {
    if (CRM_Core_Smarty::singleton()->getVersion() < 5) {
      // Apiv4 custom fields do not work with Smarty 2. By the time there is any
      // real uptake here we should have migrated fully to Smarty 5 so for now, do now harm.
      $this->assign('custom_fields_' . strtolower($entity), []);
      return;
    }
    $fields = (array) civicrm_api4($entity, 'getFields', [
      'action' => 'create',
      'values' => $filters,
      'where' => [
        ['type', '=', 'Custom'],
        ['readonly', '=', FALSE],
      ],
      'checkPermissions' => TRUE,
    ])->indexBy('custom_field_id');
    foreach ($fields as $field) {
      $elementName = strtolower($entity) . "[$blockNumber][{$field['name']}]";
      CRM_Core_BAO_CustomField::addQuickFormElement($this, $elementName, $field['custom_field_id'], $field['required']);
      if ($field['input_type'] === 'File') {
        $this->registerFileField([$elementName]);
      }
      $this->customFieldBlocks[$blockNumber][$field['name']] = $field;
    }
    $this->assign('custom_fields_' . strtolower($entity), $this->customFieldBlocks);
  }

}
