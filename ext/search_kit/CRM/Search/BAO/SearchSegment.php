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
 * Search Segment BAO
 */
class CRM_Search_BAO_SearchSegment extends CRM_Search_DAO_SearchSegment {

  /**
   * Retrieve pseudoconstant options for $this->entity_name field
   * @return array
   */
  public static function getDAOEntityOptions() {
    return Civi\Api4\Entity::get(FALSE)
      ->addSelect('name', 'title_plural')
      ->addOrderBy('title_plural')
      ->addWhere('type', 'CONTAINS', 'DAOEntity')
      ->execute()
      ->column('title_plural', 'name');
  }

}
