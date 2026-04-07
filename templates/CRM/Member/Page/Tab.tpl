{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="view-content">
{if $action eq 1 or $action eq 2 or $action eq 8} {* add, update or delete *}
    {include file="CRM/Member/Form/Membership.tpl"}
{elseif $action eq 4}
    {include file="CRM/Member/Form/MembershipView.tpl"}
{elseif $action eq 32768}  {* renew *}
    {include file="CRM/Member/Form/MembershipRenewal.tpl"}
{elseif $action eq 16} {* Browse memberships for a contact *}
    {if $action ne 1 and $action ne 2 and $permission EQ 'edit'}
        <div class="help">
            {if $linkButtons.add_membership}
              {ts}Click <em>Add Membership</em> to record a new membership.{/ts}
            {else}
              {ts 1=$displayName}Current and inactive memberships for %1 are listed below.{/ts}
            {/if}
            {if $linkButtons.creditcard_membership}
              {ts}Click <em>Submit Credit Card Membership</em> to process a Membership on behalf of the member using their credit card.{/ts}
            {/if}
        </div>

        <div class="action-link">
          {include file="CRM/common/formButtons.tpl" location="top" form=false}
        </div>
    {/if}
    {if NOT ($activeMembers or $inActiveMembers) and $action ne 2 and $action ne 1 and $action ne 8 and $action ne 4 and $action ne 32768}
         <div class="messages status no-popup">
          {icon icon="fa-info-circle"}{/icon}
              {ts}No memberships have been recorded for this contact.{/ts}
         </div>
    {/if}
    {include file="CRM/common/jsortable.tpl"}
    {if $activeMembers}
    <div id="memberships">
        <h3>{ts}Active Memberships{/ts}</h3>
        {strip}
        <table id="active_membership" class="display">
            <thead>
            <tr>
                <th class="crm-membership">{ts}Membership{/ts}</th>
                <th class="crm-membership-join_date">{ts}Member Since{/ts}</th>
                <th class="crm-membership-start_date">{ts}Membership Start Date{/ts}</th>
                <th class="crm-membership-end_date">{ts}Membership Expiration Date{/ts}</th>
                <th class="crm-membership-status">{ts}Status{/ts}</th>
                <th class="crm-membership-source">{ts}Membership Source{/ts}</th>
                <th class="crm-membership-auto_renew">{ts}Auto-renew{/ts}</th>
                <th class="crm-membership-related_count">{ts}Related{/ts}</th>
                <th></th>
            </tr>
            </thead>
            {foreach from=$activeMembers item=activeMember}
            <tr id="crm-membership_{$activeMember.id}" class="{cycle values="odd-row,even-row"} {$activeMember.class} crm-membership">
                <td class="crm-membership-membership_type">
                    {$activeMember.membership_type}{if $activeMember.is_test} ({ts}test{/ts}){/if}
                    {if $activeMember.owner_membership_id}<br />({ts}by relationship{/ts}){/if}
                </td>
                <td class="crm-membership-join_date" data-order="{$activeMember.join_date}">{$activeMember.join_date|crmDate}</td>
                <td class="crm-membership-start_date" data-order="{$activeMember.start_date}">{$activeMember.start_date|crmDate}</td>
                <td class="crm-membership-end_date" data-order="{$activeMember.end_date}">{$activeMember.end_date|crmDate}</td>
                <td class="crm-membership-status">{$activeMember.status}</td>
                <td class="crm-membership-source">{$activeMember.source}</td>
                <td class="crm-membership-auto_renew">
                  {if $activeMember.auto_renew eq 1}
                      {icon icon="fa-check"}{ts}Auto-renew active{/ts}{/icon}
                  {elseif $activeMember.auto_renew eq 2}
                      {icon icon="fa-exclamation-triangle"}{ts}Auto-renew error{/ts}{/icon}
                  {/if}
                </td>
                <td class="crm-membership-related_count">{$activeMember.related_count}</td>
    <td>
                    {$activeMember.action|replace:'xx':$activeMember.id}
                    {if $activeMember.owner_membership_id}
                      <a href="{crmURL p='civicrm/membership/view' q="reset=1&id=`$activeMember.owner_membership_id`&action=view&context=membership&selectedChild=member"}" title="{ts escape='htmlattribute'}View Primary member record{/ts}" class="crm-hover-button action-item">{ts}View Primary{/ts}</a>
                    {/if}
                </td>
            </tr>
            {/foreach}
        </table>
        {/strip}
    </div>
    {/if}

    {if $inActiveMembers}
        <div id="inactive-memberships">
        <p></p>
        <h3 class="font-red">{ts}Pending and Inactive Memberships{/ts}</h3>
        {strip}
        <table id="pending_membership" class="display">
            <thead>
            <tr>
                <th class="crm-membership">{ts}Membership{/ts}</th>
                <th class="crm-membership-join_date">{ts}Member Since{/ts}</th>
                <th class="crm-membership-start_date">{ts}Membership Start Date{/ts}</th>
                <th class="crm-membership-end_date">{ts}Membership Expiration Date{/ts}</th>
                <th class="crm-membership-status">{ts}Status{/ts}</th>
                <th class="crm-membership-source">{ts}Membership Source{/ts}</th>
                <th class="crm-membership-auto_renew">{ts}Auto-renew{/ts}</th>
    <th></th>
            </tr>
            </thead>
            {foreach from=$inActiveMembers item=inActiveMember}
            <tr id="crm-membership_{$inActiveMember.id}" class="{cycle values="odd-row,even-row"} {$inActiveMember.class} crm-membership">
                <td class="crm-membership-membership_type">
                    {$inActiveMember.membership_type}{if $inActiveMember.is_test} ({ts}test{/ts}){/if}
                    {if $inActiveMember.owner_membership_id}<br />({ts}by relationship{/ts}){/if}
                </td>
                <td class="crm-membership-join_date" data-order="{$inActiveMember.join_date}">{$inActiveMember.join_date|crmDate}</td>
                <td class="crm-membership-start_date" data-order="{$inActiveMember.start_date}">{$inActiveMember.start_date|crmDate}</td>
                <td class="crm-membership-end_date" data-order="{$inActiveMember.end_date}">{$inActiveMember.end_date|crmDate}</td>
                <td class="crm-membership-status">{$inActiveMember.status}</td>
                <td class="crm-membership-source">{$inActiveMember.source}</td>
                <td class="crm-membership-auto_renew">
                  {if $inActiveMember.auto_renew eq 1}
                    {icon icon="fa-check"}{ts}Auto-renew active{/ts}{/icon}
                  {elseif $inActiveMember.auto_renew eq 2}
                    {icon icon="fa-exclamation-triangle"}{ts}Auto-renew error{/ts}{/icon}
                  {/if}
                </td>
    <td>{$inActiveMember.action|replace:'xx':$inActiveMember.id}
    {if $inActiveMember.owner_membership_id}
      <a href="{crmURL p='civicrm/membership/view' q="reset=1&id=`$inActiveMember.owner_membership_id`&action=view&context=membership&selectedChild=member"}" title="{ts escape='htmlattribute'}View Primary member record{/ts}" class="crm-hover-button action-item">{ts}View Primary{/ts}
      </a>
    {/if}
    </td>
            </tr>
            {/foreach}
        </table>
        {/strip}
        </div>
    {/if}

    {if $membershipTypes}
    <div class="solid-border-bottom">&nbsp;</div>
    <div id="membership-types">
        <div><label>{ts}Membership Types{/ts}</label></div>
        <div class="help">
            {ts}The following Membership Types are associated with this organization. Click <strong>Members</strong> for a listing of all contacts who have memberships of that type. Click <strong>Edit</strong> to modify the settings for that type.{/ts}
        <div class="form-item">
            {strip}
            <table id="membership_type" class="display">
            <thead>
            <tr>
                <th>{ts}Name{/ts}</th>
                <th>{ts}Period{/ts}</th>
                <th>{ts}Fixed Start{/ts}</th>
                <th>{ts}Minimum Fee{/ts}</th>
                <th>{ts}Duration{/ts}</th>
                <th>{ts}Visibility{/ts}</th>
                <th></th>
            </tr>
            </thead>
            {foreach from=$membershipTypes item=membershipType}
            <tr class="{cycle values="odd-row,even-row"} {$membershipType.class} crm-membership">
                <td class="crm-membership-name">{$membershipType.name}</td>
                <td class="crm-membership-period_type">{$membershipType.period_type}</td>
                <td class="crm-membership-fixed_period_start_day">{$membershipType.fixed_period_start_day}</td>
                <td class="crm-membership-minimum_fee">{$membershipType.minimum_fee}</td>
                <td class="crm-membership-duration_unit">{$membershipType.duration_unit}</td>
                <td class="crm-membership-visibility">{$membershipType.visibility}</td>
                <td>{$membershipType.action|replace:xx:$membershipType.id}</td>
            </tr>
            {/foreach}
            </table>
            {/strip}

        </div>
    </div>
    {/if}
{/if} {* End of $action eq 16 - browse memberships. *}
</div>
