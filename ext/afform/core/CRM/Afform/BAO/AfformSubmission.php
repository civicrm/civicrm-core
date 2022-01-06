<?php
use CRM_Afform_ExtensionUtil as E;

class CRM_Afform_BAO_AfformSubmission extends CRM_Afform_DAO_AfformSubmission {

  /**
   * Pseudoconstant callback for `afform_name`
   * @return array
   */
  public static function getAllAfformsByName() {
    return \Civi\Api4\Afform::get(FALSE)
      ->addSelect('name', 'title')
      ->addOrderBy('title')
      ->execute()
      ->indexBy('name')
      ->column('title');
  }

}
