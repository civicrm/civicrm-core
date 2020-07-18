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
class CRM_Badge_Page_AJAX {

  public static function getImageProp() {
    $img = $_GET['img'];
    list($w, $h) = CRM_Badge_BAO_Badge::getImageProperties($img);
    CRM_Utils_JSON::output(['width' => $w, 'height' => $h]);
  }

}
