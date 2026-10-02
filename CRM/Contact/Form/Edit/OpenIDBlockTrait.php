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

use Civi\Api4\Generic\Result;

/**
 * Form helper trait for including open IDs in forms.
 *
 * @internal not supported for use outside core - if you do use it ensure your
 *  code has adequate unit test cover.
 */
trait CRM_Contact_Form_Edit_OpenIDBlockTrait {

  use CRM_Contact_Form_Edit_BlockTrait;

  /**
   * @return \Civi\Api4\Generic\Result
   * @throws \CRM_Core_Exception
   */
  public function getExistingOpenIDs(): Result {
    return $this->getExistingBlocks('OpenID');
  }

  /**
   * Get the open ids indexed numerically from 1.
   *
   * This reflects historical form requirements.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getExistingOpenIDsReIndexed(): array {
    return $this->getExistingBlocks('OpenID', 1);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  protected function addOpenIDBlockFields(int $blockNumber): void {
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
  public function saveOpenIDs(array $openIDs): void {
    $this->saveBlocks('OpenID', $openIDs);
  }

}
