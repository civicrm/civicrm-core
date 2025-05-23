{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $addProfileBottomAdd OR $addProfileBottom}
  <td scope="row" class="label" width="20%">
    {if $addProfileBottomAdd}{$form.additional_custom_post_id_multiple[$profileBottomNumAdd].label}
    {else}{$form.custom_post_id_multiple[$profileBottomNum].label}{/if}</td>
  <td>
    {if $addProfileBottomAdd}{$form.additional_custom_post_id_multiple[$profileBottomNumAdd].html}
    {else}{$form.custom_post_id_multiple[$profileBottomNum].html}{/if}
    <span class='profile_bottom_link_remove'><a href="#" class="crm-hover-button crm-button-rem-profile" data-addtlPartc="{$addProfileBottomAdd}"><i class="crm-i fa-trash" aria-hidden="true"></i> {ts}remove profile{/ts}</a></span>
    <span class='profile_bottom_link'>&nbsp;<a href="#" class="crm-hover-button crm-button-add-profile"><i class="crm-i fa-plus-circle" aria-hidden="true"></i> {ts}add another profile (bottom of page){/ts}</a></span>
    <br/><span class="profile-links"></span>
  </td>
{else}
{assign var=eventID value=$id}
  <div class="help">
    {ts}If you want to provide an Online Registration page for this event, check the first box below and then complete the fields on this form.{/ts}
    {help id="id-event-reg"}
  </div>
<div class="crm-block crm-form-block crm-event-manage-registration-form-block">

<div id="register">
  <table class="form-layout">
    <tr class="crm-event-manage-registration-form-block-is_online_registration">
      <td class="label">{$form.is_online_registration.label}</td>
      <td>{$form.is_online_registration.html}</td>
    </tr>
  </table>
</div>
<div class="spacer"></div>
<div id="registration_blocks">
<table class="form-layout-compressed">

  <tr class="crm-event-manage-registration-form-block-registration_link_text">
    <td scope="row" class="label"
        width="20%">{$form.registration_link_text.label} <span class="crm-marker">*</span>{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='registration_link_text' id=$eventID}{/if}</td>
    <td>{$form.registration_link_text.html} {help id="id-link_text"}</td>
  </tr>
  {if !$isTemplate}
    <tr class="crm-event-manage-registration-form-block-registration_start_date">
      <td scope="row" class="label" width="20%">{$form.registration_start_date.label}</td>
      <td>{$form.registration_start_date.html}</td>
    </tr>
    <tr class="crm-event-manage-registration-form-block-registration_end_date">
      <td scope="row" class="label" width="20%">{$form.registration_end_date.label}</td>
      <td>{$form.registration_end_date.html}</td>
    </tr>
  {/if}
  <tr class="crm-event-manage-registration-form-block-is_multiple_registrations">
    <td scope="row" class="label" width="20%">{$form.is_multiple_registrations.label}</td>
    <td>{$form.is_multiple_registrations.html} {help id="id-allow_multiple"}</td>
  </tr>
  <tr class="crm-event-manage-registration-form-block-maximum_additional_participants" id="id-max-additional-participants">
    <td scope="row" class="label" width="20%">{$form.max_additional_participants.label}</td>
    <td>{$form.max_additional_participants.html} {help id="id-max_additional"}</td>
  </tr>
  <tr class="crm-event-manage-registration-form-block-allow_same_participant_emails">
    <td scope="row" class="label" width="20%">{$form.allow_same_participant_emails.label}</td>
    <td>{$form.allow_same_participant_emails.html} {help id="id-allow_same_email"}</td>
  </tr>
  <tr class="crm-event-manage-registration-form-block-dedupe_rule_group_id">
    <td scope="row" class="label" width="20%">{$form.dedupe_rule_group_id.label}</td>
    <td>{$form.dedupe_rule_group_id.html} {help id="id-dedupe_rule_group_id"}</td>
  </tr>
  <tr class="crm-event-manage-registration-form-block-requires_approval">
    {if !empty($form.requires_approval)}
      <td scope="row" class="label" width="20%">{$form.requires_approval.label}</td>
      <td>{$form.requires_approval.html} {help id="id-requires_approval"}</td>
    {/if}
  </tr>
  <tr id="id-approval-text" class="crm-event-manage-registration-form-block-approval_req_text">
    {if !empty($form.approval_req_text)}
      <td scope="row" class="label"
          width="20%">{$form.approval_req_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='approval_req_text' id=$eventID}{/if}</td>
      <td>{$form.approval_req_text.html}</td>
    {/if}
  </tr>
  <tr class="crm-event-manage-registration-form-block-expiration_time">
    <td scope="row" class="label" width="20%">{$form.expiration_time.label}</td>
    <td>{$form.expiration_time.html|crmAddClass:four} {help id="id-expiration_time"}</td>
  </tr>
  <tr class="crm-event-manage-registration-form-block-selfcancelxfer">
    <td scope="row" class="label" width="20%">{$form.allow_selfcancelxfer.label}</td>
    <td>{$form.allow_selfcancelxfer.html} {help id="id-allow_selfcancelxfer"}</td>
  </tr>
  <tr class="crm-event-manage-registration-form-block-selfcancelxfer_time">
    <td scope="row" class="label" width="20%">{$form.selfcancelxfer_time.label}</td>
    <td>{$form.selfcancelxfer_time.html|crmAddClass:four} {help id="id-selfcancelxfer_time"}</td>
  </tr>
