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
<div class="view-content">
{if $activeMembers}
<div id="memberships">
    <div class="form-item">
        {strip}
        <table>
        <tr class="columnheader">
            <th>{ts}Membership{/ts}</th>
            <th>{ts}Member Since{/ts}</th>
            <th>{ts}Start Date{/ts}</th>
            <th>{ts}End Date{/ts}</th>
            <th>{ts}Status{/ts}</th>
            <th></th>
        </tr>
        {foreach from=$activeMembers item=activeMember}
        <tr class="{cycle values="odd-row,even-row"} {$activeMember.class}">
          <td>{$activeMember.membership_type}</td>
    <td>{$activeMember.join_date|crmDate}</td>
          <td>{$activeMember.start_date|crmDate}</td>
          <td>{$activeMember.end_date|crmDate}</td>
          <td>{$activeMember.status}</td>
          <td>{if $activeMember.renewPageId}<a href="{crmURL p='civicrm/contribute/transact' q="id=`$activeMember.renewPageId`&mid=`$activeMember.id`&reset=1"}">[ {ts}Renew Now{/ts} ]</a>{/if}</td>
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
            <th>{ts}Start Date{/ts}</th>
            <th>{ts}End Date{/ts}</th>
            <th>{ts}Status{/ts}</th>
            <th></th>
        </tr>
        {foreach from=$inActiveMembers item=inActiveMember}
        <tr class="{cycle values="odd-row,even-row"} {$inActiveMember.class}">
          <td>{$inActiveMember.membership_type}</td>
          <td>{$inActiveMember.start_date|crmDate}</td>
          <td>{$inActiveMember.end_date|crmDate}</td>
          <td>{$inActiveMember.status}</td>
          <td>{if $inActiveMember.renewPageId}<a href="{crmURL p='civicrm/contribute/transact' q="id=`$inActiveMember.renewPageId`&mid=`$inActiveMember.id`&reset=1"}">[ {ts}Renew Now{/ts} ]</a>{/if}</td>

        </tr>
        {/foreach}
        </table>
        {/strip}

    </div>
</div>
{/if}

{if NOT ($activeMembers or $inActiveMembers)}
   <div class="messages status no-popup">
       <div class="icon inform-icon"></div></dt>
           {ts}There are no memberships on record for you.{/ts}
   </div>
{/if}
</div>
