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
<td {if $colspan} colspan="{$colspan}" {else} colspan="2" {/if} {if $class} class="{$class}" {/if}>
  {assign var='hideRelativeLabel' value=$hideRelativeLabel|default:0}
  {include file="CRM/Core/DatePickerRange.tpl" fieldName=$fieldName hideRelativeLabel=$hideRelativeLabel}
</td>
