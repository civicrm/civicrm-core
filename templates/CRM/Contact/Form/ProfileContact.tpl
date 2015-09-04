{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
{**
 * This file provides the HTML for the on-behalf-of form.
 * Also used for related contact edit form.
 * FIXME: This is way more complex than it needs to be
 * FIXME: Why are we not just using the dynamic form tpl to display this profile?
 *}
{if $form.is_for_organization}
  <div class="crm-section {$form.is_for_organization.name}-section">
    <div class="label">&nbsp;</div>
    <div class="content">
      {$form.is_for_organization.html}&nbsp;{$form.is_for_organization.label}
    </div>
    <div class="clear"></div>
  </div>
{/if}

<div id="on-behalf-block">
  {crmRegion name="onbehalf-block"}
    {if $onBehalfOfFields|@count}
      <fieldset>
      <legend>{$fieldSetTitle}</legend>
      {if $form.org_option}
        <div id='orgOptions' class="section crm-section">
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
    var snippet = {/literal}"{$snippet}"{literal};

    $("#orgOptions").show( );
    var orgOption = $("input:radio[name=org_option]:checked").val( ); 
    selectCreateOrg(orgOption, false);

    function resetValues() {
     // Don't trip chain-select when clearing values
     $('.crm-chain-select-control', "#select_org div").select2('val', '');
     $('input[type=text], select, textarea', "#select_org div").not('.crm-chain-select-control, #onbehalfof_id').val('').change();
     $('input[type=radio], input[type=checkbox]', "#select_org div").prop('checked', false).change();
    }

    function buildOnBehalfProfile(type) {
      if (type == 0) {
        $("#on-behalf-block").html('');
        return;
      }
      {/literal}{if $onBehalfprofileId}
        {capture assign='onBehalfprofileId'}id={$onBehalfprofileId}&{/capture};
        var dataUrl = "{crmURL p='civicrm/profileContact/form' h=0 q="`$onBehalfprofileId`prefix=onbehalf"}";
      {/if}{literal}
      if (typeof dataUrl != 'undefined') {CRM.loadPage(dataUrl, {target: '#on-behalf-block'})};
    }

    if ($('#is_for_organization').length) {
      $('#is_for_organization').on('change', function() {
         var type = $(this).is(':checked') ? 1 : 0;
         buildOnBehalfProfile(type);
      });
    }
    else if (!snippet) {
      var type = {/literal}"{$onBehalfRequired}"{literal}; 
      buildOnBehalfProfile(type);
    }

   $("input:radio[name='org_option']").click( function( ) {
     var orgOption = $(this).val();
     selectCreateOrg(orgOption, true);
   });

   $('#onbehalfof_id').change(function() {
    setLocationDetails($(this).val());
   }).change();

   function selectCreateOrg( orgOption, reset ) {
    if (orgOption == 0) {
      $("#onbehalfof_id").show().change();
      $("input#onbehalf_organization_name").hide();
    }
    else if ( orgOption == 1 ) {
      $("input#onbehalf_organization_name").show();
      $("#onbehalfof_id").hide();
    }

    if ( reset ) {
      resetValues();
    }
  }

function setLocationDetails(contactID , reset) {
  var submittedCID = {/literal}"{$submittedOnBehalf}"{literal};
  var submittedOnBehalfInfo = {/literal}'{$submittedOnBehalfInfo}'{literal};
  if (submittedOnBehalfInfo) {
    submittedOnBehalfInfo = $.parseJSON(submittedOnBehalfInfo);

    if (submittedCID == contactID) {
      $.each(submittedOnBehalfInfo, function(key, value) {
        $('#onbehalf_' + key ).val(value);
      });
      return;
    }
  }

  resetValues();
  var locationUrl = {/literal}"{$locDataURL}"{literal} + contactID + "&ufId=" + {/literal}"{$profileId}"{literal};
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
