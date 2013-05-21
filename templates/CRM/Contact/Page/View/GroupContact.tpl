{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
  {if $groupCount eq 0}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      &nbsp;{ts}This contact does not currently belong to any groups.{/ts}
    </div>
  {/if}

  {include file="CRM/common/jsortable.tpl"}

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
          <tr id="grp_{$row.id}" class="{cycle values="odd-row,even-row"}">
            <td class="bold">
              <a href="{crmURL p='civicrm/group/search' q="reset=1&force=1&context=smog&gid=`$row.group_id`"}">
                {$row.title}
              </a>
            </td>
            <td>{ts 1=$row.in_method}Added (by %1){/ts}</td>
            <td>{$row.in_date|crmDate}</td>
            <td>
              {if $permission EQ 'edit'}
              <a href="{crmURL p='civicrm/contact/view/group' q="gcid=`$row.id`&action=delete&st=o"}"
                onclick="return confirm('{ts 1=$displayName 2=$row.title}Are you sure you want to remove %1 from %2?{/ts}');"
                title="{ts}Remove contact from this group (status in this group will be changed to 'Removed').{/ts}">
                [ {ts}Remove{/ts} ]</a>
              {/if}
              {if $permission EQ 'edit'}
                <a href="{crmURL p='civicrm/contact/view/group' q="gcid=`$row.id`&action=delete&st=d"}"
                onclick="return confirm('{ts 1=$displayName 2=$row.title}Are you sure you want to delete %1 from %2?{/ts}');"
                title="{ts}Remove contact from this group AND delete their group status record.{/ts}">[ {ts}Delete{/ts}
                ]</a>
              {/if}
            </td>
          </tr>
        {/foreach}
      </table>
    {/strip}
  {/if}

  {if $contactSmartGroupSettings neq 3}
  <div class="accordion ui-accordion ui-widget ui-helper-reset">
    <div class="crm-accordion-wrapper crm-ajax-accordion crm-smartgroup-accordion {if $contactSmartGroupSettings eq 1}collapsed{/if}">
      <div class="crm-accordion-header" id="crm-contact_smartgroup" contact_id="{$contactId}">
        {ts}Smart Groups{/ts}
      </div>
      <!-- /.crm-accordion-header -->
      <div class="crm-accordion-body">
        <div class="crm-contact_smartgroup"></div>
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
          <tr class="{cycle values="odd-row,even-row"}">
            <td class="bold">
              <a href="{crmURL p='civicrm/group/search' q="reset=1&force=1&context=smog&gid=`$row.group_id`"}">
                {$row.title}
              </a>
            </td>
            <td>{ts 1=$row.pending_method}Pending (by %1){/ts}</td>
            <td>{$row.pending_date|crmDate}</td>
            <td>
              {if $permission EQ 'edit'}
              <a href="{crmURL p='civicrm/contact/view/group' q="gcid=`$row.id`&action=delete&st=o"}"
                onclick="return confirm('{ts 1=$displayName 2=$row.title}Are you sure you want to remove %1 from %2?{/ts}');"
                title="{ts}Remove contact from this group (status in this group will be changed to 'Removed').{/ts}">
                [ {ts}Remove{/ts} ]</a>
              {/if}
              {if $permission EQ 'edit'}
              <a href="{crmURL p='civicrm/contact/view/group' q="gcid=`$row.id`&action=delete&st=d"}"
                onclick="return confirm('{ts 1=$displayName 2=$row.title}Are you sure you want to delete %1 from %2?{/ts}');"
                title="{ts}Delete the group status record (this group will no longer be listed under Pending Groups).{/ts}">
                [ {ts}Delete{/ts} ]</a>
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
          <tr class="{cycle values="odd-row,even-row"}">
            <td class="bold">
              <a href="{crmURL p='civicrm/group/search' q="reset=1&force=1&context=smog&gid=`$row.group_id`"}">
                {$row.title}
              </a>
            </td>
            <td class="status-removed">{ts 1=$row.out_method}Removed (by %1){/ts}</td>
            <td>{$row.date_added|crmDate}</td>
            <td>{$row.out_date|crmDate}</td>
            <td>{if $permission EQ 'edit'}
              <a href="{crmURL p='civicrm/contact/view/group' q="gcid=`$row.id`&action=delete&st=i"}"
                onclick="return confirm('{ts 1=$displayName 2=$row.title}Are you sure you want to add %1 back into %2?{/ts}');">
                [ {ts}Rejoin Group{/ts} ]</a>{/if}
              {if $permission EQ 'edit'}
              <a href="{crmURL p='civicrm/contact/view/group' q="gcid=`$row.id`&action=delete&st=d"}"
                onclick="return confirm('{ts 1=$displayName 2=$row.title}Are you sure you want to delete %1 from %2?{/ts}');"
                title="{ts}Delete the group status record (this group will no longer be listed under Past Groups).{/ts}">
                [ {ts}Delete{/ts} ]</a>{/if}
            </td>
          </tr>
        {/foreach}
      </table>
    {/strip}
  {/if}
</div>


{literal}
<script type="text/javascript">
  // bind first click of accordion header to load crm-accordion-body with snippet
  // everything else taken care of by cj().crm-accordions()
  cj(function () {
    cj('.crm-ajax-accordion .crm-accordion-header').one('click', function () {
      loadPanes(cj(this));
    });
    cj('.crm-ajax-accordion:not(.collapsed) .crm-accordion-header').each(function (index) {
      loadPanes(cj(this));
    });
  });

  // load panes function calls for snippet based on id of crm-accordion-header
  function loadPanes(paneObj) {
    var id = paneObj.attr('id');
    var contactId = paneObj.attr('contact_id');

    var dataUrl = CRM.url('civicrm/contact/view/smartgroup', 'snippet=4&cid=' + contactId);

    if (!cj('div.' + id).html()) {
      var loading = '<img src="{/literal}{$config->resourceBase}i/loading.gif{literal}" alt="{/literal}{ts escape='js'}loading{/ts}{literal}" />&nbsp;{/literal}{ts escape='js'}Loading{/ts}{literal}...';
      cj('div.' + id).html(loading);
      cj.ajax({
        url: dataUrl,
        success: function (data) {
          cj('div.' + id).html(data);
        }
      });
    }
  }
</script>
{/literal}