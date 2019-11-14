{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{*this is included inside a table row*}
{assign var=relativeName   value=$fieldName|cat:"_relative"}
{assign var='from' value=$from|default:'_low'}
{assign var='to' value=$to|default:'_high'}

  {if !$hideRelativeLabel}
    {$form.$relativeName.label}<br />
  {/if}
  {$form.$relativeName.html}<br />
  <span class="crm-absolute-date-range">
    <span class="crm-absolute-date-from">
      {assign var=fromName value=$fieldName|cat:$from}
      {$form.$fromName.label}
      {$form.$fromName.html}
    </span>
    <span class="crm-absolute-date-to">
      {assign var=toName   value=$fieldName|cat:$to}
      {$form.$toName.label}
      {$form.$toName.html}
    </span>
  </span>
  {include file="CRM/Core/DatePickerRangejs.tpl" relativeName=$relativeName}

