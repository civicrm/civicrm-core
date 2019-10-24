<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 *  Provide data to the CRM_Contact_Form_Search_Custom_SampleTest class
 *
 * @package CiviCRM
 */
class CRM_Contact_Form_Search_Custom_SampleTestDataProvider implements Iterator {

  /**
   * Current count.
   *
   * @var int
   */
  private $i = 0;

  /**
   * @var mixed[]
   *  This dataset describes various form values and what contact
   *  IDs should be selected when the form values are applied to the
   *  database in dataset.xml
   */
  private $dataset = [
    //  Search by Household name: 'Household 9'
    [
      'fv' => ['household_name' => 'Household 9'],
      'id' => [
        '9',
      ],
    ],
    //  Search by Household name: 'Household'
    [
      'fv' => ['household_name' => 'Household'],
      'id' => [
        '9',
        '10',
        '11',
        '12',
      ],
    ],
    //  Search by State: California
    [
      'fv' => ['state_province_id' => '1004'],
      'id' => [
        '10',
        '11',
      ],
    ],
    //  Search by State: New York
    [
      'fv' => ['state_province_id' => '1031'],
      'id' => [
        '12',
      ],
    ],
  ];

  public function _construct() {
    $this->i = 0;
  }

  public function rewind() {
    $this->i = 0;
  }

  /**
   * @return array
   */
  public function current() {
    $count = count($this->dataset[$this->i]['id']);
    $ids = $this->dataset[$this->i]['id'];
    $full = [];
    foreach ($this->dataset[$this->i]['id'] as $key => $value) {
      $full[] = [
        'contact_id' => $value,
        'contact_type' => 'Household',
        'household_name' => "Household $value",
      ];
    }
    return [$this->dataset[$this->i]['fv'], $count, $ids, $full];
  }

  /**
   * @return int
   */
  public function key() {
    return $this->i;
  }

  public function next() {
    $this->i++;
  }

  /**
   * @return bool
   */
  public function valid() {
    return isset($this->dataset[$this->i]);
  }

}