</table>
<div class="spacer"></div>

{*Registration Block*}
<details id="registration" {if !$defaultsEmpty}open{/if}>
  <summary>{ts}Registration Screen{/ts}</summary>
  <div id="registration_screen" class="crm-accordion-body">
    <table class="form-layout-compressed">
      <tr class="crm-event-manage-registration-form-block-intro_text">
        <td scope="row" class="label"
            width="20%">{$form.intro_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='intro_text' id=$eventID}{/if}</td>
        <td>{$form.intro_text.html}</td>
      </tr>
      <tr class="crm-event-manage-registration-form-block-footer_text">
        <td scope="row" class="label"
            width="20%">{$form.footer_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='footer_text' id=$eventID}{/if}</td>
        <td>{$form.footer_text.html}</td>
      </tr>
    </table>
    <table class="form-layout-compressed">
      <tr class="crm-event-manage-registration-form-block-custom_pre_id">
        <td scope="row" class="label" width="20%">{$form.custom_pre_id.label} {help id="event-profile"}</td>
        <td>{$form.custom_pre_id.html}</td>
      </tr>
      <tr id="profile_post" class="crm-event-manage-registration-form-block-custom_post_id">
        <td scope="row" class="label" width="20%">{$form.custom_post_id.label}</td>
        <td>{$form.custom_post_id.html}
          <span class='profile_bottom_link_main {if $profilePostMultiple}hiddenElement{/if}'><a href="#" class="crm-hover-button crm-button-add-profile"><i class="crm-i fa-plus-circle" aria-hidden="true"></i> {ts}add another profile (bottom of page){/ts}</a></span>
          <br/>
        </td>
      </tr>

      {if $profilePostMultiple}
        {foreach from=$profilePostMultiple item=profilePostId key=profilePostNum name=profilePostIdName}
          <tr id="custom_post_id_multiple_{$profilePostNum}_wrapper"
              class='crm-event-manage-registration-form-block-custom_post_multiple'>
            <td scope="row" class="label" width="20%">{$form.custom_post_id_multiple.$profilePostNum.label}</td>
            <td>{$form.custom_post_id_multiple.$profilePostNum.html}
              &nbsp;
              <span class='profile_bottom_link_remove'>
                <a href="#" class="crm-hover-button crm-button-rem-profile">
                  <i class="crm-i fa-trash" aria-hidden="true"></i> {ts}remove profile{/ts}
                </a>
              </span>
              &nbsp;&nbsp;
              <span class='profile_bottom_link' {if !$smarty.foreach.profilePostIdName.last} style="display: none"{/if}>
                <a href="#" class="crm-hover-button crm-button-add-profile">
                  <i class="crm-i fa-plus-circle" aria-hidden="true"></i>
                  {ts}add another profile (bottom of page){/ts}
                </a>
              </span>
              <br/><span class="profile-links"></span>
            </td>
          </tr>
        {/foreach}
      {/if}
    </table>
    <table class="form-layout-compressed">
      <tr id="additional_profile_pre" class="crm-event-manage-registration-form-block-additional_custom_pre_id">
        <td scope="row" class="label" width="20%">{$form.additional_custom_pre_id.label}</td>
        <td>{$form.additional_custom_pre_id.html}
          <span class="profile-links"></span>
        </td>
      </tr>
      <tr id="additional_profile_post" class="crm-event-manage-registration-form-block-additional_custom_post_id">
        <td scope="row" class="label" width="20%">{$form.additional_custom_post_id.label}</td>
        <td>{$form.additional_custom_post_id.html}
          <span class='profile_bottom_add_link_main{if $profilePostMultipleAdd} hiddenElement{/if}'><a href="#" class="crm-hover-button crm-button-add-profile"><i class="crm-i fa-plus-circle" aria-hidden="true"></i> {ts}add another profile (bottom of page){/ts}</a></span>
          <br/><span class="profile-links"></span>
        </td>
      </tr>
      {if $profilePostMultipleAdd}
        {foreach from=$profilePostMultipleAdd item=profilePostIdA key=profilePostNumA name=profilePostIdAName}
          <tr id='additional_custom_post_id_multiple_{$profilePostNumA}_wrapper'
              class='crm-event-manage-registration-form-block-additional_custom_post_multiple'>
            <td scope="row" class="label"
                width="20%">{$form.additional_custom_post_id_multiple.$profilePostNumA.label}</td>
            <td>{$form.additional_custom_post_id_multiple.$profilePostNumA.html}
              &nbsp;
              <span class='profile_bottom_add_link_remove'>
                <a href="#" class="crm-hover-button crm-button-rem-profile">
                  <i class="crm-i fa-trash" aria-hidden="true"></i> {ts}remove profile{/ts}
                </a>
              </span>
              <span class='profile_bottom_add_link' {if !$smarty.foreach.profilePostIdAName.last} style="display: none"{/if}>
                <a href="#" class="crm-hover-button crm-button-add-profile">
                  <i class="crm-i fa-plus-circle" aria-hidden="true"></i>
                  {ts}add another profile (bottom of page){/ts}
                </a>
              </span>
              <br/><span class="profile-links"></span>
            </td>
          </tr>
        {/foreach}
      {/if}
    </table>
  </div>
