<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Campaign_Page_Petition_ThankYou extends CRM_Core_Page {
  function run() {
    $id             = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $petition_id    = CRM_Utils_Request::retrieve('pid', 'Positive', $this);
    $params['id']   = $petition_id;
    $this->petition = array();
    CRM_Campaign_BAO_Survey::retrieve($params, $this->petition);
    $this->assign('petitionTitle', $this->petition['title']);
    $this->assign('thankyou_title', CRM_Utils_Array::value('thankyou_title', $this->petition));
    $this->assign('thankyou_text', CRM_Utils_Array::value('thankyou_text', $this->petition));
    $this->assign('survey_id', $petition_id);
    $this->assign('status_id', $id);
    CRM_Utils_System::setTitle(CRM_Utils_Array::value('thankyou_title', $this->petition));

    // send thank you or email verification emails
    /*
       * sendEmailMode
       * 1 = connected user via login/pwd - thank you
       *      or dedupe contact matched who doesn't have a tag CIVICRM_TAG_UNCONFIRMED - thank you
       *      login using fb connect - thank you + click to add msg to fb wall
       * 2 = send a confirmation request email
       */


    return parent::run();
  }
}

