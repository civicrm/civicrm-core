{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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

    <div>
      <table id="parent_event_name_wrapper" class="form-layout">
          <tr class="crm-event-conference-form-block-parent_event_name">
             <td class="label">{$form.parent_event_id.label}</td>
             <td>
                 {$form.parent_event_id.html|crmAddClass:huge}
             </td>
          </tr>
      </table>
    </div>

    <div>
      <table id="conference_slot_id_wrapper" class="form-layout">
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
