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

{if $table.foreignKey}
  {foreach from=$table.foreignKey item=foreign}
     {if $foreign.import}
require_once '{$foreign.fileName}';
     {/if}
  {/foreach}
{/if}

class {$table.className} extends CRM_Core_DAO {ldelim}

     /**
      * static instance to hold the table name
      *
      * @var string
      * @static
      */
      static $_tableName = '{$table.name}';

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
      static $_log = {$table.log};

{foreach from=$table.fields item=field}
    /**
{if $field.comment}
     * {$field.comment}
{/if}
     *
     * @var {$field.phpType}
     */
    public ${$field.name};

{/foreach} {* table.fields *}

    /**
     * class constructor
     *
     * @access public
     * @return {$table.name}
     */
    function __construct( ) {ldelim}
        $this->__table = '{$table.name}';

        parent::__construct( );
    {rdelim}

{if $table.foreignKey}
    /**
     * return foreign links
     *
     * @access public
     * @return array
     */
    function links( ) {ldelim}
  if ( ! ( self::$_links ) ) {ldelim}
       self::$_links = array(
{foreach from=$table.foreignKey item=foreign}
                                   '{$foreign.name}' => '{$foreign.table}:{$foreign.key}',
{/foreach}
                             );
        {rdelim}
        return self::$_links;
    {rdelim}
{/if} {* table.foreignKey *}

      /**
       * returns all the column names of this table
       *
       * @access public
       * @return array
       */
      static function &fields( ) {ldelim}
        if ( ! ( self::$_fields ) ) {ldelim}
               self::$_fields = array (
{foreach from=$table.fields item=field}

{if $field.uniqueName}
                                            '{$field.uniqueName}'
{else}
                                            '{$field.name}'
{/if}
               => array(
                                                                      'name'      => '{$field.name}',
                                                                      'type'      => {$field.crmType},
{if $field.title}
                                                                      'title'     => ts('{$field.title}'),
{/if}
{if $field.required}
                                        'required'  => {$field.required},
{/if} {* field.required *}
{if $field.length}
                      'maxlength' => {$field.length},
{/if} {* field.length *}
{if $field.size}
                      'size'      => {$field.size},
{/if} {* field.size *}
{if $field.rows}
                      'rows'      => {$field.rows},
{/if} {* field.rows *}
{if $field.cols}
                      'cols'      => {$field.cols},
{/if} {* field.cols *}

{if $field.import}
                      'import'    => {$field.import},
                                                                      'where'     => '{$table.name}.{$field.name}',
                                      'headerPattern' => '{$field.headerPattern}',
                                      'dataPattern' => '{$field.dataPattern}',
{/if} {* field.import *}
{if $field.export}
                      'export'    => {$field.export},
                                      {if ! $field.import}
                      'where'     => '{$table.name}.{$field.name}',
                                      'headerPattern' => '{$field.headerPattern}',
                                      'dataPattern' => '{$field.dataPattern}',
              {/if}
{/if} {* field.export *}
{if $field.rule}
                      'rule'      => '{$field.rule}',
{/if} {* field.rule *}
{if $field.default}
                          'default'   => '{$field.default|substring:1:-1}',
{/if} {* field.default *}
{if $field.enumValues}
                          'enumValues' => '{$field.enumValues}',
{/if} {* field.enumValues *}

{if $field.FKClassName}
                      'FKClassName' => '{$field.FKClassName}',
{/if} {* field.FKClassName *}
{if $field.pseudoconstant}
{assign var=pseudoOptions value=$field.pseudoconstant}
'pseudoconstant' => array(
{*{$pseudoOptions|@print_array}*}
{foreach from=$pseudoOptions key=optionKey item=optionValue}
                      '{$optionKey}' => '{$optionValue}',
                      {/foreach}
                )
{/if} {* field.pseudoconstant *}                                                                    ),
{/foreach} {* table.fields *}
                                      );
          {rdelim}
          return self::$_fields;
      {rdelim}

      /**
       * returns the names of this table
       *
       * @access public
       * @static
       * @return string
       */
      static function getTableName( ) {ldelim}
        {if $table.localizable}
          return CRM_Core_DAO::getLocaleTableName( self::$_tableName );
        {else}
          return self::$_tableName;
        {/if}
      {rdelim}

      /**
       * returns if this table needs to be logged
       *
       * @access public
       * @return boolean
       */
      function getLog( ) {ldelim}
          return self::$_log;
      {rdelim}

      /**
       * returns the list of fields that can be imported
       *
       * @access public
       * return array
       * @static
       */
       static function &import( $prefix = false ) {ldelim}
            if ( ! ( self::$_import ) ) {ldelim}
               self::$_import = array ( );
               $fields = self::fields( );
               foreach ( $fields as $name => $field ) {ldelim}
                 if ( CRM_Utils_Array::value( 'import', $field ) ) {ldelim}
                   if ( $prefix ) {ldelim}
                     self::$_import['{$table.labelName}'] =& $fields[$name];
                   {rdelim} else {ldelim}
                     self::$_import[$name] =& $fields[$name];
                   {rdelim}
                 {rdelim}
               {rdelim}
               {if $table.foreignKey}
                  {foreach from=$table.foreignKey item=foreign}
                     {if $foreign.import}
                        self::$_import = array_merge( self::$_import,
                  {$foreign.className}::import( true ) );
                     {/if}
                  {/foreach}
               {/if}
          {rdelim}
          return self::$_import;
      {rdelim}

       /**
       * returns the list of fields that can be exported
       *
       * @access public
       * return array
       * @static
       */
       static function &export( $prefix = false ) {ldelim}
            if ( ! ( self::$_export ) ) {ldelim}
               self::$_export = array ( );
               $fields = self::fields( );
               foreach ( $fields as $name => $field ) {ldelim}
                 if ( CRM_Utils_Array::value( 'export', $field ) ) {ldelim}
                   if ( $prefix ) {ldelim}
                     self::$_export['{$table.labelName}'] =& $fields[$name];
                   {rdelim} else {ldelim}
                     self::$_export[$name] =& $fields[$name];
                   {rdelim}
                 {rdelim}
               {rdelim}
               {if $table.foreignKey}
                   {foreach from=$table.foreignKey item=foreign}
                       {if $foreign.export}
                           self::$_export = array_merge( self::$_export,
                                                        {$foreign.className}::export( true ) );
                       {/if}
                   {/foreach}
               {/if}
          {rdelim}
          return self::$_export;
      {rdelim}

