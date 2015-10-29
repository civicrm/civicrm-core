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
    {capture assign=newPageURL}{crmURL p='civicrm/admin/contribute/add' q='action=add&reset=1'}{/capture}
    <div class="help">
       {ts}CiviContribute allows you to create and maintain any number of Online Contribution Pages. You can create different pages for different programs or campaigns - and customize text, amounts, types of information collected from contributors, etc.{/ts} {help id="id-intro"}
    </div>

    {include file="CRM/Contribute/Form/SearchContribution.tpl"}
    {if NOT ($action eq 1 or $action eq 2) }
      <table class="form-layout-compressed">
      <tr>
      <td><a href="{$newPageURL}" class="button"><span><i class="crm-i fa-plus-circle"></i> {ts}Add Contribution Page{/ts}</span></a></td>
            <td style="vertical-align: top"><a class="button" href="{crmURL p="civicrm/admin/pcp" q="reset=1"}"><span>{ts}Manage Personal Campaign Pages{/ts}</span></a> {help id="id-pcp-intro" file="CRM/PCP/Page/PCP.hlp"}</td>
      </tr>
      </table>
    {/if}

    {if $rows}
      <div id="configure_contribution_page">
             {strip}

       {include file="CRM/common/pager.tpl" location="top"}
             {include file="CRM/common/pagerAToZ.tpl"}
             {* handle enable/disable actions *}
             {include file="CRM/common/enableDisableApi.tpl"}
       {include file="CRM/common/jsortable.tpl"}
             <table id="options" class="display">
               <thead>
               <tr>
                 <th>{ts}Title{/ts}</th>
               <th>{ts}ID{/ts}</th>
               <th>{ts}Enabled?{/ts}</th>
             {if call_user_func(array('CRM_Campaign_BAO_Campaign','isCampaignEnable'))}
             <th>{ts}Campaign{/ts}</th>
            {/if}
            <th></th>
               </tr>
               </thead>
               {foreach from=$rows item=row}
                 <tr id="contribution_page-{$row.id}" class="crm-entity {if NOT $row.is_active} disabled{/if}">
                     <td><strong>{$row.title}</strong></td>
                     <td>{$row.id}</td>
                     <td id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          {if call_user_func(array('CRM_Campaign_BAO_Campaign','isCampaignEnable'))}
          <td>{$row.campaign}</td>
          {/if}
          <td class="crm-contribution-page-actions right nowrap">

       {if $row.configureActionLinks}
         <div class="crm-contribution-page-configure-actions">
                  {$row.configureActionLinks|replace:'xx':$row.id}
         </div>
             {/if}

            {if $row.contributionLinks}
        <div class="crm-contribution-online-contribution-actions">
                  {$row.contributionLinks|replace:'xx':$row.id}
        </div>
        {/if}

        {if $row.onlineContributionLinks}
        <div class="crm-contribution-search-contribution-actions">
                  {$row.onlineContributionLinks|replace:'xx':$row.id}
        </div>
        {/if}

        <div class="crm-contribution-page-more">
                    {$row.action|replace:'xx':$row.id}
            </div>

      </td>

         </tr>
         {/foreach}
      </table>

        {/strip}
      </div>
    {else}
  {if $isSearch eq 1}
      <div class="status messages">
                <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
                {capture assign=browseURL}{crmURL p='civicrm/contribute/manage' q="reset=1"}{/capture}
                    {ts}No available Contribution Pages match your search criteria. Suggestions:{/ts}
                    <div class="spacer"></div>
                    <ul>
                    <li>{ts}Check your spelling.{/ts}</li>
                    <li>{ts}Try a different spelling or use fewer letters.{/ts}</li>
                    <li>{ts}Make sure you have enough privileges in the access control system.{/ts}</li>
                    </ul>
                    {ts 1=$browseURL}Or you can <a href='%1'>browse all available Contribution Pages</a>.{/ts}
      </div>
      {else}
      <div class="messages status no-popup">
             <div class="icon inform-icon"></div> &nbsp;
             {ts 1=$newPageURL}No contribution pages have been created yet. Click <a accesskey="N" href='%1'>here</a> to create a new contribution page.{/ts}
      </div>
        {/if}
    {/if}
