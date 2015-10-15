<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 *
 * Generated from {$table.sourceFile}
 * {$generated}
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
      */
      static $_tableName = '{$table.name}';

     /**
      * static instance to hold the field values
      *
      * @var array
      */
      static $_fields = null;

     /**
      * static instance to hold the keys used in $_fields for each field.
      *
      * @var array
      */
      static $_fieldKeys = null;

     /**
      * static instance to hold the FK relationships
      *
      * @var string
      */
      static $_links = null;

     /**
      * static instance to hold the values that can
      * be imported
      *
      * @var array
      */
      static $_import = null;

      /**
       * static instance to hold the values that can
       * be exported
       *
       * @var array
       */
      static $_export = null;

      /**
       * static value to see if we should log any modifications to
       * this table in the civicrm_log table
       *
       * @var boolean
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
     * @return {$table.name}
     */
    function __construct( ) {ldelim}
        $this->__table = '{$table.name}';

        parent::__construct( );
    {rdelim}

{if $table.foreignKey || $table.dynamicForeignKey}
    /**
     * Returns foreign keys and entity references
     *
     * @return array
     *   [CRM_Core_Reference_Interface]
     */
    static function getReferenceColumns() {ldelim}
      if (!self::$_links) {ldelim}
        self::$_links = static::createReferenceColumns(__CLASS__);
{foreach from=$table.foreignKey item=foreign}
        self::$_links[] = new CRM_Core_Reference_Basic(self::getTableName(), '{$foreign.name}', '{$foreign.table}', '{$foreign.key}');
{/foreach}

{foreach from=$table.dynamicForeignKey item=foreign}
        self::$_links[] = new CRM_Core_Reference_Dynamic(self::getTableName(), '{$foreign.idColumn}', NULL, '{$foreign.key|default:'id'}', '{$foreign.typeColumn}');
{/foreach}
      {rdelim}
      return self::$_links;
    {rdelim}
{/if} {* table.foreignKey *}

      /**
       * Returns all the column names of this table
       *
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
{if $field.comment}
                                                                      'description'     => '{$field.comment|replace:"'":"\'"}',
{/if}
{if $field.required}
                                        'required'  => {$field.required},
{/if} {* field.required *}
{if $field.length}
                      'maxlength' => {$field.length},
{/if} {* field.length *}
{if $field.precision}
                      'precision'      => array({$field.precision}),
{/if}
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
                         'default'   => '{if ($field.default[0]=="'" or $field.default[0]=='"')}{$field.default|substring:1:-1}{else}{$field.default}{/if}',
{/if} {* field.default *}

{if $field.FKClassName}
                      'FKClassName' => '{$field.FKClassName}',
{/if} {* field.FKClassName *}
{if $field.html}
  {assign var=htmlOptions value=$field.html}
  'html' => array(
{*{$htmlOptions|@print_array}*}
  {foreach from=$htmlOptions key=optionKey item=optionValue}
    '{$optionKey}' => '{$optionValue}',
  {/foreach}
  ),
{/if} {* field.html *}
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
       * Returns an array containing, for each field, the arary key used for that
       * field in self::$_fields.
       *
       * @return array
       */
      static function &fieldKeys( ) {ldelim}
        if ( ! ( self::$_fieldKeys ) ) {ldelim}
               self::$_fieldKeys = array (
{foreach from=$table.fields item=field}
                    '{$field.name}' =>
{if $field.uniqueName}
                                            '{$field.uniqueName}'
{else}
                                            '{$field.name}'
{/if},

{/foreach} {* table.fields *}
                                      );
          {rdelim}
          return self::$_fieldKeys;
      {rdelim}

      /**
       * Returns the names of this table
       *
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
       * Returns if this table needs to be logged
       *
       * @return boolean
       */
      function getLog( ) {ldelim}
          return self::$_log;
      {rdelim}

      /**
       * Returns the list of fields that can be imported
       *
       * @param bool $prefix
       *
       * @return array
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
        * Returns the list of fields that can be exported
        *
        * @param bool $prefix
        *
        * @return array
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

{rdelim}


