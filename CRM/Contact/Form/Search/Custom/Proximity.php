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
class CRM_Contact_Form_Search_Custom_Proximity extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_latitude = NULL;
  protected $_longitude = NULL;
  protected $_distance = NULL;

  function __construct(&$formValues) {
    parent::__construct($formValues);

    // unset search profile and other search params if set
    unset($this->_formValues['uf_group_id']);
    unset($this->_formValues['component_mode']);
    unset($this->_formValues['operator']);

    if (!empty($this->_formValues)) {
      // add the country and state
      if (CRM_Utils_Array::value('country_id', $this->_formValues)) {
        $this->_formValues['country'] = CRM_Core_PseudoConstant::country($this->_formValues['country_id']);
      }

      if (CRM_Utils_Array::value('state_province_id', $this->_formValues)) {
        $this->_formValues['state_province'] = CRM_Core_PseudoConstant::stateProvince($this->_formValues['state_province_id']);
      }

      // use the address to get the latitude and longitude
      CRM_Utils_Geocode_Google::format($this->_formValues);

      if (!is_numeric(CRM_Utils_Array::value('geo_code_1', $this->_formValues)) ||
        !is_numeric(CRM_Utils_Array::value('geo_code_2', $this->_formValues)) ||
        !isset($this->_formValues['distance'])
      ) {
        CRM_Core_Error::fatal(ts('Could not geocode input'));
      }

      $this->_latitude = $this->_formValues['geo_code_1'];
      $this->_longitude = $this->_formValues['geo_code_2'];

      if ($this->_formValues['prox_distance_unit'] == "miles") {
        $conversionFactor = 1609.344;
      }
      else {
        $conversionFactor = 1000;
      }
      $this->_distance = $this->_formValues['distance'] * $conversionFactor;
    }
    $this->_group = CRM_Utils_Array::value('group', $this->_formValues);

    $this->_tag = CRM_Utils_Array::value('tag', $this->_formValues);

    $this->_columns = array(
      ts('Name') => 'sort_name',
      ts('Street Address') => 'street_address',
      ts('City') => 'city',
      ts('Postal Code') => 'postal_code',
      ts('State') => 'state_province',
      ts('Country') => 'country',
    );
  }

  function buildForm(&$form) {

    $config = CRM_Core_Config::singleton();
    $countryDefault = $config->defaultContactCountry;

    $form->add('text', 'distance', ts('Distance'), NULL, TRUE);

    $proxUnits = array('km' => ts('km'), 'miles' => ts('miles'));
    $form->add('select', 'prox_distance_unit', ts('Units'), $proxUnits, TRUE);

    $form->add('text',
      'street_address',
      ts('Street Address')
    );

    $form->add('text',
      'city',
      ts('City')
    );

    $form->add('text',
      'postal_code',
      ts('Postal Code')
    );
    $stateCountryMap = array();
    $stateCountryMap[] = array(
      'state_province' => 'state_province_id',
      'country' => 'country_id',
    );
    $defaults = array();
    if ($countryDefault) {
      $stateProvince = array('' => ts('- select -')) + CRM_Core_PseudoConstant::stateProvinceForCountry($countryDefault);
      $defaults['country_id'] = $countryDefault;
    }
    else {
      $stateProvince = array('' => ts('- select -')) + CRM_Core_PseudoConstant::stateProvince();
    }
    $form->addElement('select', 'state_province_id', ts('State/Province'), $stateProvince);

    $country = array('' => ts('- select -')) + CRM_Core_PseudoConstant::country();
    $form->add('select', 'country_id', ts('Country'), $country, TRUE);

    $group = array('' => ts('- any group -')) + CRM_Core_PseudoConstant::group();
    $form->addElement('select', 'group', ts('Group'), $group);

    $tag = array('' => ts('- any tag -')) + CRM_Core_PseudoConstant::tag();
    $form->addElement('select', 'tag', ts('Tag'), $tag);


    // state country js, CRM-5233
    CRM_Core_BAO_Address::addStateCountryMap($stateCountryMap);
    CRM_Core_BAO_Address::fixAllStateSelects($form, $defaults);

    /**
     * You can define a custom title for the search form
     */
    $this->setTitle('Proximity Search');

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', array(
      'distance',
        'prox_distance_unit',
        'street_address',
        'city',
        'postal_code',
        'country_id',
        'state_province_id',
        'group',
        'tag',
      ));
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL,
    $includeContactIDs = FALSE, $justIDs = FALSE
  ) {
    if ($justIDs) {
      $selectClause = "contact_a.id as contact_id";
    }
    else {
      $selectClause = "
contact_a.id           as contact_id    ,
contact_a.sort_name    as sort_name     ,
address.street_address as street_address,
address.city           as city          ,
address.postal_code    as postal_code   ,
state_province.name    as state_province,
country.name           as country
";
    }

    return $this->sql($selectClause,
      $offset, $rowcount, $sort,
      $includeContactIDs, NULL
    );
  }

  function from() {
    $f = "
FROM      civicrm_contact contact_a
LEFT JOIN civicrm_address address ON ( address.contact_id       = contact_a.id AND
                                       address.is_primary       = 1 )
LEFT JOIN civicrm_state_province state_province ON state_province.id = address.state_province_id
LEFT JOIN civicrm_country country               ON country.id        = address.country_id
";

    // This prevents duplicate rows when contacts have more than one tag any you select "any tag"
    if ($this->_tag) {
      $f .= "
LEFT JOIN civicrm_entity_tag t ON (t.entity_table='civicrm_contact' AND contact_a.id = t.entity_id)
";
    }
    if ($this->_group) {
      $f .= "
LEFT JOIN civicrm_group_contact cgc ON ( cgc.contact_id = contact_a.id AND cgc.status = 'Added')
";
    }

    return $f;
  }

  function where($includeContactIDs = FALSE) {
    $params = array();
    $clause = array();

    $where = CRM_Contact_BAO_ProximityQuery::where($this->_latitude,
      $this->_longitude,
      $this->_distance,
      'address'
    );

    if ($this->_tag) {
      $where .= "
AND t.tag_id = {$this->_tag}
";
    }
    if ($this->_group) {
      $where .= "
AND cgc.group_id = {$this->_group}
 ";
    }

    $where .= " AND contact_a.is_deleted != 1 ";

    return $this->whereClause($where, $params);
  }

  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/Proximity.tpl';
  }

  function setDefaultValues() {
    $config = CRM_Core_Config::singleton();
    $countryDefault = $config->defaultContactCountry;
    $stateprovinceDefault = $config->defaultContactStateProvince;
    $defaults = array();

    if ($countryDefault) {
      if ($countryDefault == '1228' || $countryDefault == '1226') {
        $defaults['prox_distance_unit'] = 'miles';
      }
      else {
        $defaults['prox_distance_unit'] = 'km';
      }
      $defaults['country_id'] = $countryDefault;
      if ($stateprovinceDefault) {
        $defaults['state_province_id'] = $stateprovinceDefault;
      }
      return $defaults;
    }
    return NULL;
  }

  function alterRow(&$row) {}

  function setTitle($title) {
    if ($title) {
      CRM_Utils_System::setTitle($title);
    }
    else {
      CRM_Utils_System::setTitle(ts('Search'));
    }
  }
}

