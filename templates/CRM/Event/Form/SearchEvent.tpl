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
  <div class="crm-accordion-wrapper crm-block crm-form-block crm-event-searchevent-form-block collapsed">
    <div class="crm-accordion-header">
      {ts}Find Events{/ts}
    </div>
    <div class="crm-accordion-body">
  <table class="form-layout">
    <tr class="crm-event-searchevent-form-block-title">
        <td>
          <label>{$form.title.label}</label>
          {$form.title.html|crmAddClass:twenty}
          <div class="description font-italic">
                 {ts}Complete OR partial Event name.{/ts}
          </div>
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
    {include file="CRM/Campaign/Form/addCampaignToComponent.tpl" campaignContext="componentSearch"
    campaignTrClass='crm-event-searchevent-form-block-campaign_id' campaignTdClass=''}
    <td class="right">{include file="CRM/common/formButtons.tpl"}</td>
  </table>
    </div>
  </div>

{include file="CRM/common/showHide.tpl"}

{literal}
<script type="text/javascript">
if ( document.getElementsByName('eventsByDates')[1].checked ) {
  CRM.$('#id_fromToDates').show();
}
</script>
{/literal}
