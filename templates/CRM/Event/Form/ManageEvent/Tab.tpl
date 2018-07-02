{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{* Don't show action buttons for event templates *}
{if ! $isTemplate}
    <div class="crm-actions-ribbon crm-event-manage-tab-actions-ribbon">
      <ul id="actions">
      <li><div id="crm-event-links-wrapper">
            {crmButton id="crm-event-links-link" href="#" icon="bars"}{ts}Event Links{/ts}{/crmButton}
            <div class="ac_results" id="crm-event-links-list">
                 <div class="crm-event-links-list-inner">
                   <ul>
                    <li><a class="crm-event-participant" href="{crmURL p='civicrm/participant/add' q="reset=1&action=add&context=standalone&eid=`$id`"}">{ts}Register Participant{/ts}</a></li>
                       <li><a class="crm-event-info" href="{crmURL p='civicrm/event/info' q="reset=1&id=`$id`" fe='true'}" target="_blank">{ts}Event Info{/ts}</a></li>
                    {if $isOnlineRegistration}
                        <li><a class="crm-event-test" href="{crmURL p='civicrm/event/register' q="reset=1&action=preview&id=`$id`"}">{ts}Online Registration (Test-drive){/ts}</a></li>
                               <li><a class="crm-event-live" href="{crmURL p='civicrm/event/register' q="reset=1&id=`$id`" fe='true'}" target="_blank">{ts}Online Registration (Live){/ts}</a></li>
                    {/if}
                </ul>
                 </div>
            </div>
        </div></li>

      <li><div id="crm-participant-wrapper">
            {crmButton id="crm-participant-link" href="#" icon="bars"}{ts}Find Participants{/ts}{/crmButton}
            <div class="ac_results" id="crm-participant-list">
                 <div class="crm-participant-list-inner">
                   <ul>
              {if $findParticipants.statusCounted}
                <li><a class="crm-participant-counted" href="{crmURL p='civicrm/event/search' q="reset=1&force=1&event=`$id`&status=true"}">{$findParticipants.statusCounted|replace:'/':', '}</a></li>
              {/if}
                    {if $findParticipants.statusNotCounted}
                <li><a class="crm-participant-not-counted" href="{crmURL p='civicrm/event/search' q="reset=1&force=1&event=`$id`&status=false"}">{$findParticipants.statusNotCounted|replace:'/':', '}</a>
            </li>
              {/if}
                    {if $participantListingURL}
                <li><a class="crm-participant-listing" href="{$participantListingURL}">{ts}Public Participant Listing{/ts}</a></li>
              {/if}
                </ul>
                 </div>
            </div>
        </div></li>

      <li><div>
          {help id="id-configure-events" isTemplate=$isTemplate participantListingURL=$participantListingURL isOnlineRegistration=$isOnlineRegistration eventId=$id}
      </div></li>
      </ul>
      <div class="clear"></div>
    </div>
{/if}

{include file="CRM/common/TabHeader.tpl"}

{literal}
<script>
CRM.$(function($) {
  $('body').click(function() {
    $('#crm-event-links-list, #crm-participant-list').hide();
  });

  $('#crm-event-links-link').click(function(event) {
    $('#crm-event-links-list').toggle();
    $('#crm-participant-list').hide();
    event.stopPropagation();
    return false;
  });

  $('#crm-participant-link').click(function(event) {
    $('#crm-participant-list').toggle();
    $('#crm-event-links-list').hide();
    event.stopPropagation();
    return false;
  });

  // Update title dynamically
  $('h1').each(function() {
    var title = {/literal}{$title|json_encode}{literal};
    $(this).html($(this).html().replace(title, '<span id="crm-event-name-page-title">' + title + '</span>'));
  });
  $('#crm-main-content-wrapper').on('keyup change', 'input#title', function() {
    $('#crm-event-name-page-title').text($(this).val());
  });

});
</script>
{/literal}
{include file="CRM/Event/Form/ManageEvent/ConfirmRepeatMode.tpl" entityID=$id entityTable="civicrm_event"}
