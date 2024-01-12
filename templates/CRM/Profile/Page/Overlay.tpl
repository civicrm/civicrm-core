{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $overlayProfile}
<table class="crm-table-group-summary">
  <tr>
    <td colspan="2">{$displayName}</td>
  </tr>
  <tr>
    <td>
      {assign var="count" value=0}
      {assign var="totalRows" value=$row|@count}
      <div class="crm-summary-col-0">
    {foreach from=$profileFields item=field key=rowName}
        {if $count gt $totalRows/2}
      </div>
    </td>
    <td>
      <div class="crm-summary-col-1">
        {assign var="count" value=1}
        {/if}
      <div class="crm-section {$rowName}-section">
        <div class="label">
            {$field.label}
        </div>
        <div class="content">
          {$field.value}
        </div>
        <div class="clear"></div>
      </div>
      {assign var="count" value=$count+1}
    {/foreach}
      </div>
    </td>
  </tr>
</table>
{* fields array is not empty *}
{/if}
