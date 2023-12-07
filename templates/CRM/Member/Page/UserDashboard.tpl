{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{crmRegion name="crm-member-userdashboard-pre"}
{/crmRegion}
<div class="view-content">
{if $activeMembers}
<div id="memberships">
    <div class="form-item">
        {strip}
        <table>
        <tr class="columnheader">
            <th>{ts}Membership{/ts}</th>
            <th>{ts}Member Since{/ts}</th>
            <th>{ts}Membership Start Date{/ts}</th>
            <th>{ts}Membership Expiration Date{/ts}</th>
            <th>{ts}Status{/ts}</th>
            <th></th>
        </tr>
        {foreach from=$activeMembers item=activeMember}
        <tr id="row_{$activeMember.id}" class="{cycle values="odd-row,even-row"}">
          <td class="crm-active-membership-membership_type">{$activeMember.membership_type}</td>
          <td class="crm-active-membership-join_date">{$activeMember.join_date|crmDate}</td>
          <td class="crm-active-membership-start_date">{$activeMember.start_date|crmDate}</td>
          <td class="crm-active-membership-end_date">{$activeMember.end_date|crmDate}</td>
          <td class="crm-active-membership-status">{$activeMember.status}</td>
          <td class="crm-active-membership-renew">{if $activeMember.renewPageId}<a href="{crmURL p='civicrm/contribute/transact' q="id=`$activeMember.renewPageId`&mid=`$activeMember.id`&reset=1"}">[ {ts}Renew Now{/ts} ]</a>{/if}</td>
        </tr>
        {/foreach}
        </table>
        {/strip}

    </div>
</div>
{/if}

{if $inActiveMembers}
<div id="ltype">
<p></p>
    <div class="label font-red">{ts}Expired / Inactive Memberships{/ts}</div>
    <div class="form-item">
        {strip}
        <table>
        <tr class="columnheader">
            <th>{ts}Membership{/ts}</th>
            <th>{ts}Membership Start Date{/ts}</th>
            <th>{ts}Membership Expiration Date{/ts}</th>
            <th>{ts}Status{/ts}</th>
            <th></th>
        </tr>
        {foreach from=$inActiveMembers item=inActiveMember}
        <tr id="row_{$inActiveMember.id}" class="{cycle values="odd-row,even-row"} {$inActiveMember.class}">
          <td class="crm-inactive-membership-membership_type">{$inActiveMember.membership_type}</td>
          <td class="crm-inactive-membership-start_date">{$inActiveMember.start_date|crmDate}</td>
          <td class="crm-inactive-membership-end_date">{$inActiveMember.end_date|crmDate}</td>
          <td class="crm-inactive-membership-status">{$inActiveMember.status}</td>
          <td class="crm-inactive-membership-renew">{if $inActiveMember.renewPageId}<a href="{crmURL p='civicrm/contribute/transact' q="id=`$inActiveMember.renewPageId`&mid=`$inActiveMember.id`&reset=1"}">[ {ts}Renew Now{/ts} ]</a>{/if}</td>
        </tr>
        {/foreach}
        </table>
        {/strip}

    </div>
</div>
{/if}

{if NOT ($activeMembers or $inActiveMembers)}
   <div class="messages status no-popup">
       {icon icon="fa-info-circle"}{/icon}</dt>
           {ts}There are no memberships on record for you.{/ts}
   </div>
{/if}
</div>
{crmRegion name="crm-member-userdashboard-post"}
{/crmRegion}
