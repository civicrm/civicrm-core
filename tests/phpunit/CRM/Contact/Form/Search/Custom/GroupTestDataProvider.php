<?php

/**
 *  File for the CRM_Contact_Form_Search_Custom_GroupTestDataProvider class
 *
 *  (PHP 5)
 *
 * @author Walt Haas <walt@dharmatech.org> (801) 534-1262
 * @copyright Copyright CiviCRM LLC (C) 2009
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 * @version   $Id: GroupTestDataProvider.php 44314 2012-12-19 11:12:49Z kurund $
 * @package CiviCRM
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

/**
 *  Provide data to the CRM_Contact_Form_Search_Custom_GroupTest class
 *
 * @package CiviCRM
 */
class CRM_Contact_Form_Search_Custom_GroupTestDataProvider implements Iterator {

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
    //  Exclude static group 3
    [
      'fv' => ['excludeGroups' => ['3']],
      'id' => [
        '9',
        '10',
        '11',
        '12',
        '13',
        '14',
        '15',
        '16',
        '26',
      ],
    ],
    //  Include static group 3
    [
      'fv' => ['includeGroups' => ['3']],
      'id' => [
        '17',
        '18',
        '19',
        '20',
        '21',
        '22',
        '23',
        '24',
        '27',
        '28',
      ],
    ],
    //  Include static group 5
    [
      'fv' => ['includeGroups' => ['5']],
      'id' => [
        '13',
        '14',
        '15',
        '16',
        '21',
        '22',
        '23',
        '24',
      ],
    ],
    //  Include static groups 3 and 5
    [
      'fv' => ['includeGroups' => ['3', '5']],
      'id' => [
        '13',
        '14',
        '15',
        '16',
        '17',
        '18',
        '19',
        '20',
        '21',
        '22',
        '23',
        '24',
        '27',
        '28',
      ],
    ],
    //  Include static group 3, exclude static group 5
    [
      'fv' => [
        'includeGroups' => ['3'],
        'excludeGroups' => ['5'],
      ],
      'id' => ['17', '18', '19', '20', '27', '28'],
    ],
    //  Exclude tag 7
    [
      'fv' => ['excludeTags' => ['7']],
      'id' => [
        '9',
        '10',
        '13',
        '14',
        '17',
        '18',
        '21',
        '22',
        '27',
      ],
    ],
    //  Include tag 7
    [
      'fv' => ['includeTags' => ['7']],
      'id' => [
        '11',
        '12',
        '15',
        '16',
        '19',
        '20',
        '23',
        '24',
        '26',
        '28',
      ],
    ],
    //  Include tag 9
    [
      'fv' => ['includeTags' => ['9']],
      'id' => [
        '10',
        '12',
        '14',
        '16',
        '18',
        '20',
        '22',
        '24',
      ],
    ],
    //  Include tags 7 and 9
    [
      'fv' => ['includeTags' => ['7', '9']],
      'id' => [
        '10',
        '11',
        '12',
        '14',
        '15',
        '16',
        '18',
        '19',
        '20',
        '22',
        '23',
        '24',
        '26',
        '28',
      ],
    ],
    //  Include tag 7, exclude tag 9
    [
      'fv' => [
        'includeTags' => ['7'],
        'excludeTags' => ['9'],
      ],
      'id' => ['11', '15', '19', '23', '26', '28'],
    ],
    //  Include static group 3, include tag 7 (either)
    [
      'fv' => [
        'includeGroups' => ['3'],
        'includeTags' => ['7'],
        'andOr' => 0,
      ],
      'id' => [
        '11',
        '12',
        '15',
        '16',
        '17',
        '18',
        '19',
        '20',
        '21',
        '22',
        '23',
        '24',
        '26',
        '27',
        '28',
      ],
    ],
    //  Include static group 3, include tag 7 (both)
    [
      'fv' => [
        'includeGroups' => ['3'],
        'includeTags' => ['7'],
        'andOr' => 1,
      ],
      'id' => ['19', '20', '23', '24', '28'],
    ],
    //  Include static group 3, exclude tag 7
    [
      'fv' => [
        'includeGroups' => ['3'],
        'excludeTags' => ['7'],
      ],
      'id' => ['17', '18', '21', '22', '27'],
    ],
    //  Include tag 9, exclude static group 5
    [
      'fv' => [
        'includeTags' => ['9'],
        'excludeGroups' => ['5'],
      ],
      'id' => ['10', '12', '18', '20'],
    ],
    //  Exclude tag 9, exclude static group 5
    [
      'fv' => [
        'excludeTags' => ['9'],
        'excludeGroups' => ['5'],
      ],
      'id' => [
        '9',
        '11',
        '17',
        '19',
        '26',
        '27',
        '28',
      ],
    ],
    //  Include smart group 6
    [
      'fv' => ['includeGroups' => ['6']],
      'id' => [
        '9',
        '10',
        '11',
        '12',
        '13',
        '14',
        '15',
        '16',
        '26',
      ],
    ],
    //  Include smart group 4
    [
      'fv' => ['includeGroups' => ['4']],
      'id' => [
        '17',
        '18',
        '19',
        '20',
        '21',
        '22',
        '23',
        '24',
        '27',
        '28',
      ],
    ],
    //  Include smart group 4 and static group 5
    [
      'fv' => ['includeGroups' => ['4', '5']],
      'id' => [
        '13',
        '14',
        '15',
        '16',
        '17',
        '18',
        '19',
        '20',
        '21',
        '22',
        '23',
        '24',
        '27',
        '28',
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
        'contact_type' => 'Individual',
        'sort_name' => "Test Contact $value",
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