</details>

{*Confirmation Block*}
<details id="confirm" {if !$defaultsEmpty}open{/if}>
  <summary class="collapsible-title">{ts}Confirmation Screen{/ts}</summary>
  <div class="crm-accordion-body">
    {if !$is_monetary}
    <table class="form-layout-compressed">
      <tr class="crm-event-manage-registration-form-block-is_confirm_enabled">
        <td scope="row" class="label" width="20%">{$form.is_confirm_enabled.label}</td>
        <td>{$form.is_confirm_enabled.html}
          <div class="description">{ts}Optionally hide the confirmation screen for free events.{/ts}</div>
        </td>
      </tr>
    </table>
    {/if}
    <table class="form-layout-compressed" id="confirm_screen_settings">
      <tr class="crm-event-manage-registration-form-block-confirm_title">
        <td scope="row" class="label" width="20%">{$form.confirm_title.label} <span
            class="crm-marker">*</span> {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='confirm_title' id=$eventID}{/if}
        </td>
        <td>{$form.confirm_title.html}</td>
      </tr>
      <tr class="crm-event-manage-registration-form-block-confirm_text">
        <td scope="row" class="label"
            width="20%">{$form.confirm_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='confirm_text' id=$eventID}{/if}</td>
        <td>{$form.confirm_text.html}</td>
      </tr>
      <tr class="crm-event-manage-registration-form-block-confirm_footer_text">
        <td scope="row" class="label"
            width="20%">{$form.confirm_footer_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='confirm_footer_text' id=$eventID}{/if}</td>
        <td>{$form.confirm_footer_text.html}</td>
      </tr>
    </table>
  </div>
</details>

{*ThankYou Block*}
<details id="thankyou" {if !$defaultsEmpty}open{/if}>
  <summary>{ts}Thank-you Screen{/ts}</summary>
  <div class="crm-accordion-body">
    <table class="form-layout-compressed">
      <tr class="crm-event-manage-registration-form-block-confirm_thankyou_title">
        <td scope="row" class="label" width="20%">{$form.thankyou_title.label} <span
            class="crm-marker">*</span> {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='thankyou_title' id=$eventID}{/if}
        </td>
        <td>{$form.thankyou_title.html}</td>
      </tr>
      <tr class="crm-event-manage-registration-form-block-confirm_thankyou_text">
        <td scope="row" class="label" width="20%">{$form.thankyou_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='thankyou_text' id=$eventID}{/if}</td>
        <td>{$form.thankyou_text.html}</td>
      </tr>
      <tr class="crm-event-manage-registration-form-block-confirm_thankyou_footer_text">
        <td scope="row" class="label"
            width="20%">{$form.thankyou_footer_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='thankyou_footer_text' id=$eventID}{/if}</td>
        <td>{$form.thankyou_footer_text.html}</td>
      </tr>
    </table>
  </div
