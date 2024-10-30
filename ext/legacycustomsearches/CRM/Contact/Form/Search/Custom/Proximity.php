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
 * This search now functions as a subset of advanced search since at some point it
 * was added to advanced search.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contact_Form_Search_Custom_Proximity extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_aclFrom = NULL;
  protected $_aclWhere = NULL;

  /**
   * Class constructor.
   *
   * @param array $formValues
   *
   * @throws Exception
   */
  public function __construct(&$formValues) {
    parent::__construct($formValues);

    // unset search profile and other search params if set
    unset($this->_formValues['uf_group_id']);
    unset($this->_formValues['component_mode']);
    unset($this->_formValues['operator']);
    $this->_group = $this->_formValues['group'] ?? NULL;

    $this->_tag = $this->_formValues['tag'] ?? NULL;

    $this->_columns = [
      ts('Name') => 'sort_name',
      ts('Street Address') => 'street_address',
      ts('City') => 'city',
      ts('Postal Code') => 'postal_code',
      ts('State') => 'state_province',
      ts('Country') => 'country',
    ];
  }

  /**
   * Get the query object for this selector.
   *
   * @return CRM_Contact_BAO_Query
   */
  public function getQueryObj() {
    return $this->_query;
  }

  /**
   * @param CRM_Core_Form $form
   */
  public function buildForm(&$form) {

    $config = CRM_Core_Config::singleton();
    $countryDefault = $config->defaultContactCountry;

    $form->add('text', 'distance', ts('Distance'), NULL, TRUE);

    $proxUnits = ['km' => ts('km'), 'miles' => ts('miles')];
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

    $defaults = [];
    if ($countryDefault) {
      $defaults['country_id'] = $countryDefault;
    }
    $form->addChainSelect('state_province_id');

    $country = ['' => ts('- select -')] + CRM_Core_PseudoConstant::country();
    $form->add('select', 'country_id', ts('Country'), $country, TRUE, ['class' => 'crm-select2']);

    $form->add('text', 'geo_code_1', ts('Latitude'));
    $form->add('text', 'geo_code_2', ts('Longitude'));

    $group = ['' => ts('- any group -')] + CRM_Core_PseudoConstant::nestedGroup();
    $form->addElement('select', 'group', ts('Group'), $group, ['class' => 'crm-select2 huge']);

    $tag = ['' => ts('- any tag -')] + CRM_Core_DAO_EntityTag::buildOptions('tag_id', 'get');
    $form->addElement('select', 'tag', ts('Tag'), $tag, ['class' => 'crm-select2 huge']);

    /**
     * You can define a custom title for the search form
     */
    $this->setTitle(ts('Proximity Search'));

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', [
      'distance',
      'prox_distance_unit',
      'street_address',
      'city',
      'postal_code',
      'country_id',
      'state_province_id',
      'group',
      'tag',
    ]);
  }

  /**
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   *
   * @return string
   */
  public function all(
    $offset = 0, $rowcount = 0, $sort = NULL,
    $includeContactIDs = FALSE, $justIDs = FALSE
  ) {
    $selectClause = $justIDs ? "contact_a.id as contact_id" : NULL;

    return $this->sql($selectClause,
      $offset, $rowcount, $sort,
      $includeContactIDs, NULL
    );
  }

  /**
   * Override sql() function to use the Query object rather than generating on the form.
   *
   * @param string $selectClause
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param null $groupBy
   *
   * @return string
   */
  public function sql(
    $selectClause,
    $offset = 0,
    $rowcount = 0,
    $sort = NULL,
    $includeContactIDs = FALSE,
    $groupBy = NULL
  ) {

    $isCountOnly = FALSE;
    if ($selectClause === 'count(distinct contact_a.id) as total') {
      $isCountOnly = TRUE;
    }

    if (empty($this->_formValues['geo_code_1']) ||  empty($this->_formValues['geo_code_2'])) {
      self::addGeocodingData($this->_formValues);
    }

    $searchParams = [
      ['prox_distance_unit', '=', $this->_formValues['prox_distance_unit'], 0, 0],
      ['prox_distance', '=', $this->_formValues['distance'], 0, 0],
      ['prox_geo_code_1', '=', $this->_formValues['geo_code_1'], 0, 0],
      ['prox_geo_code_2', '=', $this->_formValues['geo_code_2'], 0, 0],
    ];
    if (!empty($this->_formValues['group'])) {
      $searchParams[] = ['group', '=', ['IN', (array) $this->_formValues['group']][1], 0, 0];
    }
    if (!empty($this->_formValues['tag'])) {
      $searchParams[] = ['contact_tags', '=', ['IN', (array) $this->_formValues['tag']][1], 0, 0];
    }

    $display = array_fill_keys(['city', 'state_province', 'country', 'postal_code', 'street_address', 'display_name', 'sort_name'], 1);
    if ($selectClause === 'contact_a.id as contact_id') {
      // Not sure when this would happen but calling all with 'justIDs' gets us here.
      $display = ['contact_id' => 1];
    }

    $this->_query = new CRM_Contact_BAO_Query($searchParams, $display);
    return $this->_query->searchQuery(
      $offset,
      $rowcount,
      $sort,
      $isCountOnly,
      $includeContactIDs,
      FALSE,
      $isCountOnly,
      $returnQuery = TRUE
    );

  }

  /**
   * @return string
   */
  public function from() {
    //unused
    return '';
  }

  /**
   * @param bool $includeContactIDs
   *
   * @return string
   */
  public function where($includeContactIDs = FALSE) {
    //unused
    return '';
  }

  /**
   * @return string
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/Proximity.tpl';
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    if (!empty($this->_formValues)) {
      return $this->_formValues;
    }
    $config = CRM_Core_Config::singleton();
    $countryDefault = $config->defaultContactCountry;
    $stateprovinceDefault = $config->defaultContactStateProvince;
    $defaults = [
      'prox_distance_unit' => CRM_Utils_Address::getDefaultDistanceUnit(),
    ];

    if ($countryDefault) {
      $defaults['country_id'] = $countryDefault;
      if ($stateprovinceDefault) {
        $defaults['state_province_id'] = $stateprovinceDefault;
      }
    }
    return $defaults;
  }

  /**
   * @param $row
   */
  public function alterRow(&$row) {
  }

  /**
   * Validate form input.
   *
   * @param array $fields
   * @param array $files
   * @param CRM_Core_Form $self
   *
   * @return array
   *   Input errors from the form.
   */
  public function formRule($fields, $files, $self) {
    $this->addGeocodingData($fields);

    if (!is_numeric($fields['geo_code_1'] ?? '') ||
      !is_numeric($fields['geo_code_2'] ?? '') ||
      !isset($fields['distance'])
    ) {
      $errorMessage = ts('Could not determine co-ordinates for provided data');
      return array_fill_keys(['street_address', 'city', 'postal_code', 'country_id', 'state_province_id'], $errorMessage);
    }
    return [];
  }

  /**
   * Add the geocoding data to the fields supplied.
   *
   * @param array $fields
   */
  protected function addGeocodingData(&$fields) {
    if (!empty($fields['country_id'])) {
      $fields['country'] = CRM_Core_PseudoConstant::country($fields['country_id']);
    }

    if (!empty($fields['state_province_id'])) {
      $fields['state_province'] = CRM_Core_PseudoConstant::stateProvince($fields['state_province_id']);
    }
    CRM_Core_BAO_Address::addGeocoderData($fields);
  }

}
