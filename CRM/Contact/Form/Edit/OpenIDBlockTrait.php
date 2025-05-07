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

use Civi\Api4\OpenID;
use Civi\Api4\Generic\Result;

/**
 * Form helper trait for including open IDs in forms.
 *
 * @internal not supported for use outside core - if you do use it ensure your
 *  code has adequate unit test cover.
 */
trait CRM_Contact_Form_Edit_OpenIDBlockTrait {
  use CRM_Contact_Form_Edit_BlockCustomDataTrait;

  /**
   * @var \Civi\Api4\Generic\Result
   */
  private Result $existingOpenIDs;

  /**
   *
   * @return \Civi\Api4\Generic\Result
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function getExistingOpenIDs() : Result {
    if (!isset($this->existingOpenIDs)) {
      $this->existingOpenIDs = OpenID::get()
        ->addSelect('*', 'custom.*', 'location_type_id:label')
        ->addOrderBy('is_primary', 'DESC')
        ->addWhere('contact_id', '=', $this->getContactID())
        ->execute();
    }
    return $this->existingOpenIDs;
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
  public function getExistingOpenIDsReIndexed() : array {
    $result = array_merge([0 => 1], (array) $this->getExistingOpenIDs());
    unset($result[0]);
    return $result;
  }

  /**
   * @throws \CRM_Core_Exception
   */
  protected function addOpenIDBlockFields(int $blockNumber): void {

    $this->applyFilter('__ALL__', 'trim');

    $this->addElement('text', "openid[$blockNumber][openid]", ts('OpenID'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_OpenID', 'openid')
    );

    //Block type
    $this->addElement('select', "openid[$blockNumber][location_type_id]", '', CRM_Core_DAO_Address::buildOptions('location_type_id'));

    //is_Primary radio
    $js = ['id' => "OpenID_" . $blockNumber . "_IsPrimary"];
    if ($this->isContactSummaryEdit) {
      $js['onClick'] = 'singleSelect( this.id );';
    }

    $this->addElement('radio', "openid[$blockNumber][is_primary]", '', '', '1', $js);
    $this->addCustomDataFieldBlock('OpenID', $blockNumber);
  }

  /**
   * @throws CRM_Core_Exception
   */
  public function saveOpenIDss(array $openIDs): void {
    $existingOpenIDs = (array) $this->getExistingOpenIDs()->indexBy('id');
    foreach ($openIDs as $index => $openID) {
      $id = $openID['id'] ?? NULL;
      $dataExists = !CRM_Utils_System::isNull($openID['openid']);
      if (!$dataExists) {
        unset($openIDs[$index]);
        continue;
      }
      if (!array_key_exists('contact_id', $openID)) {
        $openIDs[$index]['contact_id'] = $this->getContactID();
      }
      if ($id) {
        if (array_key_exists($id, $existingOpenIDs)) {
          // We unset this here because we are going to delete any existing
          // emails that were not in the incoming array.
          unset($existingOpenIDs[$id]);
        }
        else {
          // The id is not valid, this becomes a create.
          unset($openID['id']);
        }
      }
    }
    if ($openIDs) {
      OpenID::save()
        ->setRecords($openIDs)
        ->execute();
    }

    if (!empty($existingOpenIDs)) {
      OpenID::delete()->addWhere('id', 'IN', array_keys($existingOpenIDs))
        ->execute();
    }

  }

}
