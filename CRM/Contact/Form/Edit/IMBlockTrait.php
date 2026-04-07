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

use Civi\Api4\IM;
use Civi\Api4\Generic\Result;

/**
 * Form helper trait for including IMs in forms.
 *
 * @internal not supported for use outside core - if you do use it ensure your
 *  code has adequate unit test cover.
 */
trait CRM_Contact_Form_Edit_IMBlockTrait {
  use CRM_Contact_Form_Edit_BlockCustomDataTrait;

  /**
   * @var \Civi\Api4\Generic\Result
   */
  private Result $existingIMs;

  /**
   * @return \Civi\Api4\Generic\Result
   * @throws CRM_Core_Exception
   */
  public function getExistingIMs() : Result {
    if (!isset($this->existingIMs)) {
      $this->existingIMs = IM::get()
        ->addSelect('*', 'custom.*')
        ->addOrderBy('is_primary', 'DESC')
        ->addWhere('contact_id', '=', $this->getContactID())
        ->execute();
    }
    return $this->existingIMs;
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
  public function getExistingIMsReIndexed() : array {
    $result = array_merge([0 => 1], (array) $this->getExistingIMs());
    unset($result[0]);
    return $result;
  }

  /**
   * @throws \CRM_Core_Exception
   */
  protected function addIMBlockFields(int $blockNumber): void {

    $this->applyFilter('__ALL__', 'trim');
    //IM provider select
    $this->addField("im[$blockNumber][provider_id]", ['entity' => 'im', 'class' => 'eight', 'placeholder' => NULL, 'title' => ts('IM Type %1', [1 => $blockNumber])]);
    //Block type select
    $this->addField("im[$blockNumber][location_type_id]", ['entity' => 'im', 'class' => 'eight', 'placeholder' => NULL, 'option_url' => NULL, 'title' => ts('IM Location %1', [1 => $blockNumber])]);

    //IM box
    $this->addField("im[$blockNumber][name]", ['entity' => 'im', 'aria-label' => ts('Instant Messenger %1', [1 => $blockNumber])]);
    //is_Primary radio
    $js = ['id' => 'IM_' . $blockNumber . '_IsPrimary', 'aria-label' => ts('Instant Messenger %1 is primary?', [1 => $blockNumber])];
    if ($this->isContactSummaryEdit) {
      $js['onClick'] = 'singleSelect( this.id );';
    }

    $this->addElement('radio', "im[$blockNumber][is_primary]", '', '', '1', $js);
    $this->addCustomDataFieldBlock('IM', $blockNumber);
  }

  /**
   * @throws CRM_Core_Exception
   */
  public function saveIMs(array $ims): void {
    $existingIMs = (array) $this->getExistingIMs()->indexBy('id');
    foreach ($ims as $index => $im) {
      $id = $im['id'] ?? NULL;
      $dataExists = !CRM_Utils_System::isNull($im['name']);
      if (!$dataExists) {
        unset($ims[$index]);
        continue;
      }
      if (!array_key_exists('contact_id', $im)) {
        $ims[$index]['contact_id'] = $this->getContactID();
      }
      if ($id) {
        if (array_key_exists($id, $existingIMs)) {
          // We unset this here because we are going to delete any existing
          // emails that were not in the incoming array.
          unset($existingIMs[$id]);
        }
        else {
          // The id is not valid, this becomes a create.
          unset($im['id']);
        }
      }
    }
    if ($ims) {
      IM::save()
        ->setRecords($ims)
        ->execute();
    }

    if (!empty($ims)) {
      IM::delete()->addWhere('id', 'IN', array_keys($existingIMs))
        ->execute();
    }

  }

}
