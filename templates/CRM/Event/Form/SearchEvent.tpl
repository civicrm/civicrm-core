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
<div class="crm-block crm-form-block crm-event-searchevent-form-block">
 <h3>{ts}Find Events{/ts}</h3>
  <table class="form-layout">
    <tr class="crm-event-searchevent-form-block-title">
        <td>{$form.title.html|crmAddClass:twenty}
             <div class="description font-italic">
                    {ts}Complete OR partial Event name.{/ts}
             </div>
             <div style="height: auto; vertical-align: bottom">{$form.eventsByDates.html}</div>
        </td>
        <td rowspan="2"><label>{ts}Event Type{/ts}</label>
            <div class="listing-box">
                {foreach from=$form.event_type_id item="event_val"}
                <div class="{cycle values="odd-row,even-row"}">
                    {$event_val.html}
                </div>
                {/foreach}
            </div>
        </td>
        <td class="right" rowspan="2">&nbsp;{include file="CRM/common/formButtons.tpl"}</td>  
    </tr>
  
    <tr>
       <td colspan="2">
       <table class="form-layout-compressed" id="id_fromToDates">
        <tr class="crm-event-searchevent-form-block-start_date">
            <td class="label">{$form.start_date.label}</td>
            <td>{include file="CRM/common/jcalendar.tpl" elementName=start_date}</td>
        </tr>
        <tr class="crm-event-searchevent-form-block-end_date" >
            <td class="label">{$form.end_date.label}</td>
            <td>{include file="CRM/common/jcalendar.tpl" elementName=end_date}</td>             
        </tr>
      </table> 
    </td></tr>  

    {* campaign in event search *}
    {include file="CRM/Campaign/Form/addCampaignToComponent.tpl" campaignContext="componentSearch" 
    campaignTrClass='crm-event-searchevent-form-block-campaign_id' campaignTdClass=''}

  </table>
</div>

{include file="CRM/common/showHide.tpl"}

{literal} 
<script type="text/javascript">
if ( document.getElementsByName('eventsByDates')[1].checked ) {
   show( 'id_fromToDates', 'block' );
}
</script>
{/literal} 
