{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* View existing membership record. *}
<div class="crm-block crm-content-block crm-membership-view-form-block">
    <div class="crm-submit-buttons">
        {* Check permissions and make sure this is not an inherited membership (edit and delete not allowed for inherited memberships) *}
        {if ! ($owner_contact_id AND call_user_func(array('CRM_Core_Permission','check'), 'edit memberships'))
          && (call_user_func(array('CRM_Core_Permission', 'check'), "edit contributions of type $financialTypeId") || $noACL)}
      {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=update&context=$context"}
      {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
      {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=update&context=$context&key=$searchKey"}
      {/if}
            <a class="button" href="{crmURL p='civicrm/contact/view/membership' q=$urlParams}" accesskey="e" id="crm-membership-edit-button-top"><span><i class="crm-i fa-pencil" role="img" aria-hidden="true"></i> {ts}Edit{/ts}</span></a>
        {/if}
        {if ! ($owner_contact_id AND call_user_func(array('CRM_Core_Permission','check'), 'delete in CiviMember'))
          && (call_user_func(array('CRM_Core_Permission', 'check'), "delete contributions of type $financialTypeId") || $noACL)}
            {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=delete&context=$context"}
      {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
      {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=delete&context=$context&key=$searchKey"}
      {/if}
            <a class="button" href="{crmURL p='civicrm/contact/view/membership' q=$urlParams}" id="crm-membership-delete-button-top"><span><i class="crm-i fa-trash" role="img" aria-hidden="true"></i> {ts}Delete{/ts}</span></a>
        {/if}
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>

    {if $is_test}
    <div class="help">
      <strong>{ts}This is a TEST transaction{/ts}</strong>
    </div>
    {/if}
    <table class="crm-info-panel">
      <tr><td class="label">{ts}Member{/ts}</td><td class="bold"><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$contact_id&context=$context"}" title="{ts escape='htmlattribute'}View contact summary{/ts}">{$displayName}</td></tr>
        {if $owner_display_name}
            <tr><td class="label">{ts}By Relationship{/ts}</td><td>{$relationship}&nbsp;&nbsp;<a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$owner_contact_id&context=$context"}" title="{ts escape='htmlattribute'}View primary member contact summary{/ts}">{$owner_display_name}</a>&nbsp;</td></tr>
        {/if}
        <tr><td class="label">{ts}Membership Type{/ts}</td><td>{$membership_type}</td></tr>
        {if $has_related}
            <tr><td class="label">{ts}Max related{/ts}</td><td>{$max_related}</td></tr>
        {/if}
        <tr><td class="label">{ts}Status{/ts}</td><td>{$status} {if $is_override}({ts}Overridden{/ts}){/if}</td></tr>
        <tr><td class="label">{ts}Membership Source{/ts}</td><td>{$source}</td></tr>
  {if $campaign}<tr><td class="label">{ts}Campaign{/ts}</td><td>{$campaign}</td></tr>{/if}
        <tr><td class="label">{ts}Member Since{/ts}</td><td>{$join_date|crmDate}</td></tr>
        <tr><td class="label">{ts}Membership Start Date{/ts}</td><td>{$start_date|crmDate}</td></tr>
        <tr><td class="label">{ts}Membership Expiration Date{/ts}</td><td>{$end_date|crmDate}</td></tr>
        <tr><td class="label">{ts}Auto-renew{/ts}</td><td>{$auto_renew}</td></tr>
     {if $contribution_recur_id}
          <tr>
            <td class="label">{ts}Recurring Contribution{/ts}</td>
            <td>
              <a class="crm-hover-button action-item" href='{crmURL p="civicrm/contact/view/contributionrecur" q="reset=1&id=`$contribution_recur_id`&cid=`$contactId`&context=contribution"}'>{ts}View Recurring Contribution{/ts}</a>
            </td>
          </tr>
     {/if}
    </table>

    {include file="CRM/Custom/Page/CustomDataView.tpl"}

    {if $accessContribution}
      <details class="crm-accordion-bold" open>
        <summary>
          {ts}Related Contributions and Recurring Contributions{/ts}
        </summary>
        <div class="crm-accordion-body">
          {if $rows.0.contribution_id}
            {include file="CRM/Contribute/Form/Selector.tpl" context="Search"}
          {/if}
          <script type="text/javascript">
            var membershipID = {$id};
            var contactID = {$contactId};
            {literal}
            CRM.$(function($) {
              CRM.loadPage(
                CRM.url(
                  'civicrm/membership/recurring-contributions',
                  {
                    reset: 1,
                    membershipID: membershipID,
                    cid: contactID
                  },
                  'back'
                ),
                {
                  target : '#membership-recurring-contributions',
                  dialog : false
                }
              );
            });
            {/literal}
          </script>
          <div id="membership-recurring-contributions"></div>
        </div>
      </details>
    {/if}

    {if $softCredit}
        <details class="crm-accordion-bold" open>
            <summary>{ts}Related Soft Contributions{/ts}</summary>
            <div class="crm-accordion-body">{include file="CRM/Contribute/Page/ContributionSoft.tpl" context="membership"}</div>
        </details>
    {/if}

    {if $has_related}
        {include file="CRM/Member/Form/MembershipRelated.tpl" context="Search"}
    {/if}

    <div class="crm-submit-buttons">
        {* Check permissions and make sure this is not a related membership (edit and delete not allowed for related memberships) *}
        {if ! ($owner_contact_id AND call_user_func(array('CRM_Core_Permission','check'), 'edit memberships'))
          && (call_user_func(array('CRM_Core_Permission', 'check'), "edit contributions of type $financialTypeId") || $noACL)}
          {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=update&context=$context"}
          {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
            {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=update&context=$context&key=$searchKey"}
          {/if}
          <a class="button" href="{crmURL p='civicrm/contact/view/membership' q=$urlParams}" accesskey="e" id="crm-membership-edit-button-bottom"><span><i class="crm-i fa-pencil" role="img" aria-hidden="true"></i> {ts}Edit{/ts}</span></a>
        {/if}
        {if ! ($owner_contact_id AND call_user_func(array('CRM_Core_Permission','check'), 'delete in CiviMember'))
          && (call_user_func(array('CRM_Core_Permission', 'check'), "delete contributions of type $financialTypeId") || $noACL)}
          {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=delete&context=$context"}
          {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
            {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=delete&context=$context&key=$searchKey"}
          {/if}
          <a class="button" href="{crmURL p='civicrm/contact/view/membership' q=$urlParams}" id="crm-membership-delete-button-bottom"><span><i class="crm-i fa-trash" role="img" aria-hidden="true"></i> {ts}Delete{/ts}</span></a>
        {/if}
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
</div>
