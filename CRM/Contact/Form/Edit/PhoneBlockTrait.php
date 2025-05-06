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

use Civi\Api4\Phone;
use Civi\Api4\Generic\Result;

/**
 * Form helper trait for including phones in forms.
 *
 * @internal not supported for use outside core - if you do use it ensure your
 *  code has adequate unit test cover.
 */
trait CRM_Contact_Form_Edit_PhoneBlockTrait {
  use CRM_Contact_Form_Edit_BlockCustomDataTrait;

  /**
   * @throws \CRM_Core_Exception
   */
  protected function addPhoneBlockFields(int $blockNumber): void {

    $this->applyFilter('__ALL__', 'trim');

    //phone type select
    $this->addField("phone[$blockNumber][phone_type_id]", [
      'entity' => 'phone',
      'class' => 'eight',
      'placeholder' => NULL,
      'title' => ts('Phone Type %1', [1 => $blockNumber]),
    ]);
    //main phone number with crm_phone class
    $this->addField("phone[$blockNumber][phone]", [
      'entity' => 'phone',
      'class' => 'crm_phone twelve',
      'aria-label' => ts('Phone %1', [1 => $blockNumber]),
      'label' => ts('Phone %1:', [1 => $blockNumber]),
    ]);
    $this->addField("phone[$blockNumber][phone_ext]", [
      'entity' => 'phone',
      'aria-label' => ts('Phone Extension %1', [1 => $blockNumber]),
      'label' => ts('ext.', ['context' => 'phone_ext']),
    ]);
    //Block type select
    $this->addField("phone[$blockNumber][location_type_id]", [
      'entity' => 'phone',
      'class' => 'eight',
      'placeholder' => NULL,
      'option_url' => NULL,
      'title' => ts('Phone Location %1', [1 => $blockNumber]),
    ]);

    //is_Primary radio
    $js = ['id' => 'Phone_' . $blockNumber . '_IsPrimary', 'onClick' => 'singleSelect( this.id );', 'aria-label' => ts('Phone %1 is primary?', [1 => $blockNumber])];
    $this->addElement('radio', "phone[$blockNumber][is_primary]", '', '', '1', $js);
  }

}
