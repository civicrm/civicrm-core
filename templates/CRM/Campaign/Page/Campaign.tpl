{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{* this template is used for displaying survey information *}
{if $campaigns}
  <div class="action-link">
      <a href="{$addCampaignUrl}" class="button"><span>&raquo; {ts}Add Campaign{/ts}</span></a>
  </div>
  {include file="CRM/common/enableDisableApi.tpl"}
  {include file="CRM/common/crmeditable.tpl"}
  <div id="campaignType">
    <table id="options" class="display">
      <thead>
        <tr>
          <th>{ts}Campaign Title{/ts}</th>
          <th>{ts}Description{/ts}</th>
          <th>{ts}Start Date{/ts}</th>
          <th>{ts}End Date{/ts}</th>
          <th>{ts}Campaign Type{/ts}</th>
          <th>{ts}Status{/ts}</th>
          <th>{ts}Active?{/ts}</th>
          <th id="nosort"></th>
  </tr>
      </thead>
      {foreach from=$campaigns item=campaign}
        <tr id="campaign-{$campaign.campaign_id}" class="crm-entity {if $campaign.is_active neq 1} disabled{/if}">
          <td class="crm-editable" data-field="title">{$campaign.title}</td>
          <td>{$campaign.description}</td>
          <td>{$campaign.start_date}</td>
          <td>{$campaign.end_date}</td>
          <td>{$campaign.campaign_type_id}</td>
          <td>{$campaign.status_id}</td>
          <td id="row_{$campaign.id}_status">{if $campaign.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{$campaign.action}</td>
  </tr>
      {/foreach}
    </table>
  </div>

{else}
    <div class="messages status no-popup">
        <div class="icon inform-icon"></div> &nbsp;
        {ts}No Campaigns found.{/ts}
    </div>
{/if}
<div class="action-link">
   <a href="{$addCampaignUrl}" class="button"><span>&raquo; {ts}Add Campaign{/ts}</span></a>
</div>
