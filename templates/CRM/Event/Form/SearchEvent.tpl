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
  <table class="form-layout">
    <tr class="crm-event-searchevent-form-block-title">
        <td>
          <label>{$form.title.label}</label>
          {$form.title.html|crmAddClass:twenty}
        </td>
        <td><label>{ts}Event Type{/ts}</label>
          {$form.event_type_id.html}
        </td>
    </tr>
    <tr>
    <td colspan="2"><div style="height: auto; vertical-align: bottom">{$form.eventsByDates.html}</div></td>
    </tr>
    <tr>
       <td colspan="2">
       <table class="form-layout-compressed" id="id_fromToDates">
        <tr class="">
          <td class="crm-event-searchevent-form-block-start_date">
            <label>{$form.start_date.label}</label>
            {$form.start_date.html}
          </td>
          <td class="crm-event-searchevent-form-block-end_date">
            <label>{$form.end_date.label}</label>
            {$form.end_date.html}
          </td>
        </tr>
      </table>
    </td></tr>

    {* campaign in event search *}
    {include file="CRM/Campaign/Form/addCampaignToSearch.tpl"
    campaignTrClass='crm-event-searchevent-form-block-campaign_id' campaignTdClass=''}
    <td class="right">{include file="CRM/common/formButtons.tpl" location=''}</td>
  </table>
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
