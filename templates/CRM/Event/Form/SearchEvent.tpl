{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<details class="crm-accordion-bold crm-block crm-form-block crm-event-searchevent-form-block" open>
  <summary>
    {ts}Find Events{/ts}
  </summary>
  <div class="crm-accordion-body">
    <div class="float-right">
      {include file="CRM/common/formButtons.tpl" location=''}
    </div>
    {* The 85% is to leave space for the Submit button (and not just in English) *}
    <div class="advanced-search-fields form-layout" style="max-width: 85%;">
      <div class="search-field crm-event-searchevent-form-block-title">
        {$form.title.label}<br>
        {$form.title.html|crmAddClass:twenty}
      </div>
      <div class="search-field">
        {$form.event_type_id.label}<br>
        {$form.event_type_id.html}
      </div>
      {* Show Campaign if CiviCampaign is enabled *}
      {if $campaignElementName}
        <div class="search-field crm-event-searchevent-form-block-campaign_id">
          {$form.$campaignElementName.label}<br>
          {$form.$campaignElementName.html}
        </div>
      {/if}
    </div>
    <div class="advanced-search-fields form-layout" style="max-width: 85%;">
      <div class="search-field">
        {$form.eventsByDates.label}
        {$form.eventsByDates.html}
      </div>
      <div class="search-field">
        <div id="id_fromToDates" class="advanced-search-fields form-layout">
          <div crm-event-searchevent-form-block-start_date">
            {$form.start_date.label}<br>
            {$form.start_date.html}
          </div>
          <div crm-event-searchevent-form-block-end_date">
            {$form.end_date.label}<br>
            {$form.end_date.html}
          </div>
        </div>
      </div>
  </div>
</details>

{include file="CRM/common/showHide.tpl"}

{literal}
<script type="text/javascript">
if ( document.getElementsByName('eventsByDates')[1].checked ) {
  CRM.$('#id_fromToDates').show();
}
</script>
{/literal}
