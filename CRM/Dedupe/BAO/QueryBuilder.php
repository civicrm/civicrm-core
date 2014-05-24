<?php

/**
 * Class CRM_Dedupe_BAO_QueryBuilder
 */
class CRM_Dedupe_BAO_QueryBuilder {
  /**
   * @param $rg
   * @param string $strID1
   * @param string $strID2
   *
   * @return string
   */
  static function internalFilters( $rg, $strID1 = 'contact1.id', $strID2 = 'contact2.id' ) {
    // Add a contact id filter for dedupe by group requests and add logic
    // to remove duplicate results with opposing orders, i.e. 1,2 and 2,1
    if( !empty($rg->contactIds) ) {
      $cids = implode(',',$rg->contactIds);
      return "($strID1 IN ($cids) AND ( $strID2 NOT IN ($cids) OR ($strID2 IN ($cids) AND $strID1 < $strID2) ))";
    }
    else {
      return "($strID1 < $strID2)";
    }
  }
};