</details>

{* Confirmation Email Block *}
<details id="mail" {if !$defaultsEmpty}open{/if}>
  <summary class="collapsible-title">{ts}Confirmation Email{/ts}</summary>
  <div class="crm-accordion-wrapper">
    <table class="form-layout-compressed">
      <tr class="crm-event-manage-registration-form-block-is_email_confirm">
        <td scope="row" class="label" width="20%">{$form.is_email_confirm.label} {help id="id-is_email_confirm"}</td>
        <td>{$form.is_email_confirm.html}</td>
      </tr>
    </table>
    <div id="confirmEmail">
      <table class="form-layout-compressed">
        <tr class="crm-event-manage-registration-form-block-confirm_email_text">
          <td scope="row" class="label"
              width="20%">{$form.confirm_email_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='confirm_email_text' id=$eventID}{/if}</td>
          <td>{$form.confirm_email_text.html}<br/>
            <span
              class="description">{ts}Additional message or instructions to include in confirmation email.{/ts}</span>
          </td>
        </tr>
        <tr class="crm-event-manage-registration-form-block-confirm_from_name">
          <td scope="row" class="label" width="20%">{$form.confirm_from_name.label} <span
              class="crm-marker">*</span> {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='confirm_from_name' id=$eventID}{/if}
          </td>
          <td>{$form.confirm_from_name.html}</td>
        </tr>
        <tr class="crm-event-manage-registration-form-block-confirm_from_email">
          <td scope="row" class="label" width="20%">{$form.confirm_from_email.label} <span class="crm-marker">*</span></td>
          <td>{$form.confirm_from_email.html}</td>
        </tr>
        <tr class="crm-event-manage-registration-form-block-cc_confirm">
          <td scope="row" class="label" width="20%">{$form.cc_confirm.label} {help id="id-cc_confirm"}</td>
          <td>{$form.cc_confirm.html}</td>
        </tr>
        <tr class="crm-event-manage-registration-form-block-bcc_confirm">
          <td scope="row" class="label" width="20%">{$form.bcc_confirm.label} {help id="id-bcc_confirm"}</td>
          <td>{$form.bcc_confirm.html}</td>
        </tr>
      </table>
    </div>
  </div>
</details>
</div> {*end of div registration_blocks*}
    </div>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
  {include file="CRM/common/showHide.tpl"}
{include file="CRM/common/showHideByFieldValue.tpl"
trigger_field_id    ="is_online_registration"
trigger_value       =""
target_element_id   ="registration_blocks"
target_element_type ="block"
field_type          ="radio"
invert              = 0
}
{if !$is_monetary}
{include file="CRM/common/showHideByFieldValue.tpl"
trigger_field_id    ="is_confirm_enabled"
trigger_value       =""
target_element_id   ="confirm_screen_settings"
target_element_type ="block"
field_type          ="radio"
invert              = 0
}
{/if}
{include file="CRM/common/showHideByFieldValue.tpl"
trigger_field_id    ="is_email_confirm"
trigger_value       =""
target_element_id   ="confirmEmail"
target_element_type ="block"
field_type          ="radio"
invert              = 0
}
{if !empty($form.requires_approval)}
{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="requires_approval"
    trigger_value       =""
    target_element_id   ="id-approval-text"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
}
{/if}

{*include profile link function*}
{include file="CRM/common/buildProfileLink.tpl"}

