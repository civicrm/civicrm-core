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
        <td class="label">
          {$form.location_option.label}
        </td>
        {foreach from=$form.location_option key=key item =item}
          {if $key|is_numeric}
            <td class="value"><strong>{$item.html}</strong></td>
          {/if}
        {/foreach}
      </tr>
      <tr id="existingLoc" class="crm-event-manage-location-form-block-loc_event_id">
        <td class="label">
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
        <td class="label">
          {$form.is_show_location.label} {help id="is_show_location"}
        </td>
        <td class="values">
          {$form.is_show_location.html}
        </td>
      </tr>
      <tr class="crm-event-manage-location-form-block-is_map">
        <td class="label">{$form.is_map.label} {help id="is_map"}</td>
        <td>{$form.is_map.html}</td>
      </tr>
    </table>
  {/if}

  {include file="CRM/Contact/Form/Edit/Address.tpl" blockId=1 masterAddress='' parseStreetAddress='' className='CRM_Event_Form_ManageEvent_Location'}
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
          const selectLocBlockId = $(this).val();
          if (!selectLocBlockId) {
            $(":input[id *= 'address_1_'], :input[id *= 'email_'], :input[id *= 'phone_']", $form).val('').change();
            displayMessage(0);
            return false;
          }
          CRM.api4('LocBlock', 'get', {
            select: [
              'address_id.*',
              'email_id.email',
              'email_2_id.email',
              'phone_id.phone',
              'phone_id.phone_type_id',
              'phone_id.phone_ext',
              'phone_2_id.phone',
              'phone_2_id.phone_type_id',
              'phone_2_id.phone_ext',
              'COUNT(event.id) AS count_loc_used'
            ],
            join: [
              ['Event AS event', 'LEFT', ['event.loc_block_id', '=', 'id']]
            ],
            where: [['id', '=', selectLocBlockId]],
            groupBy: ['id']
          }).then((locBlocks) => {
            if (!locBlocks.length) {
              return;
            }
            var locBlock = locBlocks[0],
              data = {};
            for (const [key, val] of Object.entries(locBlock)) {
              if (key.startsWith('address_id.') && key !== 'address_id.id') {
                data['address_1_' + key.substring(11)] = val ?? '';
              } else if (key.startsWith('email_id.')) {
                data['email_1_' + key.substring(9)] = val ?? '';
              } else if (key.startsWith('email_2_id.')) {
                data['email_2_' + key.substring(11)] = val ?? '';
              } else if (key.startsWith('phone_id.')) {
                data['phone_1_' + key.substring(9)] = val ?? '';
              } else if (key.startsWith('phone_2_id.')) {
                data['phone_2_' + key.substring(11)] = val ?? '';
              }
            }
            // Only change state when options are loaded.
            if (data.address_1_state_province_id) {
              var defaultState = data.address_1_state_province_id;
              $('#address_1_state_province_id', $form).one('crmOptionsUpdated', function() {
                $(this).val(defaultState).change();
              });
              delete data.address_1_state_province_id;
            }
            for (const [field, val] of Object.entries(data)) {
              $('#' + field, $form).val(val).change();
            }
            var countLocUsed = locBlock.count_loc_used || 0;
            if ( ((selectLocBlockId == locBlockId) && countLocUsed > 1) ||
              ((selectLocBlockId != locBlockId) && countLocUsed > 0) ) {
              // Counts retrieved via AJAX are already "other" Event counts.
              displayMessage(parseInt(countLocUsed, 10) + 1);
            } else {
              displayMessage(0);
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
