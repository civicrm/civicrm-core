{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
<div class="view-content view-contact-groups">
  {if $groupCount eq 0}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      &nbsp;{ts}This contact does not currently belong to any groups.{/ts}
    </div>
  {else}
    {include file="CRM/common/jsortable.tpl"}
  {/if}

  {* Include 'add to new group' form if session has edit contact permissions *}
  {if $permission EQ 'edit'}
    {include file="CRM/Contact/Form/GroupContact.tpl"}
  {/if}

  {if $groupIn }
    <div class="ht-one"></div>
    <h3>{ts}Regular Groups{/ts}</h3>
    <div class="description">{ts 1=$displayName}%1 has joined or been added to these group(s).{/ts}</div>
    {strip}
      <table id="current_group" class="display">
        <thead>
        <tr>
          <th>{ts}Group{/ts}</th>
          <th>{ts}Status{/ts}</th>
          <th>{ts}Date Added{/ts}</th>
          <th></th>
        </tr>
        </thead>
        {foreach from=$groupIn item=row}
          <tr id="group_contact-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"}">
            <td class="bold">
              <a href="{crmURL p='civicrm/group/search' q="reset=1&force=1&context=smog&gid=`$row.group_id`"}">
                {$row.title}
              </a>
            </td>
            <td>{ts 1=$row.in_method}Added (by %1){/ts}</td>
            <td>{$row.in_date|crmDate}</td>
            <td>
              {if $permission EQ 'edit'}
                <a class="action-item crm-hover-button" href="#Removed" title="{ts 1=$displayName 2=$row.title}Remove %1 from %2? (status in this group will be changed to 'Removed').{/ts}">
                  {ts}Remove{/ts}</a>
                <a class="action-item crm-hover-button" href="#Deleted" title="{ts 1=$displayName 2=$row.title}Delete %1 from %2? (remove contact AND delete their record of having been in this group).{/ts}">
                  {ts}Delete{/ts}</a>
              {/if}
            </td>
          </tr>
        {/foreach}
      </table>
    {/strip}
  {/if}

  {if $contactSmartGroupSettings neq 3}
    <div class="spacer" style="height: 1.5em;"></div>
    <div class="accordion ui-accordion ui-widget ui-helper-reset">
      <div class="crm-accordion-wrapper crm-ajax-accordion crm-smartgroup-accordion {if $contactSmartGroupSettings eq 1}collapsed{/if}">
        <div class="crm-accordion-header" id="crm-contact_smartgroup" contact_id="{$contactId}">
          {ts}Smart Groups{/ts}
        </div>
        <!-- /.crm-accordion-header -->
        <div class="crm-accordion-body">
          <div class="crm-contact_smartgroup" style="min-height: 3em;"></div>
        </div>
        <!-- /.crm-accordion-body -->
      </div>
      <!-- /.crm-accordion-wrapper -->
    </div>
  {/if}

  {if $groupPending}
    <div class="ht-one"></div>
    <h3 class="status-pending">{ts}Pending{/ts}</h3>
    <div class="description">{ts}Joining these group(s) is pending confirmation by this contact.{/ts}</div>
    {strip}
      <table id="pending_group" class="display">
        <thead>
        <tr>
          <th>{ts}Group{/ts}</th>
          <th>{ts}Status{/ts}</th>
          <th>{ts}Date Pending{/ts}</th>
          <th></th>
        </tr>
        </thead>
        {foreach from=$groupPending item=row}
          <tr id="group_contact-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"}">
            <td class="bold">
              <a href="{crmURL p='civicrm/group/search' q="reset=1&force=1&context=smog&gid=`$row.group_id`"}">
                {$row.title}
              </a>
            </td>
            <td>{ts 1=$row.pending_method}Pending (by %1){/ts}</td>
            <td>{$row.pending_date|crmDate}</td>
            <td>
              {if $permission EQ 'edit'}
                <a class="action-item crm-hover-button" href="#Removed" title="{ts 1=$displayName 2=$row.title}Remove %1 from %2? (status in this group will be changed to 'Removed').{/ts}">
                  {ts}Remove{/ts}</a>
                <a class="action-item crm-hover-button" href="#Deleted" title="{ts 1=$displayName 2=$row.title}Delete %1 from %2? (this group will no longer be listed under Pending Groups){/ts}">
                  {ts}Delete{/ts}</a>
              {/if}
            </td>
          </tr>
        {/foreach}
      </table>
    {/strip}
  {/if}

  {if $groupOut}
    <div class="ht-one"></div>
    <h3 class="status-removed">{ts}Past Groups{/ts}</h3>
    <div class="description">{ts 1=$displayName}%1 is no longer part of these group(s).{/ts}</div>
    {strip}
      <table id="past_group" class="display">
        <thead>
        <tr>
          <th>{ts}Group{/ts}</th>
          <th>{ts}Status{/ts}</th>
          <th>{ts}Date Added{/ts}</th>
          <th>{ts}Date Removed{/ts}</th>
          <th></th>
        </tr>
        </thead>
        {foreach from=$groupOut item=row}
          <tr id="group_contact-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"}">
            <td class="bold">
              <a href="{crmURL p='civicrm/group/search' q="reset=1&force=1&context=smog&gid=`$row.group_id`"}">
                {$row.title}
              </a>
            </td>
            <td class="status-removed">{ts 1=$row.out_method}Removed (by %1){/ts}</td>
            <td data-order="{$row.date_added}">{$row.date_added|crmDate}</td>
            <td data-order="{$row.out_date}">{$row.out_date|crmDate}</td>
            <td>{if $permission EQ 'edit'}
                <a class="action-item crm-hover-button" href="#Added" title="{ts 1=$displayName 2=$row.title}Add %1 back into %2?{/ts}">
                  {ts}Rejoin Group{/ts}</a>
              <a class="action-item crm-hover-button" href="#Deleted" title="{ts 1=$displayName 2=$row.title}Delete %1 from %2? (this group will no longer be listed under Past Groups).{/ts}">
                {ts}Delete{/ts}</a>{/if}
            </td>
          </tr>
        {/foreach}
      </table>
    {/strip}
  {/if}
