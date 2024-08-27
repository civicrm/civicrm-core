{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template used to build location block *}
<div class="help">
  {ts}Use this form to configure the location and optional contact information for the event. This information will be displayed on the Event Information page. It will also be included in online registration pages and confirmation emails if these features are enabled.{/ts}
</div>

<div class="crm-block crm-form-block crm-event-manage-location-form-block">
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
      <tr class="crm-event-manage-location-form-block-is_show_location">
        <td class="labels">
          {$form.is_show_location.label} {help id="id-is_show_location"}
        </td>
        <td class="values">
          {$form.is_show_location.html}
        </td>
      </tr>
    </table>
  {/if}

  {include file="CRM/Contact/Form/Edit/Address.tpl" blockId=1}
  <table class="form-layout-compressed">
    <tr>
      <td>{$form.email.1.email.label}</td>
      <td>{$form.email.1.email.html|crmAddClass:email}</td>
      {include file="CRM/Contact/Form/Inline/BlockCustomData.tpl" entity=email customFields=$custom_fields_email blockId=1 actualBlockCount=2}
    </tr>
    <tr>
      <td>{$form.email.2.email.label}</td>
      <td>{$form.email.2.email.html|crmAddClass:email}</td>
      {include file="CRM/Contact/Form/Inline/BlockCustomData.tpl" entity=email customFields=$custom_fields_email blockId=2 actualBlockCount=2}
    </tr>
    <tr>
      <td>{$form.phone.1.phone.label}</td>
      <td>{$form.phone.1.phone.html|crmAddClass:phone} {$form.phone.1.phone_ext.label}&nbsp;{$form.phone.1.phone_ext.html|crmAddClass:four}&nbsp;{$form.phone.1.phone_type_id.html}</td>
    </tr>
    <tr>
      <td>{$form.phone.2.phone.label}</td>
      <td>{$form.phone.2.phone.html|crmAddClass:phone} {$form.phone.2.phone_ext.label}&nbsp;{$form.phone.2.phone_ext.html|crmAddClass:four}&nbsp;{$form.phone.2.phone_type_id.html}</td>
    </tr>
  </table>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

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
              // Only change state when options are loaded.
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
                    // Counts retrieved via AJAX are already "other" Event counts.
                    displayMessage(parseInt(data.count_loc_used) + 1);
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
            // Clear all location fields values.
            if (clear !== false) {
              $(":input[id *= 'address_1_'], :input[id *= 'email_1_'], :input[id *= 'phone_1_']", $form).val("").change();
              {/literal}{if $config->defaultContactCountry}
              {if $config->defaultContactStateProvince}
              // Set default state once options are loaded.
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
          if (parseInt(count) > 1) {
            var otherCount = parseInt(count) - 1;
            if (otherCount > 1) {
              var msg = {/literal}'{ts escape="js" 1="%1"}This location is used by %1 other events. Modifying location information will change values for all events.{/ts}'{literal};
            } else {
              var msg = {/literal}'{ts escape="js" 1="%1"}This location is used by %1 other event. Modifying location information will also change values for that event.{/ts}'{literal};
            }
            $('#locUsedMsg', $form).text(ts(msg, {1: otherCount})).addClass('status');
          } else {
            $('#locUsedMsg', $form).text(' ').removeClass('status');
          }
        }
      });
      {/literal}
    </script>
  {/if}
