{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{**
 * This file provides the HTML for the on-behalf-of form.
 * Also used for related contact edit form.
 * FIXME: This is way more complex than it needs to be
 * FIXME: Why are we not just using the dynamic form tpl to display this profile?
 *}
{if $form.is_for_organization}
  <div class="crm-public-form-item crm-section {$form.is_for_organization.name}-section">
    <div class="label">&nbsp;</div>
    <div class="content">
      {$form.is_for_organization.html}&nbsp;{$form.is_for_organization.label}
    </div>
    <div class="clear"></div>
  </div>
{/if}

<div class="crm-public-form-item" id="on-behalf-block">
  {crmRegion name="onbehalf-block"}
  {if $onBehalfOfFields && $onBehalfOfFields|@count}
    <fieldset>
      <legend>{$fieldSetTitle}</legend>
      {if $form.org_option}
        <div id='orgOptions' class="section crm-public-form-item crm-section">
          <div class="content">
            {$form.org_option.html}
          </div>
        </div>
      {/if}
      {include file="CRM/UF/Form/Block.tpl" fields=$onBehalfOfFields mode=8 prefix='onbehalf'}
    </fieldset>
  {/if}
  {/crmRegion}
</div>

{literal}
<script type="text/javascript">

  CRM.$(function($) {

    var orgOption = $("input:radio[name=org_option]:checked").attr('id');
    var onBehalfRequired = {/literal}'$onBehalfRequired'{literal};
    var onbehalfof_id = $('#onbehalfof_id');
    var is_for_organization = $('#is_for_organization');

    selectCreateOrg(orgOption, false);

    if (is_for_organization.length) {
      showHideOnBehalfOfBlock();

      is_for_organization.on('change', function() {
        showHideOnBehalfOfBlock();
      });
    }

    function showHideOnBehalfOfBlock() {
      $('#on-behalf-block').toggle(is_for_organization.is(':checked'));

      if (is_for_organization.is(':checked')) {
        $('#onBehalfOfOrg select.crm-select2').removeClass('crm-no-validate');
      }
      else {
        $('#onBehalfOfOrg select.crm-select2').addClass('crm-no-validate');
      }
    }

    $("input:radio[name='org_option']").click( function( ) {
      var orgOption = $(this).attr('id');
      selectCreateOrg(orgOption, true);
    });

    onbehalfof_id.change(function() {
      setLocationDetails($(this).val());
    }).change();

    if (onbehalfof_id.length) {
      setLocationDetails(onbehalfof_id.val());
    }

    function resetValues() {
      // Don't trip chain-select when clearing values
      $('.crm-chain-select-control', "#select_org div").select2('val', '');
      $('input[type=text], select, textarea', "#select_org div").not('.crm-chain-select-control, #onbehalfof_id').val('').change();
      $('input[type=radio], input[type=checkbox]', "#select_org div").prop('checked', false).change();

      $('#on-behalf-block input').not('input[type=checkbox], input[type=radio], #onbehalfof_id').val('');
      // clear checkboxes and radio
      $('#on-behalf-block')
              .find('input[type=checkbox], input[type=radio]')
              .not('input[name=org_option]')
              .attr('checked', false);
    }

    function selectCreateOrg( orgOption, reset ) {
      if (orgOption == 'CIVICRM_QFID_0_org_option') {
        $("#onbehalfof_id").show().change();
        $("input#onbehalf_organization_name").hide();
      }
      else if (orgOption == 'CIVICRM_QFID_1_org_option') {
        $("input#onbehalf_organization_name").show();
        $("#onbehalfof_id").hide();
        reset = true;
      }

      if ( reset ) {
        resetValues();
      }
    }

    function setLocationDetails(contactID , reset) {
      resetValues();
      var locationUrl = {/literal}'{$locDataURL}'{literal} + contactID;
      var submittedOnBehalfInfo = {/literal}'{$submittedOnBehalfInfo}'{literal};
      var submittedCID = {/literal}"{$submittedOnBehalf}"{literal};

      if (submittedOnBehalfInfo) {
        submittedOnBehalfInfo = $.parseJSON(submittedOnBehalfInfo);

        if (submittedCID == contactID) {
          $.each(submittedOnBehalfInfo, function(key, value) {
            //handle checkboxes
            if (typeof value === 'object') {
              $.each(value, function(k, v) {
                $('#onbehalf_' + key + '_' + k).prop('checked', v);
              });
            }
            else if ($('#onbehalf_' + key).length) {
              $('#onbehalf_' + key ).val(value);
            }
            //radio buttons
            else if ($("input[name='onbehalf[" + key + "]']").length) {
                $("input[name='onbehalf[" + key + "]']").val([value]);
              }
          });
          return;
        }
      }

      $.ajax({
        url         : locationUrl,
        dataType    : "json",
        timeout     : 5000, //Time in milliseconds
        success     : function(data, status) {
          for (var ele in data) {
            if ($("#"+ ele).hasClass('crm-chain-select-target')) {
              $("#"+ ele).data('newVal', data[ele].value).off('.autofill').on('crmOptionsUpdated.autofill', function() {
                $(this).off('.autofill').val($(this).data('newVal')).change();
              });
            }
            else if ($('#' + ele).data('select2')) {
              $('#' + ele).select2('val', data[ele].value);
            }
            if (data[ele].type == 'Radio') {
              if (data[ele].value) {
                var fldName = ele.replace('onbehalf_', '');
                $("input[name='onbehalf["+ fldName +"]']").filter("[value='" + data[ele].value + "']").prop('checked', true);
              }
            }
            else if (data[ele].type == 'CheckBox') {
              for (var selectedOption in data[ele].value) {
                var fldName = ele.replace('onbehalf_', '');
                $("input[name='onbehalf["+ fldName+"]["+ selectedOption +"]']").prop('checked','checked');
              }
            }
            else if (data[ele].type == 'AdvMulti-Select') {
                var customFld = ele.replace('onbehalf_', '');
                // remove empty value if any
                $('#onbehalf\\['+ customFld +'\\]-f option[value=""]').remove();
                $('#onbehalf\\['+ customFld +'\\]-t option[value=""]').remove();

                for (var selectedOption in data[ele].value) {
                  // remove selected values from left and selected values to right
                  $('#onbehalf\\['+ customFld +'\\]-f option[value="' + selectedOption + '"]').remove()
                          .appendTo('#onbehalf\\['+ customFld +'\\]-t');
                  $('#onbehalf_'+ customFld).val(selectedOption);
                }
              }
              else {
                // do not set defaults to file type fields
                if ($('#' + ele).attr('type') != 'file') {
                  $('#' + ele ).val(data[ele].value).change();
                }
              }
          }
        },
        error       : function(XMLHttpRequest, textStatus, errorThrown) {
          CRM.console('error', "HTTP error status: ", textStatus);
        }
      });
    }
  });

</script>
{/literal}
