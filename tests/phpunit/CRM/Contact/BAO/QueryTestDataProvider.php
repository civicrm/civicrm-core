<?php
// vim: set si ai expandtab tabstop=4 shiftwidth=4 softtabstop=4:

/**
 *  File for the CRM_Contact_BAO_Query class
 *
 *  (PHP 5)
 *
 *   @author Walt Haas <walt@dharmatech.org> (801) 534-1262
 *   @copyright Copyright CiviCRM LLC (C) 2009
 *   @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 *   @version   $Id: GroupTestDataProvider.php 23715 2009-09-21 06:35:47Z shot $
 *   @package CiviCRM
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
 *  Provide data to the CRM_Contact_BAO_QueryTest class
 *
 *  @package CiviCRM
 */
class CRM_Contact_BAO_QueryTestDataProvider implements Iterator {

  /**
   *  @var integer
   */
  private $i = 0;

  /**
   *  @var mixed[]
   *  This dataset describes various form values and what contact
   *  IDs should be selected when the form values are applied to the
   *  database in dataset.xml
   */
  private $dataset = array(
    //  Include static group 3
    array('fv' => array('group' => array('3' => 1)),
      'id' => array(
        '17', '18', '19', '20', '21',
        '22', '23', '24',
      ),
    ),
    //  Include static group 5
    array('fv' => array('group' => array('5' => 1)),
      'id' => array(
        '13', '14', '15', '16', '21',
        '22', '23', '24',
      ),
    ),
    //  Include static groups 3 and 5
    array(
      'fv' => array('group' => array('3' => 1,
          '5' => 1,
        )),
      'id' => array(
        '13', '14', '15', '16', '17', '18',
        '19', '20', '21', '22', '23', '24',
      ),
    ),
    //  Include tag 7
    array('fv' => array('tag' => array('7' => 1)),
      'id' => array(
        '11', '12', '15', '16',
        '19', '20', '23', '24',
      ),
    ),
    //  Include tag 9
    array('fv' => array('tag' => array('9' => 1)),
      'id' => array(
        '10', '12', '14', '16',
        '18', '20', '22', '24',
      ),
    ),
    //  Include tags 7 and 9
    array(
      'fv' => array('tag' => array('7' => 1,
          '9' => 1,
        )),
      'id' => array(
        '10', '11', '12', '14', '15', '16',
        '18', '19', '20', '22', '23', '24',
      ),
    ),
  );

  public function _construct() {
    $this->i = 0;
  }

  public function rewind() {
    $this->i = 0;
  }

  public function current() {
    $count = count($this->dataset[$this->i]['id']);
    $ids   = $this->dataset[$this->i]['id'];
    $full  = array();
    foreach ($this->dataset[$this->i]['id'] as $key => $value) {
      $full[] = array(
        'contact_id' => $value,
        'contact_type' => 'Individual',
        'sort_name' => "Test Contact $value",
      );
    }
    return array($this->dataset[$this->i]['fv'], $count, $ids, $full);
  }

  public function key() {
    return $this->i;
  }

  public function next() {
    $this->i++;
  }

  public function valid() {
    return isset($this->dataset[$this->i]);
  }
}
// class CRM_Contact_BAO_QueryTestDataProvider

// -- set Emacs parameters --
// Local variables:
// mode: php;
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:

