{*
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
*}
{* this div is being used to apply special css *}
    {if !$section }
    <div class="crm-block crm-form-block crm-report-field-form-block">
    {include file="CRM/Report/Form/Fields.tpl"}
       </div>
    {/if}

<div class="crm-block crm-content-block crm-report-form-block">
{include file="CRM/Report/Form/Actions.tpl"}
{*Statistics at the Top of the page*}
    {if !$section }
        {include file="CRM/Report/Form/Statistics.tpl" top=true}
    {/if}

    {if $events}
        <div class="report-pager">
            {include file="CRM/common/pager.tpl" location="top"}
        </div>
        {foreach from=$events item=eventID}
                  <table class="report-layout">
                      {foreach from=$summary.$eventID item=values key=keys}
                          {if $keys == 'Title'}
                              <tr>
                                        <th>{$keys}</th>
                                        <th colspan="3">{$values}</th>
                                    </tr>
                                {else}
                                    <tr class="{cycle values="odd-row,even-row"} crm-report crm-report_event_summary" id="crm-report_{$eventID}_summary_{$keys}">
                                        <td class="report-contents crm-report_summary_title">{$keys}</td>
                                        <td class="report-contents crm-report_summary_details" colspan="3">{$values}</td>
                                    </tr>
                                {/if}
                            {/foreach}
                        </table>
                        {foreach from=$rows item=row key=keys}
                            {if $row.$eventID}
                            <table class="report-layout">
                          {if $row}
                              <tr>
                                  <th width="34%">{ts 1=$keys}%1 Breakdown{/ts}</th>
                                  <th class="reports-header-right">{ts}Total{/ts}</th>
                                        <th class="reports-header-right">{ts}% of Total{/ts}</th>
                                        <th class="reports-header-right">{ts}Revenue{/ts}</th>
                                    </tr>
                                    {foreach from=$row.$eventID item=row key=role}
                                        <tr class="{cycle values="odd-row,even-row"} crm-report crm-report_{$keys}_{$role}" id="crm-report_{$eventID}_{$keys}_{$role}">
                                            <td class="report-contents crm-report_{$keys}_breakdown" width="34%">{$role}</td>
                                            <td class="report-contents-right crm-report_{$keys}_total">{$row.total}</td>
                                            <td class="report-contents-right crm-report_{$keys}_percentage">{$row.round}</td>
                                            <td class="report-contents-right crm-report_{$keys}_revenue">{$row.amount}</td>
                                        </tr>
                                    {/foreach}
                                {/if}
                            </table>
                            {/if}
                        {/foreach}
        {/foreach}

    <div class="report-pager">
            {include file="CRM/common/pager.tpl"}
        </div>
        {if !$section }
            {*Statistics at the bottom of the page*}
            {include file="CRM/Report/Form/Statistics.tpl" bottom=true}
        {/if}
    {/if}
    {include file="CRM/Report/Form/ErrorMessage.tpl"}
</div>
