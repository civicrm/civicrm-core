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
   * @var \Civi\Api4\Generic\Result
   */
  private Result $existingPhones;

  /**
   * @return \Civi\Api4\Generic\Result
   * @throws CRM_Core_Exception
   */
  public function getExistingPhones() : Result {
    if (!isset($this->existingPhones)) {
      $this->existingPhones = Phone::get()
        ->addSelect('*', 'custom.*')
        ->addOrderBy('is_primary', 'DESC')
        ->addWhere('contact_id', '=', $this->getContactID())
        ->execute();
    }
    return $this->existingPhones;
  }

  /**
   * Get the open ids indexed numerically from 1.
   *
   * This reflects historical form requirements.
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function getExistingPhonesReIndexed() : array {
    $result = array_merge([0 => 1], (array) $this->getExistingPhones());
    unset($result[0]);
    return $result;
  }

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
    $this->addCustomDataFieldBlock('Phone', $blockNumber);
  }

  /**
   * @throws CRM_Core_Exception
   */
  public function savePhones(array $phones): void {
    $existingPhones = (array) $this->getExistingPhones()->indexBy('id');
    foreach ($phones as $index => $phone) {
      $id = $phone['id'] ?? NULL;
      $dataExists = !CRM_Utils_System::isNull($phone['phone']);
      if (!$dataExists) {
        unset($phones[$index]);
        continue;
      }
      if (!array_key_exists('contact_id', $phone)) {
        $phones[$index]['contact_id'] = $this->getContactID();
      }
      if ($id) {
        if (array_key_exists($id, $existingPhones)) {
          // We unset this here because we are going to delete any existing
          // emails that were not in the incoming array.
          unset($existingPhones[$id]);
        }
        else {
          // The id is not valid, this becomes a create.
          unset($phone['id']);
        }
      }
    }
    if ($phones) {
      Phone::save()
        ->setRecords($phones)
        ->execute();
    }

    if (!empty($existingPhones)) {
      Phone::delete()->addWhere('id', 'IN', array_keys($existingPhones))
        ->execute();
    }

  }

}
