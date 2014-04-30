<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Contact_Page_ImageFile extends CRM_Core_Page {
  function run(){
    $currentURL = CRM_Utils_System::makeURL(NULL, FALSE, FALSE, NULL, TRUE);
    $sql = "SELECT id FROM civicrm_contact WHERE image_url=%1;";
    $params = array(1 => array($currentURL, 'String'));
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()){
      $cid=$dao->id;
    }
    if ($cid){
       $config = CRM_Core_Config::singleton();
       $buffer = file_get_contents($config->customFileUploadDir . $_GET['photo']);
       $mimeType = 'image/' .pathinfo($_GET['photo'], PATHINFO_EXTENSION);
       CRM_Utils_System::download($_GET['photo'], $mimeType, $buffer,
        NULL,
        TRUE, 
       'inline'
      );
    }
    else{
      echo 'image url not in database';
    }
    CRM_Utils_System::civiExit();     
  }
}


