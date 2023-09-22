{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Wrapper around DatePickerRange TPL file *}
<td {if !empty($colspan)} colspan="{$colspan}" {else} colspan="2" {/if} {if !empty($class)} class="{$class}" {/if}>
  {assign var='from' value=$from|default:'_low'}
  {assign var='to' value=$to|default:'_high'}
  {include file="CRM/Core/DatePickerRange.tpl" fieldName=$fieldName hideRelativeLabel=$hideRelativeLabel to=$to from=$from}
</td>
