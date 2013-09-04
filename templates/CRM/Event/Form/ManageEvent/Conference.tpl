{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
<div class="crm-block crm-form-block crm-event-conference-form-block">
<div class="crm-submit-buttons">
   {include file="CRM/common/formButtons.tpl" location="top"}
</div>

    <table class="form-layout">
       <tr class="crm-event-conference-form-block-title">
    <td class="label">{$form.title.label}</td>
    <td>{$form.title.html}</td>
       </tr>
    </table>

    <div id="parent_event_name">
      <table id="parent_event_name" class="form-layout">
          <tr class="crm-event-conference-form-block-parent_event_name">
             <td class="label">{$form.parent_event_name.label}</td>
             <td>
                 {$form.parent_event_name.html|crmAddClass:huge}
             </td>
          </tr>
      </table>
    </div>

    <div id="conference_slot_id">
      <table id="conference_slot_id" class="form-layout">
          <tr class="crm-event-conference-form-block-slot_label_id">
             <td class="label">{$form.slot_label_id.label}</td>
             <td>{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='slot_label_id' id=$id}{/if}{$form.slot_label_id.html|crmAddClass:big}
             </td>
          </tr>
      </table>
    </div>
    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
</div>

{include file="CRM/common/formNavigate.tpl"}

{literal}
<script type="text/javascript">
var eventUrl = "{/literal}{crmURL p='civicrm/ajax/event' h=0}{literal}";

cj('input#parent_event_name').autocomplete(
    eventUrl,
    {
        width : 280,
        selectFirst : false,
        matchContains: true,
    }
).result( function(event, data, formatted)
    {
        cj( "input#parent_event_name" ).val( data[0] );
        cj( "input[name=parent_event_id]" ).val( data[1] );
    }
).bind( 'click', function( ) { cj( "input#parent_event_name" ).val(''); });
</script>
{/literal}
