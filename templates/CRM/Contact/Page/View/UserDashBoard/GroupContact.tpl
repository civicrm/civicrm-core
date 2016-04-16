{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
<div id="groupContact">
    <div class="view-content">
        {if $groupCount eq 0 }
            <div class="messages status no-popup">
                    <div class="icon inform-icon"></div>
                    {ts}You are not currently subscribed to any Groups.{/ts}
            </div>
        {/if}

        {if $groupIn }
            <div class="form-item">
                <div>
                    {strip}
                    <table class="selector">
                        <tr class="columnheader">
                            <th>{ts}Group{/ts}</th>
                            <th>{ts}Status{/ts}</th>
                            <th>{ts}Date Added{/ts}</th>
                            {if $edit}
                              <th></th>
                            {/if}
                        </tr>
                        {foreach from=$groupIn item=row}
                            <tr class="{cycle values="odd-row,even-row"}">
                                <td><strong>{$row.title}</strong></td>
                                <td>{ts 1=$row.in_method}Added (by %1){/ts}</td>
                                <td>{$row.in_date|crmDate}</td>
                                {if $edit}
                                    <td><a href="{crmURL p='civicrm/user/group' q="gcid=`$row.id`&action=delete&st=o"}" onclick ="return confirm('{ts 1=$row.title}Are you sure you want to unsubscribe from %1?{/ts}');">[ {ts}Unsubscribe{/ts} ]</a></td>
                                {/if}
                            </tr>
                        {/foreach}
                    </table>
                    {/strip}
                </div>
            </div>
        {/if}

        {if $edit}
            {* Include 'Join a Group' form *}
            {include file="CRM/Contact/Form/GroupContact.tpl"}
        {/if}

        {if $groupPending }
            <div class="form-item">
                <div class="label status-pending">{ts}Pending Subscriptions{/ts}</div>
                <div class="description">{ts}Your subscription to these group(s) is pending confirmation.{/ts}</div>
                <div>
                    {strip}
                    <table class="selector">
                        <tr class="columnheader">
                            <th>{ts}Group{/ts}</th>
                            <th>{ts}Status{/ts}</th>
                            <th>{ts}Date Pending{/ts}</th>
                            {if $edit}
                                <th></th>
                            {/if}
                        </tr>
                        {foreach from=$groupPending item=row}
                            <tr class="{cycle values="odd-row,even-row"}">
                                <td><strong>{$row.title}</strong></td>
                                <td>{ts 1=$row.pending_method}Pending (by %1){/ts}</td>
                                <td>{$row.pending_date|crmDate}</td>
                                {if $edit}
                                    <td><a href="{crmURL p='civicrm/user/group' q="gcid=`$row.id`&action=delete&st=i"}" onclick ="return confirm('{ts 1=$row.title}Are you sure you want to confirm subscription for %1?{/ts}');">[ {ts}Confirm{/ts} ]</a></td>
                                {/if}
                            </tr>
                        {/foreach}
                    </table>
                    {/strip}
                </div>
            </div>
        {/if}

        {if $groupOut }
            <div class="form-item">
                <div class="label status-removed">{ts}Unsubscribed Groups{/ts}</div>
                <div class="description">{ts}You are no longer subscribed to these group(s). Click Rejoin Group if you want to re-subscribe.{/ts}</div>
                <div>
                    {strip}
                    <table class="selector">
                        <tr class="columnheader">
                            <th>{ts}Group{/ts}</th>
                            <th>{ts}Status{/ts}</th>
                            <th>{ts}Date Added{/ts}</th>
                            <th>{ts}Date Removed{/ts}</th>
                            {if $edit}
                                <th></th>
                            {/if}
                        </tr>
                        {foreach from=$groupOut item=row}
                            <tr class="{cycle values="odd-row,even-row"}">
                                <td><strong>{$row.title}</strong></td>
                                <td class="status-removed">{ts 1=$row.out_method}Removed (by %1){/ts}</td>
                                <td>{$row.date_added|crmDate}</td>
                                <td>{$row.out_date|crmDate}</td>
                                {if $edit}
                                    <td><a href="{crmURL p='civicrm/user/group' q="gcid=`$row.id`&action=delete&st=i"}" onclick ="return confirm('{ts 1=$row.title}Are you sure you want to add back into %1?{/ts}');">[ {ts}Rejoin Group{/ts} ]</a></td>
                                {/if}
                            </tr>
                        {/foreach}
                    </table>
                    {/strip}
                </div>
            </div>
        {/if}
    </div>
</div>
