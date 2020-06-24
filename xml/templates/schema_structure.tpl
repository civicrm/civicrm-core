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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 *
 * Generated from {$smarty.template}
 * {$generated}
 */
class CRM_Core_I18n_SchemaStructure {ldelim}

  /**
   * Get translatable columns.
   *
   * @return array
   *   A table-indexed array of translatable columns.
   */
  public static function &columns() {ldelim}
    static $result = NULL;
    if (!$result) {ldelim}
      $result = [
{foreach from=$columns key=table item=types}
        '{$table}' => [
{foreach from=$types key=column item=type}
          '{$column}' => "{$type}",
{/foreach}{* /foreach from=$types item=type *}
        ],
{/foreach}{* /foreach from=$columns item=types *}
      ];
    {rdelim}
    return $result;
  {rdelim}

  /**
   * Get a table indexed array of the indices for translatable fields.
   *
   * @return array
   *   Indices for translatable fields.
   */
  public static function &indices() {ldelim}
    static $result = NULL;
    if (!$result) {ldelim}
      $result = [
{foreach from=$indices key=table item=tableIndices}
        '{$table}' => [
{foreach from=$tableIndices key=name item=info}
          '{$name}' => [
            'name' => '{$info.name}',
            'field' => [
{foreach from=$info.field item=field}
              '{$field}',
{/foreach}{* foreach from=$info.field item=field *}
            ],
            {if $info.unique}'unique' => 1,{/if}

          ],
{/foreach}{* /foreach from=$tableIndices item=info *}
        ],
{/foreach}{* /foreach from=$indices item=tableIndices *}
      ];
    {rdelim}
    return $result;
  {rdelim}

  /**
   * Get tables with translatable fields.
   *
   * @return array
   *   Array of names of tables with fields that can be translated.
   */
  public static function &tables() {ldelim}
    static $result = NULL;
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
  public static function &widgets() {ldelim}
    static $result = NULL;
    if (!$result) {ldelim}
      $result = [
{foreach from=$widgets key=table item=columns}
        '{$table}' => [
{foreach from=$columns key=column item=widget}
          '{$column}' => [
{foreach from=$widget key=name item=value}
            '{$name}' => "{$value}",
{/foreach}{* /foreach from=$widget item=value *}
          ],
{/foreach}{* /foreach from=$columns item=widget *}
        ],
{/foreach}{* /foreach from=$widgets item=columns *}
      ];
    {rdelim}
    return $result;
  {rdelim}

{rdelim}
