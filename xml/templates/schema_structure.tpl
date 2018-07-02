<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 *
 * Generated from {$smarty.template}
 * {$generated}
 */
class CRM_Core_I18n_SchemaStructure
{ldelim}

    /**
     * Get translatable columns.
     *
     * @return array
     *   A table-indexed array of translatable columns.
     */
    public static function &columns()
    {ldelim}
        static $result = null;
        if (!$result) {ldelim}
          $result = array(
            {foreach from=$columns key=table item=types}
              '{$table}' => array(
                {foreach from=$types key=column item=type}
                  '{$column}' => "{$type}",
                {/foreach}
              ),
            {/foreach}
          );
        {rdelim}
        return $result;
    {rdelim}

    /**
     * Get a table indexed array of the indices for translatable fields.
     *
     * @return array
     *   Indices for translatable fields.
     */
    public static function &indices()
    {ldelim}
        static $result = null;
        if (!$result) {ldelim}
          $result = array(
            {foreach from=$indices key=table item=tableIndices}
              '{$table}' => array(
                {foreach from=$tableIndices key=name item=info}
                  '{$name}' => array(
                      'name' => '{$info.name}',
                      'field' => array(
                        {foreach from=$info.field item=field}
                          '{$field}',
                        {/foreach}
                      ),
                      {if $info.unique}'unique' => 1,{/if}
                  ),
                {/foreach}
              ),
            {/foreach}
          );
        {rdelim}
        return $result;
    {rdelim}

    /**
     * Get tables with translatable fields.
     *
     * @return array
     *   Array of names of tables with fields that can be translated.
     */
    static function &tables()
    {ldelim}
        static $result = null;
        if (!$result) {ldelim}
            $result = array_keys(self::columns());
        {rdelim}
        return $result;
    {rdelim}

    /**
     * Get a list of widgets for editing translatable fields.
     *
     * @return array
     *   Array of the widgets for editing translatable fields.
     */
    static function &widgets()
    {ldelim}
        static $result = null;
        if (!$result) {ldelim}
          $result = array(
            {foreach from=$widgets key=table item=columns}
              '{$table}' => array(
                {foreach from=$columns key=column item=widget}
                  '{$column}' => array(
                    {foreach from=$widget key=name item=value}
                      '{$name}' => "{$value}",
                    {/foreach}
                  ),
                {/foreach}
              ),
            {/foreach}
          );
        {rdelim}
        return $result;
    {rdelim}
{rdelim}
