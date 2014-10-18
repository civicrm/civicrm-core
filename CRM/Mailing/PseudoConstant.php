<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
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

/**
 * This class holds all the Pseudo constants that are specific to Mass mailing. This avoids
 * polluting the core class and isolates the mass mailer class
 */
class CRM_Mailing_PseudoConstant extends CRM_Core_PseudoConstant {

  /**
   * mailing templates
   * @var array
   * @static
   */
  private static $template;

  /**
   * completed mailings
   * @var array
   * @static
   */
  private static $completed;

  /**
   * mailing components
   * @var array
   * @static
   */
  private static $component;

  /**
   * default component id's, indexed by component type
   */
  private static $defaultComponent;

  /**
   * Get all the mailing components of a particular type
   *
   * @param $type the type of component needed
   * @access public
   *
   * @return array - array reference of all mailing components
   * @static
   */
  public static function &component($type = NULL) {
    $name = $type ? $type : 'ALL';

    if (!self::$component || !array_key_exists($name, self::$component)) {
      if (!self::$component) {
        self::$component = array();
      }
      if (!$type) {
        self::$component[$name] = NULL;
        CRM_Core_PseudoConstant::populate(self::$component[$name], 'CRM_Mailing_DAO_Component');
      }
      else {
        // we need to add an additional filter for $type
        self::$component[$name] = array();


        $object = new CRM_Mailing_DAO_Component();
        $object->component_type = $type;
        $object->selectAdd();
        $object->selectAdd("id, name");
        $object->orderBy('component_type, is_default, name');
        $object->is_active = 1;
        $object->find();
        while ($object->fetch()) {
          self::$component[$name][$object->id] = $object->name;
        }
      }
    }
    return self::$component[$name];
  }

  /**
   * Determine the default mailing component of a given type
   *
   * @param $type the type of component needed
   * @param $undefined the value to use if no default is defined
   * @access public
   *
   * @return integer -The ID of the default mailing component.
   * @static
   */
  public static function &defaultComponent($type, $undefined = NULL) {
    if (!self::$defaultComponent) {
      $queryDefaultComponents = "SELECT id, component_type
                FROM    civicrm_mailing_component
                WHERE   is_active = 1
                AND     is_default = 1
                GROUP BY component_type";

      $dao = CRM_Core_DAO::executeQuery($queryDefaultComponents);

      self::$defaultComponent = array();
      while ($dao->fetch()) {
        self::$defaultComponent[$dao->component_type] = $dao->id;
      }
    }
    $value = CRM_Utils_Array::value($type, self::$defaultComponent, $undefined);
    return $value;
  }

  /**
   * Get all the mailing templates
   *
   * @access public
   *
   * @return array - array reference of all mailing templates if any
   * @static
   */
  public static function &template() {
    if (!self::$template) {
      CRM_Core_PseudoConstant::populate(self::$template, 'CRM_Mailing_DAO_Mailing', TRUE, 'name', 'is_template');
    }
    return self::$template;
  }

  /**
   * Get all the completed mailing
   *
   * @access public
   *
   * @param null $mode
   *
   * @return array - array reference of all mailing templates if any
   * @static
   */
  public static function &completed($mode = NULL) {
    if (!self::$completed) {
      $mailingACL = CRM_Mailing_BAO_Mailing::mailingACL();
      $mailingACL .= $mode == 'sms' ? " AND sms_provider_id IS NOT NULL " : "";

      CRM_Core_PseudoConstant::populate(self::$completed,
        'CRM_Mailing_DAO_Mailing',
        FALSE,
        'name',
        'is_completed',
        $mailingACL
      );
    }
    return self::$completed;
  }

  /**
   * Labels for advanced search against mailing summary.
   *
   * @param $field
   *
   * @return unknown_type
   */
  public static function &yesNoOptions($field) {
    static $options;
    if (!$options) {
      $options = array(
        'bounce' => array(
          'N' => ts('Successful '), 'Y' => ts('Bounced '),
        ),
        'delivered' => array(
          'Y' => ts('Successful '), 'N' => ts('Bounced '),
        ),
        'open' => array(
          'Y' => ts('Opened '), 'N' => ts('Unopened/Hidden '),
        ),
        'click' => array(
          'Y' => ts('Clicked '), 'N' => ts('Not Clicked '),
        ),
        'reply' => array(
          'Y' => ts('Replied '), 'N' => ts('No Reply '),
        ),
      );
    }
    return $options[$field];
  }

  /**
   * Flush given pseudoconstant so it can be reread from db
   * next time it's requested.
   *
   * @access public
   * @static
   *
   * @param bool|string $name pseudoconstant to be flushed
   */
  public static function flush($name = 'template') {
   if (isset(self::$$name)) {
      self::$$name = NULL;
    }
  }
}

