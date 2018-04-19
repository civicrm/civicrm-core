{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
<div class="help">
  {ts}Use this form to configure the location and optional contact information for the event. This information will be displayed on the Event Information page. It will also be included in online registration pages and confirmation emails if these features are enabled.{/ts}
</div>

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

  {include file="CRM/Contact/Form/Edit/Address.tpl" blockId=1}
  <table class="form-layout-compressed">
    <tr>
      <td><label>{ts}Email 1:{/ts}</label></td>
      <td>{$form.email.1.email.html|crmAddClass:email}</td>
    </tr>
    <tr>
      <td><label>{ts}Email 2:{/ts}</label></td>
      <td>{$form.email.2.email.html|crmAddClass:email}</td>
    </tr>
    <tr>
      <td><label>{ts}Phone 1:{/ts}</label></td>
      <td>{$form.phone.1.phone.html|crmAddClass:phone} {ts context="phone_ext"}ext.{/ts}&nbsp;{$form.phone.1.phone_ext.html|crmAddClass:four}&nbsp;{$form.phone.1.phone_type_id.html}</td>
    </tr>
    <tr>
      <td><label>{ts}Phone 2:{/ts}</label></td>
      <td>{$form.phone.2.phone.html|crmAddClass:phone} {ts context="phone_ext"}ext.{/ts}&nbsp;{$form.phone.2.phone_ext.html|crmAddClass:four}&nbsp;{$form.phone.2.phone_type_id.html}</td>
    </tr>
  </table>

  {if $locEvents}

    <script type="text/javascript">
      {literal}
      CRM.$(function($) {
        var $form = $('form.{/literal}{$form.formClass}{literal}'),
          locBlockId = {/literal}"{$form.loc_event_id.value.0}"{literal};

        displayMessage({/literal}{$locUsed}{literal});

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
