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
  {if !isset($hideRelativeLabel) || $hideRelativeLabel === ''}{assign var='hideRelativeLabel' value=0}{else}{assign var='hideRelativeLabel' value=$hideRelativeLabel}{/if}
  {if !isset($from) || $from === ''}{assign var='from' value='_low'}{else}{assign var='from' value=$from}{/if}
  {if !isset($to) || $to === ''}{assign var='to' value='_high'}{else}{assign var='to' value=$to}{/if}
  {include file="CRM/Core/DatePickerRange.tpl" fieldName=$fieldName hideRelativeLabel=$hideRelativeLabel to=$to from=$from}
</td>
