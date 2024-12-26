{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
    {capture assign=newPageURL}{crmURL p='civicrm/admin/contribute/add' q='action=add&reset=1'}{/capture}
    <div class="help">
       {ts}CiviContribute allows you to create and maintain any number of Online Contribution Pages. You can create different pages for different programs or campaigns, and you can customize text, amounts, types of information collected from contributors, etc.{/ts} {help id="id-intro"}
    </div>

    {include file="CRM/Contribute/Form/SearchContribution.tpl"}
    {if NOT ($action eq 1 or $action eq 2)}
      <table class="form-layout-compressed">
      <tr>
      <td><a href="{$newPageURL}" class="button"><span><i class="crm-i fa-plus-circle" aria-hidden="true"></i> {ts}Add Contribution Page{/ts}</span></a></td>
            <td style="vertical-align: top"><a class="button" href="{crmURL p="civicrm/admin/pcp" q="reset=1"}"><span>{ts}Manage Personal Campaign Pages{/ts}</span></a> {help id="id-pcp-intro" file="CRM/PCP/Page/PCP.hlp"}</td>
      </tr>
      </table>
    {/if}

    {if $rows}
      <div id="configure_contribution_page">
             {strip}

       {include file="CRM/common/pager.tpl" location="top"}
             {* handle enable/disable actions *}
             {include file="CRM/common/enableDisableApi.tpl"}
       {include file="CRM/common/jsortable.tpl"}
             <table id="options" class="display">
               <thead>
               <tr>
                 <th>{ts}Title{/ts}</th>
               <th>{ts}ID{/ts}</th>
               <th>{ts}Enabled?{/ts}</th>
             {if call_user_func(array('CRM_Campaign_BAO_Campaign','isComponentEnabled'))}
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
          {if call_user_func(array('CRM_Campaign_BAO_Campaign','isComponentEnabled'))}
          <td>{$row.campaign}</td>
          {/if}
          <td class="crm-contribution-page-actions right nowrap">

       {if $row.configureActionLinks}
         <div class="crm-contribution-page-configure-actions">
           {$row.configureActionLinks|smarty:nodefaults|replace:'xx':$row.id}
         </div>
       {/if}

        {if $row.contributionLinks}
          <div class="crm-contribution-online-contribution-actions">
            {$row.contributionLinks|smarty:nodefaults|replace:'xx':$row.id}
          </div>
        {/if}

        {if $row.onlineContributionLinks}
          <div class="crm-contribution-search-contribution-actions">
            {$row.onlineContributionLinks|smarty:nodefaults|replace:'xx':$row.id}
          </div>
        {/if}

        <div class="crm-contribution-page-more">
          {$row.action|smarty:nodefaults|replace:'xx':$row.id}
        </div>

      </td>

         </tr>
         {/foreach}
      </table>
        {include file="CRM/common/pagerAToZ.tpl"}
        {/strip}
        {include file="CRM/common/pager.tpl" location="bottom"}
      </div>
    {else}
  {if $isSearch eq 1}
      <div class="status messages">
                <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
                {capture assign=browseURL}{crmURL p='civicrm/admin/contribute/manage' q="reset=1"}{/capture}
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
             {icon icon="fa-info-circle"}{/icon}
             {ts 1=$newPageURL}No contribution pages have been created yet. Click <a accesskey="N" href='%1'>here</a> to create a new contribution page.{/ts}
      </div>
        {/if}
    {/if}
