-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC. All rights reserved.                        |
-- |                                                                    |
-- | This work is published under the GNU AGPLv3 license with some      |
-- | permitted exceptions and without any warranty. For full license    |
-- | and copyright information, see https://civicrm.org/licensing       |
-- +--------------------------------------------------------------------+
--
-- Generated from {$smarty.template}
-- {$generated}
--
{$database.comments}

{include file="drop.tpl"}

-- /*******************************************************
-- *
-- * Create new tables
-- *
-- *******************************************************/

{foreach from=$tables item=table}
-- /*******************************************************
-- *
-- * {$table.name}
{if $table.comment}
-- *
-- * {$table.comment}
{/if}
-- *
-- *******************************************************/
CREATE TABLE `{$table.name}` (
{assign var='first' value=true}

{foreach from=$table.fields item=field}
{if ! $first},{/if}
{assign var='first' value=false}

     `{$field.name}` {$field.sqlType}{if $field.collate} COLLATE {$field.collate}{/if} {if $field.required}{if $field.required == "false"}NULL{else}NOT NULL{/if}{/if} {if $field.autoincrement}AUTO_INCREMENT{/if} {if $field.default|count_characters}DEFAULT {$field.default}{/if} {if $field.comment}COMMENT '{ts escape=sql}{$field.comment}{/ts}'{/if}
{/foreach} {* table.fields *}

{if $table.primaryKey}
{if ! $first},{/if}
{assign var='first' value=false}

    {assign var='firstIndexField' value=true}
    PRIMARY KEY ({foreach from=$table.primaryKey.field item=fieldName}{if $firstIndexField}{assign var='firstIndexField' value=false}{else},{/if}`{$fieldName}`{/foreach})
{/if} {* table.primaryKey *}

{if $table.index}
  {foreach from=$table.index item=index}
  {if ! $first},{/if}
  {assign var='first' value=false}
  {if $index.unique} UNIQUE{/if} INDEX `{$index.name}`(
  {assign var='firstIndexField' value=true}
  {foreach from=$index.field item=fieldName}
    {if $firstIndexField}{assign var='firstIndexField' value=false}{else}, {/if}{$fieldName}
  {/foreach}
)
{/foreach} {* table.index *}
{/if} {* table.index *}

{if $table.foreignKey}
{foreach from=$table.foreignKey item=foreign}
{if ! $first},{/if}
{assign var='first' value=false}
     {if $mysql eq 'simple'} INDEX FKEY_{$foreign.name} ( `{$foreign.name}` ) , {/if}
     CONSTRAINT {$foreign.uniqName} FOREIGN KEY (`{$foreign.name}`) REFERENCES `{$foreign.table}`(`{$foreign.key}`) {if $foreign.onDelete}ON DELETE {$foreign.onDelete}{/if}
{/foreach} {* table.foreignKey *}
{/if} {* table.foreignKey *}

{* ) {if $mysql eq 'modern' }{$table.attributes}{/if}; *}
) {if $mysql eq 'modern' } {$table.attributes_modern} {else} {$table.attributes_simple} {/if} ;

{/foreach} {* tables *}
