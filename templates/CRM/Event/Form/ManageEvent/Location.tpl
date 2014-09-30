{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{* this template used to build location block *}
{if !$addBlock}
   <div id="help">
        {ts}Use this form to configure the location and optional contact information for the event. This information will be displayed on the Event Information page. It will also be included in online registration pages and confirmation emails if these features are enabled.{/ts}
    </div>
{/if}

{if $addBlock}
{include file="CRM/Contact/Form/Edit/$blockName.tpl"}
{else}
<div class="crm-block crm-form-block crm-event-manage-location-form-block">
<div class="crm-submit-buttons">
   {include file="CRM/common/formButtons.tpl" location="top"}
</div>
    {if $locEvents}
      <table class="form-layout-compressed">
      <tr id="optionType" class="crm-event-manage-location-form-block-location_option">
        <td class="labels">
          {$form.location_option.label}
        </td>
        {foreach from=$form.location_option key=key item =item}
          {if $key|is_numeric}
            <td class="value"><strong>{$item.html}</strong></td>
            {/if}
                {/foreach}
       </tr>
      <tr id="existingLoc" class="crm-event-manage-location-form-block-loc_event_id">
        <td class="labels">
          {$form.loc_event_id.label}
        </td>
        <td class="value" colspan="2">
          {$form.loc_event_id.html|crmAddClass:huge}
        </td>
      </tr>
      <tr>
        <td id="locUsedMsg" colspan="3">
        </td>
      </tr>

    </table>
    {/if}



    <div id="newLocation">
      <h3>Address</h3>
    {* Display the address block *}
    {include file="CRM/Contact/Form/Edit/Address.tpl"}
  <table class="form-layout-compressed">
    {* Display the email block(s) *}
    {include file="CRM/Contact/Form/Edit/Email.tpl"}

    {* Display the phone block(s) *}
    {include file="CRM/Contact/Form/Edit/Phone.tpl"}
    </table>
   <table class="form-layout-compressed">
   <tr class="crm-event-is_show_location">
    <td colspan="2">{$form.is_show_location.label}</td>
    <td colspan="2">
      {$form.is_show_location.html}<br />
      <span class="description">{ts}Uncheck this box if you want to HIDE the event Address on Event Information and Registration pages as well as on email confirmations.{/ts}
    </td>
  </tr>
  </table>
<div class="crm-submit-buttons">
   {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
</div>

{* Include Javascript to hide and display the appropriate blocks as directed by the php code *}
{*include file="CRM/common/showHide.tpl"*}
{if $locEvents}
  {* include common additional blocks tpl *}
  {include file="CRM/common/additionalBlocks.tpl"}

<script type="text/javascript">
{literal}
CRM.$(function($) {
  //FIX ME: by default load 2 blocks and hide add and delete links
  //we should make additional block function more flexible to set max block limit
  buildBlocks('Email');
  buildBlocks('Phone');

  var $form = $('form.{/literal}{$form.formClass}{literal}'),
    locBlockId = {/literal}"{$form.loc_event_id.value.0}"{literal};

  displayMessage({/literal}{$locUsed}{literal});

  // build blocks only if it is not built
  function buildBlocks(element) {
    if (!$('[id='+ element +'_Block_2]').length) {
      buildAdditionalBlocks(element, 'CRM_Event_Form_ManageEvent_Location');
    }
  }

  hideAddDeleteLinks('Email');
  hideAddDeleteLinks('Phone');
  function hideAddDeleteLinks(element) {
    $('#add'+ element, $form).hide();
    $('[id='+ element +'_Block_2] a:last', $form).hide();
  }

  $('#loc_event_id', $form).change(function() {
    $.ajax({
      url: CRM.url('civicrm/ajax/locBlock', 'reset=1'),
      data: {'lbid': $(this).val()},
      dataType: 'json',
      success: function(data) {
        var selectLocBlockId = $('#loc_event_id').val();
        // Only change state when options are loaded
        if (data.address_1_state_province_id) {
          var defaultState = data.address_1_state_province_id;
          $('#address_1_state_province_id', $form).one('crmOptionsUpdated', function() {
            $(this).val(defaultState).change();
          });
          delete data.address_1_state_province_id;
        }
        for(i in data) {
          if ( i == 'count_loc_used' ) {
            if ( ((selectLocBlockId == locBlockId) && data.count_loc_used > 1) ||
                 ((selectLocBlockId != locBlockId) && data.count_loc_used > 0) ) {
              displayMessage(data.count_loc_used);
            } else {
              displayMessage(0);
            }
          } else {
            $('#'+i, $form).val(data[i]).change();
          }
        }
      }
    });
    return false;
  });

  function showLocFields(clear) {
    var createNew = document.getElementsByName("location_option")[0].checked;
    if (createNew) {
      $('#existingLoc', $form).hide();
      //clear all location fields values.
      if (clear !== false) {
        $(":input[id *= 'address_1_'], :input[id *= 'email_1_'], :input[id *= 'phone_1_']", $form).val("").change();
        {/literal}{if $config->defaultContactCountry}
          {if $config->defaultContactStateProvince}
            // Set default state once options are loaded
            var defaultState = {$config->defaultContactStateProvince}
            {literal}
              $('#address_1_state_province_id', $form).one('crmOptionsUpdated', function() {
                $(this).val(defaultState).change();
              });
            {/literal}
          {/if}
          // Set default country
          $('#address_1_country_id', $form).val({$config->defaultContactCountry}).change();
        {/if}{literal}
      }
      displayMessage(0);
    } else {
      $('#existingLoc', $form).show();
      if (clear !== false) {
        $('#loc_event_id', $form).change();
      }
    }
  }
  
  $('input[name=location_option]').click(showLocFields);
  showLocFields(false);

  function displayMessage(count) {
    if (count) {
      var msg = {/literal}'{ts escape="js" 1="%1"}This location is used by %1 other events. Modifying location information will change values for all events.{/ts}'{literal};
      $('#locUsedMsg', $form).text(ts(msg, {1: count})).addClass('status');
    } else {
      $('#locUsedMsg', $form).text(' ').removeClass('status');
    }
  }
});
{/literal}
</script>
{/if}

{/if} {* add block if end*}
