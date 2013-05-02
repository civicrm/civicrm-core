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
require_once 'CRM/Core/DAO.php';
require_once 'CRM/Utils/Type.php';
class CRM_Upgrade_Snapshot_V4p2_Price_DAO_Set extends CRM_Core_DAO
{
    /**
     * static instance to hold the table name
     *
     * @var string
     * @static
     */
    static $_tableName = 'civicrm_price_set';
    /**
     * static instance to hold the field values
     *
     * @var array
     * @static
     */
    static $_fields = null;
    /**
     * static instance to hold the FK relationships
     *
     * @var string
     * @static
     */
    static $_links = null;
    /**
     * static instance to hold the values that can
     * be imported
     *
     * @var array
     * @static
     */
    static $_import = null;
    /**
     * static instance to hold the values that can
     * be exported
     *
     * @var array
     * @static
     */
    static $_export = null;
    /**
     * static value to see if we should log any modifications to
     * this table in the civicrm_log table
     *
     * @var boolean
     * @static
     */
    static $_log = true;
    /**
     * Price Set
     *
     * @var int unsigned
     */
    public $id;
    /**
     * Which Domain is this price-set for
     *
     * @var int unsigned
     */
    public $domain_id;
    /**
     * Variable name/programmatic handle for this set of price fields.
     *
     * @var string
     */
    public $name;
    /**
     * Displayed title for the Price Set.
     *
     * @var string
     */
    public $title;
    /**
     * Is this price set active
     *
     * @var boolean
     */
    public $is_active;
    /**
     * Description and/or help text to display before fields in form.
     *
     * @var text
     */
    public $help_pre;
    /**
     * Description and/or help text to display after fields in form.
     *
     * @var text
     */
    public $help_post;
    /**
     * Optional Javascript script function(s) included on the form with this price_set. Can be used for conditional
     *
     * @var string
     */
    public $javascript;
    /**
     * What components are using this price set?
     *
     * @var string
     */
    public $extends;
    /**
     * FK to Contribution Type(for membership price sets only).
     *
     * @var int unsigned
     */
    public $contribution_type_id;
    /**
     * Is set if edited on Contribution or Event Page rather than through Manage Price Sets
     *
     * @var boolean
     */
    public $is_quick_config;
    /**
     * Is this a predefined system price set  (i.e. it can not be deleted, edited)?
     *
     * @var boolean
     */
    public $is_reserved;
    /**
     * class constructor
     *
     * @access public
     * @return civicrm_price_set
     */
    function __construct()
    {
        $this->__table = 'civicrm_price_set';
        parent::__construct();
    }
    /**
     * return foreign links
     *
     * @access public
     * @return array
     */
    function links()
    {
        if (!(self::$_links)) {
            self::$_links = array(
                'domain_id' => 'civicrm_domain:id',
                'contribution_type_id' => 'civicrm_contribution_type:id',
            );
        }
        return self::$_links;
    }
    /**
     * returns all the column names of this table
     *
     * @access public
     * @return array
     */
    static function &fields()
    {
        if (!(self::$_fields)) {
            self::$_fields = array(
                'id' => array(
                    'name' => 'id',
                    'type' => CRM_Utils_Type::T_INT,
                    'required' => true,
                ) ,
                'domain_id' => array(
                    'name' => 'domain_id',
                    'type' => CRM_Utils_Type::T_INT,
                    'FKClassName' => 'CRM_Core_DAO_Domain',
                ) ,
                'name' => array(
                    'name' => 'name',
                    'type' => CRM_Utils_Type::T_STRING,
                    'title' => ts('Name') ,
                    'required' => true,
                    'maxlength' => 255,
                    'size' => CRM_Utils_Type::HUGE,
                ) ,
                'title' => array(
                    'name' => 'title',
                    'type' => CRM_Utils_Type::T_STRING,
                    'title' => ts('Title') ,
                    'required' => true,
                    'maxlength' => 255,
                    'size' => CRM_Utils_Type::HUGE,
                ) ,
                'is_active' => array(
                    'name' => 'is_active',
                    'type' => CRM_Utils_Type::T_BOOLEAN,
                    'default' => '',
                ) ,
                'help_pre' => array(
                    'name' => 'help_pre',
                    'type' => CRM_Utils_Type::T_TEXT,
                    'title' => ts('Help Pre') ,
                    'rows' => 4,
                    'cols' => 80,
                ) ,
                'help_post' => array(
                    'name' => 'help_post',
                    'type' => CRM_Utils_Type::T_TEXT,
                    'title' => ts('Help Post') ,
                    'rows' => 4,
                    'cols' => 80,
                ) ,
                'javascript' => array(
                    'name' => 'javascript',
                    'type' => CRM_Utils_Type::T_STRING,
                    'title' => ts('Javascript') ,
                    'maxlength' => 64,
                    'size' => CRM_Utils_Type::BIG,
                ) ,
                'extends' => array(
                    'name' => 'extends',
                    'type' => CRM_Utils_Type::T_STRING,
                    'title' => ts('Extends') ,
                    'required' => true,
                    'maxlength' => 255,
                    'size' => CRM_Utils_Type::HUGE,
                ) ,
                'contribution_type_id' => array(
                    'name' => 'contribution_type_id',
                    'type' => CRM_Utils_Type::T_INT,
                    'default' => 'UL',
                    'FKClassName' => 'CRM_Contribute_DAO_ContributionType',
                ) ,
                'is_quick_config' => array(
                    'name' => 'is_quick_config',
                    'type' => CRM_Utils_Type::T_BOOLEAN,
                ) ,
                'is_reserved' => array(
                    'name' => 'is_reserved',
                    'type' => CRM_Utils_Type::T_BOOLEAN,
                ) ,
            );
        }
        return self::$_fields;
    }
    /**
     * returns the names of this table
     *
     * @access public
     * @static
     * @return string
     */
    static function getTableName()
    {
        return CRM_Core_DAO::getLocaleTableName(self::$_tableName);
    }
    /**
     * returns if this table needs to be logged
     *
     * @access public
     * @return boolean
     */
    function getLog()
    {
        return self::$_log;
    }
    /**
     * returns the list of fields that can be imported
     *
     * @access public
     * return array
     * @static
     */
    static function &import($prefix = false)
    {
        if (!(self::$_import)) {
            self::$_import = array();
            $fields = self::fields();
            foreach($fields as $name => $field) {
                if (CRM_Utils_Array::value('import', $field)) {
                    if ($prefix) {
                        self::$_import['price_set'] = & $fields[$name];
                    } else {
                        self::$_import[$name] = & $fields[$name];
                    }
                }
            }
        }
        return self::$_import;
    }
    /**
     * returns the list of fields that can be exported
     *
     * @access public
     * return array
     * @static
     */
    static function &export($prefix = false)
    {
        if (!(self::$_export)) {
            self::$_export = array();
            $fields = self::fields();
            foreach($fields as $name => $field) {
                if (CRM_Utils_Array::value('export', $field)) {
                    if ($prefix) {
                        self::$_export['price_set'] = & $fields[$name];
                    } else {
                        self::$_export[$name] = & $fields[$name];
                    }
                }
            }
        }
        return self::$_export;
    }
}