<script type="text/javascript">
{literal}    (function($, _) { // Generic Closure

    $(".crm-submit-buttons button").click( function() {
      $(".dedupenotify .ui-notify-close").click();
    });

    var profileBottomCount = Number({/literal}{$profilePostMultiple|@count}{literal});
    var profileBottomCountAdd = Number({/literal}{$profilePostMultipleAdd|@count}{literal});

    function addBottomProfile( e ) {
        var urlPath;
        e.preventDefault();

        var addtlPartc = $(this).data('addtlPartc');

        if ($(this).closest("td").children("input").attr("name").indexOf("additional_custom_post") > -1 || addtlPartc) {
            profileBottomCountAdd++
            urlPath = CRM.url('civicrm/event/manage/registration', { addProfileBottomAdd: 1, addProfileNumAdd: profileBottomCountAdd, snippet: 4 } ) ;
        } else {
            profileBottomCount++;
            urlPath = CRM.url('civicrm/event/manage/registration', { addProfileBottom: 1 , addProfileNum : profileBottomCount, snippet: 4 } ) ;
        }

        $(this).closest('tbody').append('<tr class="additional_profile"></tr>');
        var $el = $(this).closest('tbody').find('tr:last');
        $el.load(urlPath, function() { $(this).trigger('crmLoad') });
        $(this).closest(".profile_bottom_link_main, .profile_bottom_link, .profile_bottom_add_link_main").hide();
        $el.find(".profile_bottom_link_main, .profile_bottom_link, .profile_bottom_add_link_main").show();
    }

    function removeBottomProfile( e ) {
        e.preventDefault();

        $(e.target).closest('tr').hide().find('.crm-profile-selector').val('');
        $(e.target).closest('tbody').find('tr:visible:last .profile_bottom_link_main,tr:visible:last .profile_bottom_add_link, tr:visible:last .profile_bottom_link, tr:visible:last .profile_bottom_add_link_main').show();
    }

    var
      strSameAs = '{/literal}{ts escape='js'}- same as for main contact -{/ts}{literal}',
      strSelect = '{/literal}{ts escape='js'}- select -{/ts}{literal}';

    $('#crm-container').on('crmLoad', function() {
        var $container = $("[id^='additional_profile_'],.additional_profile").not('.processed').addClass('processed');
        $container.find(".crm-profile-selector-select select").each( function() {
            var $select = $(this);
            var selected = $select.find(':selected').val(); //cache the default
            $select.find('option[value=""]').remove();
            $select.prepend('<option value="">'+strSameAs+'</option>');
            if ($select.closest('tr').is(':not([id*="_pre"])')) {
               $select.prepend('<option value="">'+strSelect+'</option>');
            }
            $select.find('option[value="'+selected+'"]').attr('selected', 'selected'); //restore default
        });
    });

$(function($) {

    var allow_multiple = $("#is_multiple_registrations");
    if ( !allow_multiple.prop('checked') ) {
        $('#additional_profile_pre,#additional_profile_post,#id-max-additional-participants').hide();
    }
    allow_multiple.change( function( ) {
        if ( !$(this).prop('checked') ) {
            $("#additional_custom_pre_id,#additional_custom_post_id").val('');
            $(".crm-event-manage-registration-form-block-additional_custom_post_multiple").hide();
            $('#additional_profile_pre,#additional_profile_post,#id-max-additional-participants').hide();
        } else {
            $(".crm-event-manage-registration-form-block-additional_custom_post_multiple").show();
            $('#additional_profile_pre,#additional_profile_post,#id-max-additional-participants').show();
        }
    });

    var allow_selfCancel = $("#allow_selfcancelxfer");
    if ( !allow_selfCancel.prop('checked') ) {
        $('#selfcancelxfer_time').hide();
        $('.crm-event-manage-registration-form-block-selfcancelxfer_time').hide();
    }
    allow_selfCancel.change( function( ) {
        if ( !$(this).prop('checked') ) {
            $("#selfcancelxfer_time").val('');
            $('#selfcancelxfer_time').hide();
            $('.crm-event-manage-registration-form-block-selfcancelxfer_time').hide();
        } else {
          $('#selfcancelxfer_time').show();
          $('.crm-event-manage-registration-form-block-selfcancelxfer_time').show();
        }

    });

    $('#registration_blocks').on('click', '.crm-button-add-profile', addBottomProfile);
    $('#registration_blocks').on('click', '.crm-button-rem-profile', removeBottomProfile);

    $('#crm-container').on('crmLoad', function(e) {
        $('tr[id^="additional_profile"] input[id^="additional_custom_"]').change(function(e) {
            var $input = $(e.target);
            if ( $input.val() == '') {
                var $selected = $input.closest('tr').find('.crm-profile-selector-select :selected');
                if ($selected.text() == strSelect) { $input.val('none'); }
            }
        });
    });

}); // END onReady
}(CRM.$, CRM._)); //Generic Closure
{/literal}
</script>
{/if}