{if $table.hasEnum}
    /**
     * returns an array containing the enum fields of the {$table.name} table
     *
     * @return array (reference)  the array of enum fields
     */
    static function &getEnums() {ldelim}
        static $enums = array(
            {foreach from=$table.fields item=field}
                {if $field.crmType == 'CRM_Utils_Type::T_ENUM'}
                    '{$field.name}',
                {/if}
            {/foreach}
        );
        return $enums;
    {rdelim}

    /**
     * returns a ts()-translated enum value for display purposes
     *
     * @param string $field  the enum field in question
     * @param string $value  the enum value up for translation
     *
     * @return string  the display value of the enum
     */
    static function tsEnum($field, $value) {ldelim}
        static $translations = null;
        if (!$translations) {ldelim}
            $translations = array(
                {foreach from=$table.fields item=field}
                    {if $field.crmType == 'CRM_Utils_Type::T_ENUM'}
                        '{$field.name}' => array(
                            {foreach from=$field.values item=value}
                                '{$value}' => ts('{$value}'),
                            {/foreach}
                        ),
                    {/if}
                {/foreach}
            );
        {rdelim}
        return $translations[$field][$value];
    {rdelim}

    /**
     * adds $value['foo_display'] for each $value['foo'] enum from {$table.name}
     *
     * @param array $values (reference)  the array up for enhancing
     * @return void
     */
    static function addDisplayEnums(&$values) {ldelim}
        $enumFields =& {$table.className}::getEnums();
        foreach ($enumFields as $enum) {ldelim}
            if (isset($values[$enum])) {ldelim}
                $values[$enum.'_display'] = {$table.className}::tsEnum($enum, $values[$enum]);
            {rdelim}
        {rdelim}
    {rdelim}
{/if}

{rdelim}


