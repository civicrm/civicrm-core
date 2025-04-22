{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Step 1 of New Event Wizard, and Edit Event Info form. *}

<div class="crm-block crm-form-block crm-event-manage-eventinfo-form-block">
  <table class="form-layout-compressed">
    {if !empty($form.template_id)}
      <tr class="crm-event-manage-eventinfo-form-block-template_id">
        <td class="label">{$form.template_id.label} {help id="id-select-template" isTemplate=$isTemplate}</td>
        <td>{$form.template_id.html}</td>
      </tr>
    {/if}
    <tr class="crm-event-manage-eventinfo-form-block-title">
      <td class="label">{$form.title.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='title' id=$eventID}{/if}</td>
      <td>{$form.title.html}</td>
    </tr>
    {if !empty($form.template_title)}
      <tr class="crm-event-manage-eventinfo-form-block-template_title">
        <td class="label">{$form.template_title.label} {help id="id-template-title"}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='template_title' id=$eventID}{/if}</td>
        <td>{$form.template_title.html}</td>
      </tr>
    {/if}
    {if !$isTemplate}
      <tr class="crm-event-manage-eventinfo-form-block-start_date">
        <td class="label">{$form.start_date.label}</td>
        <td>{$form.start_date.html}</td>
      </tr>
      <tr class="crm-event-manage-eventinfo-form-block-end_date">
        <td class="label">{$form.end_date.label}</td>
        <td>{$form.end_date.html}</td>
      </tr>
    {/if}
    <tr class="crm-event-manage-eventinfo-form-block-event_type_id">
      <td class="label">{$form.event_type_id.label}</td>
      <td>{$form.event_type_id.html}</td>
    </tr>

    <tr class="crm-event-manage-eventinfo-form-block-default_role_id">
      <td class="label">{$form.default_role_id.label} {help id="id-participant-role"}</td>
      <td>{$form.default_role_id.html}
      </td>
    </tr>
    {* CRM-7362 --add campaign *}
    {include file="CRM/Campaign/Form/addCampaignToComponent.tpl"
    campaignTrClass="crm-event-manage-eventinfo-form-block-campaign_id"}

    <tr class="crm-event-manage-eventinfo-form-block-summary">
      <td class="label">{$form.summary.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='summary' id=$eventID}{/if}</td>
      <td>{$form.summary.html}</td>
    </tr>
    <tr class="crm-event-manage-eventinfo-form-block-description">
      <td class="label">{$form.description.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='description' id=$eventID}{/if}</td>
      <td>{$form.description.html}</td>
    </tr>
    <tr class="crm-event-manage-eventinfo-form-block-participant_listing_id">
      <td class="label">{$form.participant_listing_id.label} {help id="id-listing" isTemplate=$isTemplate action=$action entityId=$eventID}</td>
      <td>{$form.participant_listing_id.html}</td>
    </tr>
    <tr class="crm-event-manage-eventinfo-form-block-max_participants">
      <td class="label">{$form.max_participants.label} {help id="id-max_participants" waitlist=$waitlist}</td>
      <td>
        {$form.max_participants.html|crmAddClass:four}
        {crmPermission has='administer CiviCRM'}
          <a class="crm-popup crm-hover-button" target="_blank" title="{ts escape='htmlattribute'}Edit Participant Status Options{/ts}" href="{crmURL p='civicrm/admin/participant_status' q='reset=1'}"><i class="crm-i fa-wrench" aria-hidden="true"></i></a>
        {/crmPermission}
      </td>
    </tr>
    <tr id="id-waitlist" class="crm-event-manage-eventinfo-form-block-has_waitlist">
      {if $waitlist}
        <td class="label">{$form.has_waitlist.label}</td>
        <td>{$form.has_waitlist.html} {help id="id-has_waitlist"}</td>
      {/if}
    </tr>
    <tr id="id-event_full" class="crm-event-manage-eventinfo-form-block-event_full_text">
      <td class="label">{$form.event_full_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='event_full_text' id=$eventID}{/if}
        <br />{help id="id-event_full_text"}&nbsp;&nbsp;&nbsp;&nbsp;</td>
      <td>{$form.event_full_text.html}</td>
    </tr>
    <tr id="id-waitlist-text" class="crm-event-manage-eventinfo-form-block-waitlist_text">
      {if !empty($form.waitlist_text)}
        <td class="label">{$form.waitlist_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='waitlist_text' id=$eventID} {/if}{help id="id-help-waitlist_text"}</td>
        <td>{$form.waitlist_text.html}</td>
      {/if}
    </tr>
    <tr class="crm-event-manage-eventinfo-form-block-is_active">
      <td>&nbsp;</td>
      <td>{$form.is_active.html} {$form.is_active.label} {help id="id-is_active"}</td>
    </tr>
    <tr class="crm-event-manage-eventinfo-form-block-is_public">
      <td>&nbsp;</td>
      <td>{$form.is_public.html} {$form.is_public.label} {help id="id-is_public"}</td>
    </tr>
    <tr class="crm-event-manage-eventinfo-form-block-separator">
      <td>&nbsp;</td>
      <td>{ts}Display event information:{/ts}</td>
    </tr>
    <tr class="crm-event-manage-eventinfo-form-block-is_map">
      <td>&nbsp;</td>
      <td>{$form.is_map.html} {$form.is_map.label} {help id="id-is_map"}</td>
    </tr>
    <tr class="crm-event-manage-eventinfo-form-block-is_show_calendar_links">
      <td>&nbsp;</td>
      <td>{$form.is_show_calendar_links.html} {$form.is_show_calendar_links.label} {help id="id-is_show_calendar_links"}</td>
    </tr>
    <tr class="crm-event-manage-eventinfo-form-block-is_share">
      <td>&nbsp;</td>
      <td>{$form.is_share.html} {$form.is_share.label} {help id="id-is_share"}
    </tr>

    {if $eventID AND !$isTemplate}
      <tr class="crm-event-manage-eventinfo-form-block-info_link">
        <td>&nbsp;</td>
        <td class="description">
          {if $config->userSystem->is_drupal || $config->userFramework EQ 'WordPress'}
            {ts}When this Event is active, create links to the Event Information page by copying and pasting the following URL:{/ts}<br />
            <strong>{crmURL a=1 fe=1 p='civicrm/event/info' q="reset=1&id=`$eventID`"}</strong>
          {elseif $config->userFramework EQ 'Joomla'}
            {ts 1=$eventID}When this Event is active, create front-end links to the Event Information page using the Menu Manager. Select <strong>Event Info Page</strong> and enter <strong>%1</strong> for the Event ID.{/ts}
          {/if}
        </td>
      </tr>
    {/if}
  </table>
  {include file="CRM/common/customDataBlock.tpl" entityID=$eventID cid=false}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
  {include file="CRM/common/showHide.tpl" elemType="table-row"}

  {include file="CRM/Form/validate.tpl"}
</div>
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var $form = $('form.{/literal}{$form.formClass}{literal}');
    $('#template_id', $form).change(function() {
      $(this).closest('.crm-ajax-container, #crm-main-content-wrapper')
        .crmSnippet({url: CRM.url('civicrm/event/add', {action: 'add', reset: 1, template_id: $(this).val(), is_template: $("input[name='is_template']").val()})})
        .crmSnippet('refresh');
    })
  });
</script>
{/literal}
