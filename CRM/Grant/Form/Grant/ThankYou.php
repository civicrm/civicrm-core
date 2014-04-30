<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */

/**
 * form for thank-you / success page - 3rd step of online grant application process
 */
class CRM_Grant_Form_Grant_ThankYou extends CRM_Grant_Form_GrantBase {
  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    parent::preProcess();

    $this->_params   = $this->get('params');
    
    $this->assign('thankyou_title', CRM_Utils_Array::value('thankyou_title', $this->_values));
    $this->assign('thankyou_text', CRM_Utils_Array::value('thankyou_text', $this->_values));
    $this->assign('thankyou_footer', CRM_Utils_Array::value('thankyou_footer', $this->_values));
    CRM_Utils_System::setTitle(CRM_Utils_Array::value('thankyou_title', $this->_values));
    // Make the grantPageID avilable to the template
    $this->assign('grantPageID', $this->_id);
  }

  /**
   * overwrite action, since we are only showing elements in frozen mode
   * no help display needed
   *
   * @return int
   * @access public
   */
  function getAction() {
    if ($this->_action & CRM_Core_Action::PREVIEW) {
      return CRM_Core_Action::VIEW | CRM_Core_Action::PREVIEW;
    }
    else {
      return CRM_Core_Action::VIEW;
    }
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    $this->assignToTemplate();
    $this->assign('receiptFromEmail', CRM_Utils_Array::value('receipt_from_email', $this->_values));

    $params = $this->_params;
 
    $qParams = "reset=1&amp;id={$this->_id}";

    $this->assign( 'qParams' , $qParams );

    $this->buildCustom($this->_values['custom_pre_id'], 'customPre', TRUE);
    $this->buildCustom($this->_values['custom_post_id'], 'customPost', TRUE);
    if (CRM_Utils_Array::value('hidden_onbehalf_profile', $params)) {
      $ufJoinParams = array(
        'module' => 'onBehalf',
        'entity_table' => 'civicrm_grant_app_page',
        'entity_id' => $this->_id,
      );
      $OnBehalfProfile = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);
      $profileId = $OnBehalfProfile[0];

      $fieldTypes     = array('Contact', 'Organization');
      $contactSubType = CRM_Contact_BAO_ContactType::subTypes('Organization');
      $fieldTypes     = array_merge($fieldTypes, $contactSubType);
      $fieldTypes = array_merge($fieldTypes, array('Grant'));
      

      $this->buildCustom($profileId, 'onbehalfProfile', TRUE, TRUE, $fieldTypes);
    }
    $this->assign('application_received_date',
      CRM_Utils_Date::mysqlToIso(CRM_Utils_Array::value('receive_date', $this->_params))
    );

    $defaults = array();
    $options = array();
    $fields = array();
    $removeCustomFieldTypes = array('Grant');
    foreach ($this->_fields as $name => $dontCare) {
      if ($name == 'onbehalf') {
        foreach ($dontCare as $key => $value) {
          $fields['onbehalf'][$key] = 1;
        }
      }
      else {
        $fields[$name] = 1;
      }
    }
    $fields['state_province'] = $fields['country'] = $fields['email'] = 1;
    $contact = $this->_params = $this->controller->exportValues('Main');

    foreach ($fields as $name => $dontCare) {
      if ($name == 'onbehalf') {
        foreach ($dontCare as $key => $value) {
          //$defaults[$key] = $contact['onbehalf'][$key];
          if (isset($contact['onbehalf'][$key])) {
          $defaults[$key] = $contact['onbehalf'][$key];
        }
          if (isset($contact['onbehalf']["{$key}_id"])) {
            $defaults["{$key}_id"] = $contact['onbehalf']["{$key}_id"];
      }

        }
      }
      elseif (isset($contact[$name])) {
        $defaults[$name] = $contact[$name];
        if (substr($name, 0, 7) == 'custom_') {
          $timeField = "{$name}_time";
          if (isset($contact[$timeField])) {
            $defaults[$timeField] = $contact[$timeField];
          }
        }
        elseif (in_array($name, array('addressee', 'email_greeting', 'postal_greeting'))
          && CRM_Utils_Array::value($name . '_custom', $contact)
        ) {
          $defaults[$name . '_custom'] = $contact[$name . '_custom'];
        }
      }
    }

    // now fix all state country selectors
    CRM_Core_BAO_Address::fixAllStateSelects($this, $defaults);

    $this->_submitValues = array_merge($this->_submitValues, $defaults);
    $this->setDefaults($defaults);
    $this->freeze();

    // can we blow away the session now to prevent hackery
    // CRM-9491
    $this->controller->reset();
  }
}

