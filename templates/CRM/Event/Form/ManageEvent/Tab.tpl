{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
                        <li><a class="crm-event-test" href="{crmURL p='civicrm/event/register' q="reset=1&action=preview&id=`$id`" fe='true'}">{ts}Online Registration (Test-drive){/ts}</a></li>
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
          {help id="id-configure-events" isTemplate=$isTemplate participantListingID=$participantListingID isOnlineRegistration=$isOnlineRegistration eventId=$id}
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

});
</script>
{/literal}
{crmRegion name="event-manageevent-confirmrepeatmode"}
{include file="CRM/Event/Form/ManageEvent/ConfirmRepeatMode.tpl" entityID=$id entityTable="civicrm_event"}
{/crmRegion}
