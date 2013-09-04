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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for Address Section
 */
class CRM_Admin_Form_Preferences_Address extends CRM_Admin_Form_Preferences {
  function preProcess() {

    CRM_Utils_System::setTitle(ts('Settings - Addresses'));


    $this->_varNames = array(
      CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME =>
      array(
        'address_options' => array(
          'html_type' => 'checkboxes',
          'title' => ts('Address Fields'),
          'weight' => 1,
        ),
        'address_format' => array(
          'html_type' => 'textarea',
          'title' => ts('Display Format'),
          'description' => NULL,
          'weight' => 2,
        ),
        'mailing_format' => array(
          'html_type' => 'textarea',
          'title' => ts('Mailing Label Format'),
          'description' => NULL,
          'weight' => 3,
        ),
      ),
      CRM_Core_BAO_Setting::ADDRESS_STANDARDIZATION_PREFERENCES_NAME =>
      array(
        'address_standardization_provider' => array(
          'html_type' => 'select',
          'weight' => 4,
        ),
        'address_standardization_userid' => array(
          'html_type' => 'text',
          'title' => ts('User ID'),
          'description' => NULL,
          'weight' => 5,
        ),
        'address_standardization_url' => array(
          'html_type' => 'text',
          'title' => ts('Web Service URL'),
          'description' => NULL,
          'weight' => 6,
        ),
      ),
    );

    parent::preProcess();
  }

  function setDefaultValues() {
    $defaults = array();
    $defaults['address_standardization_provider'] = $this->_config->address_standardization_provider;
    $defaults['address_standardization_userid'] = $this->_config->address_standardization_userid;
    $defaults['address_standardization_url'] = $this->_config->address_standardization_url;


    $this->addressSequence = isset($newSequence) ? $newSequence : "";

    if (empty($this->_config->address_format)) {
      $defaults['address_format'] = "
{contact.street_address}
{contact.supplemental_address_1}
{contact.supplemental_address_2}
{contact.city}{, }{contact.state_province}{ }{contact.postal_code}
{contact.country}
";
    }
    else {
      $defaults['address_format'] = $this->_config->address_format;
    }

    if (empty($this->_config->mailing_format)) {
      $defaults['mailing_format'] = "
{contact.addressee}
{contact.street_address}
{contact.supplemental_address_1}
{contact.supplemental_address_2}
{contact.city}{, }{contact.state_province}{ }{contact.postal_code}
{contact.country}
";
    }
    else {
      $defaults['mailing_format'] = $this->_config->mailing_format;
    }


    parent::cbsDefaultValues($defaults);

    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $this->applyFilter('__ALL__', 'trim');

    // Address Standardization
    $addrProviders = CRM_Core_SelectValues::addressProvider();
    $this->addElement('select',
      'address_standardization_provider',
      ts('Address Provider'),
      array(
        '' => '- select -') + $addrProviders
    );

    $this->addFormRule(array('CRM_Admin_Form_Preferences_Address', 'formRule'));

    //get the tokens for Mailing Label field
    $tokens = CRM_Core_SelectValues::contactTokens();
    natcasesort($tokens);
    $this->assign('tokens', json_encode($tokens));

    $this->add('select', 'token1', ts('Insert Token'),
      $tokens, FALSE,
      array(
        'size' => "5",
        'multiple' => TRUE,
        'onclick' => "return tokenReplText(this);",
      )
    );

    parent::buildQuickForm();
  }

  static function formRule($fields) {
    $p = $fields['address_standardization_provider'];
    $u = $fields['address_standardization_userid'];
    $w = $fields['address_standardization_url'];

    // make sure that there is a value for all of them
    // if any of them are set
    if ($p || $u || $w) {
      if (!CRM_Utils_System::checkPHPVersion(5, FALSE)) {
        $errors['_qf_default'] = ts('Address Standardization features require PHP version 5 or greater.');
        return $errors;
      }

      if (!($p && $u && $w)) {
        $errors['_qf_default'] = ts('You must provide values for all three Address Standarization fields.');
        return $errors;
      }
    }

    return TRUE;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    if ($this->_action == CRM_Core_Action::VIEW) {
      return;
    }

    $this->_params = $this->controller->exportValues($this->_name);


    // check if county option has been set
    $options = CRM_Core_OptionGroup::values('address_options', FALSE, FALSE, TRUE);
    foreach ($options as $key => $title) {
      if ($title == ts('County')) {
        // check if the $key is present in $this->_params
        if (isset($this->_params['address_options']) &&
          !empty($this->_params['address_options'][$key])
        ) {
          // print a status message to the user if county table seems small
          $sql = "
SELECT count(*)
FROM   civicrm_county
";
          $countyCount = CRM_Core_DAO::singleValueQuery($sql);
          if ($countyCount < 10) {
            global $civicrm_root;
            $sqlFilePath = $civicrm_root . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'counties.US.sql.gz';

            CRM_Core_Session::setStatus("", ts('You have enabled the County option. Please ensure you populate the county table in your CiviCRM Database. You can find a list of US counties (in gzip format) in your distribution at: <em>%1</em>',
              array(1 => $sqlFilePath)),
              "info"
            );
          }
        }
      }
    }

    $this->postProcessCommon();
  }
  //end of function
}

