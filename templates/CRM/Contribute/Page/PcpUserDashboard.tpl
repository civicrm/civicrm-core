{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{crmRegion name="crm-contribute-pcp-userdashboard-pre"}
{/crmRegion}
<div class="view-content">

{if $pcpInfo}
<div id="ltype">
{strip}

<table class="selector">
  <tr class="columnheader">
    <th>{ts}Your Page{/ts}</th>
    <th>{ts}In Support of{/ts}</th>
    <th>{ts}Campaign Ends{/ts}</th>
    <th>{ts}Status{/ts}</th>
    {if !$userChecksum} <th></th> {/if}
  </tr>

  {foreach from=$pcpInfo item=row}
  <tr class="{cycle values="odd-row,even-row"} {$row.class}">
        <td class="bold"><a href="{crmURL p='civicrm/pcp/info' q="reset=1&id=`$row.pcpId`" a=1}" title="{ts}Preview your Personal Campaign Page{/ts}">{$row.pcpTitle}</a></td>
        <td>{$row.pageTitle}</td>
        <td>{if $row.end_date}{$row.end_date|truncate:10:''|crmDate}{else}({ts}ongoing{/ts}){/if}</td>
        <td>{$row.pcpStatus}</td>
        {if !$userChecksum}
          <td>{$row.action|replace:'xx':$row.pcpId}</td>
        {/if}
  </tr>
  {/foreach}
</table>
{/strip}
</div>
{else}
<div class="messages status no-popup">
  <div class="icon inform-icon"></div>
  {ts}You do not have any active Personal Campaign pages.{/ts}
</div>
{/if}

{if $pcpBlock}
{strip}
{if $pcpInfo} {* Change layout and text if they already have a PCP. *}
    <br />
    <div class="float-right" style="width: 65%">
    <div>{ts}Create a Personal Campaign Page for another campaign:{/ts}</div>
{else}
    <div style="width: 65%">
    <div class="label">{ts}Become a supporter by creating a Personal Campaign Page:{/ts}</div>
{/if}
<table class="selector">
  <tr class="columnheader">
    <th>{ts}Campaign{/ts}</th>
    <th>{ts}Ends{/ts}</th>
    <th></th>
  </tr>

  {foreach from=$pcpBlock item=row}
  <tr class="{cycle values="odd-row,even-row"}">
    <td>{if $row.component eq 'contribute'}<a href="{crmURL p='civicrm/contribute/transact' q="id=`$row.pageId`&reset=1"}" title="{ts}View campaign page{/ts}">{else}<a href="{crmURL p='civicrm/event/register' q="id=`$row.pageId`&reset=1"}" title="{ts}View campaign page{/ts}">{/if}{$row.pageTitle}</a></td>
        <td>{if $row.end_date}{$row.end_date|truncate:10:''|crmDate}{else}({ts}ongoing{/ts}){/if}</td>
    <td>{$row.action|replace:'xx':$row.pageId}</td>
  </tr>
  {/foreach}
</table>
{/strip}
</div>
{/if}

</div>
{crmRegion name="crm-contribute-pcp-userdashboard-post"}
{/crmRegion}