</div>


{literal}
<script type="text/javascript">
  CRM.$(function($) {
    // load panes function calls for snippet based on id of crm-accordion-header
    function loadPanes() {
      var $el = $(this).parent().find('div.crm-contact_smartgroup');
      var contactId = $(this).attr('contact_id');
      if (!$el.html()) {
        CRM.loadPage(CRM.url('civicrm/contact/view/smartgroup', {cid: contactId}), {target: $el});
      }
    }
    // bind first click of accordion header to load crm-accordion-body with snippet
    $('.view-contact-groups .crm-ajax-accordion.collapsed .crm-accordion-header').one('click', loadPanes);
    $('.view-contact-groups .crm-ajax-accordion:not(.collapsed) .crm-accordion-header').each(loadPanes);
    // Handle enable/delete links
    var that;
    function refresh() {
      CRM.refreshParent(that);
    }
    function enableDisableGroup() {
      var params = {
        id: $(that).closest('.crm-entity').attr('id').split('-')[1],
        method: 'Admin'
      };
      var status = that.href.split('#')[1];
      if (status === 'Deleted') {
        params.skip_undelete = true;
      } else {
        params.status = status;
      }
      // This api is weird - 'delete' actually works for updating as well as deleting
      // Normally you wouldn't put a variable within ts() but this works due to smarty hack below
      CRM.api3('group_contact', 'delete', params, {success: ts(status)}).done(refresh);
    }
    $('.view-contact-groups a.action-item').click(function() {
      that = this;
      CRM.confirm(enableDisableGroup, {message: this.title});
      return false;
    });
  });
  {/literal}
  // Hack to ensure status msg is properly translated
  CRM.strings.Added = "{ts escape='js'}Added{/ts}";
  CRM.strings.Removed = "{ts escape='js'}Removed{/ts}";
  CRM.strings.Deleted = "{ts escape='js'}Deleted{/ts}";
</script>
