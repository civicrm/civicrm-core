{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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

{if $buildOnBehalfForm or $onBehalfRequired}
  {if !empty($context)}
    <fieldset id="for_organization" class="for_organization-group">
    <legend>{$fieldSetTitle}</legend>
    {if ( $relatedOrganizationFound or $onBehalfRequired ) and !$organizationName and $form.org_option.html}
      <div id='orgOptions' class="section crm-section">
        <div class="content">
          {$form.org_option.html}
        </div>
      </div>
    {/if}
  {/if}
  <div id='onBehalfOfOrg' class="crm-section">
    {include file="CRM/UF/Form/Block.tpl" fields=$onBehalfOfFields mode=8 prefix='onbehalf'}
  </div>

  <div>{$form.mode.html}</div>
  {if !empty($context)}
    </fieldset>
  {/if}
{/if}
{literal}
<script type="text/javascript">

  showOnBehalf({/literal}"{$onBehalfRequired}"{literal});

  cj( "#mode" ).hide( );
  cj( "#mode" ).prop('checked', true );
  if ( cj( "#mode" ).prop('checked' ) && !{/literal}"{$reset}"{literal} ) {
    $text = ' {/literal}{ts escape="js"}Use existing organization{/ts}{literal} ';
    cj( "#createNewOrg" ).text( $text );
    cj( "#mode" ).prop('checked', false );
  }

function showOnBehalf(onBehalfRequired) {
  if ( cj( "#is_for_organization" ).prop( 'checked' ) || onBehalfRequired ) {
    var urlPath = {/literal}"{crmURL p=$urlPath h=0 q="snippet=4&onbehalf=1&id=$contributionPageID&qfKey=$qfKey"}";
    {if $mode eq 'test'}
      urlPath += '&action=preview';
    {/if}
    {if $reset}
      urlPath += '&reset={$reset}';
    {/if}{literal}
    cj("#onBehalfOfOrg").show();
    if (cj("fieldset", '#onBehalfOfOrg').length < 1) {
      cj('#onBehalfOfOrg').load(urlPath);
    }
  }
  else {
    cj("#onBehalfOfOrg").hide();
  }
}

function resetValues() {
  // Don't trip chain-select when clearing values
  cj('.crm-chain-select-control', "#select_org div").select2('val', '');
  cj('input[type=text], select, textarea', "#select_org div").not('.crm-chain-select-control, #onbehalfof_id').val('').change();
  cj('input[type=radio], input[type=checkbox]', "#select_org div").prop('checked', false).change();
}

function createNew( ) {
  if (cj("#mode").prop('checked')) {
    var textMessage = ' {/literal}{ts escape="js"}Use existing organization{/ts}{literal} ';
    cj("#onbehalf_organization_name").prop('readonly', false);
    cj("#mode").prop('checked', false);
    resetValues();
  }
  else {
    var textMessage = ' {/literal}{ts escape="js"}Enter a new organization{/ts}{literal} ';
    cj("#mode").prop('checked', true);
    setOrgName( );
  }
  cj("#createNewOrg").text(textMessage);
}

function setOrgName( ) {
  var orgName = "{/literal}{$organizationName}{literal}";
  var orgId   = "{/literal}{$orgId}{literal}";
  cj("#onbehalf_organization_name").val(orgName);
  cj("#onbehalf_organization_name").attr('readonly', true);
  setLocationDetails(orgId);
}


function setLocationDetails(contactID , reset) {
  var submittedCID = {/literal}"{$submittedOnBehalf}"{literal};
  var submittedOnBehalfInfo = {/literal}'{$submittedOnBehalfInfo}'{literal};
  if (submittedOnBehalfInfo) {
    submittedOnBehalfInfo = cj.parseJSON(submittedOnBehalfInfo);

    if (submittedCID == contactID) {
      cj.each(submittedOnBehalfInfo, function(key, value) {
        cj('#onbehalf_' + key ).val(value);
      });
      return;
    }
  }

  resetValues();
  var locationUrl = {/literal}"{$locDataURL}"{literal} + contactID + "&ufId=" + {/literal}"{$profileId}"{literal};
  cj.ajax({
    url         : locationUrl,
    dataType    : "json",
    timeout     : 5000, //Time in milliseconds
    success     : function(data, status) {
      for (var ele in data) {
        if (cj("#"+ ele).hasClass('crm-chain-select-target')) {
          cj("#"+ ele).data('newVal', data[ele].value).off('.autofill').on('crmOptionsUpdated.autofill', function() {
            cj(this).off('.autofill').val(cj(this).data('newVal')).change();
          });
        }
        else if (cj('#' + ele).data('select2')) {
          cj('#' + ele).select2('val', data[ele].value);
        }
        if (data[ele].type == 'Radio') {
          if (data[ele].value) {
            var fldName = ele.replace('onbehalf_', '');
            cj("input[name='onbehalf["+ fldName +"]']").filter("[value='" + data[ele].value + "']").prop('checked', true);
          }
        }
        else if (data[ele].type == 'CheckBox') {
          for (var selectedOption in data[ele].value) {
            var fldName = ele.replace('onbehalf_', '');
            cj("input[name='onbehalf["+ fldName+"]["+ selectedOption +"]']").prop('checked','checked');
          }
        }
        else if (data[ele].type == 'AdvMulti-Select') {
          var customFld = ele.replace('onbehalf_', '');
          // remove empty value if any
          cj('#onbehalf\\['+ customFld +'\\]-f option[value=""]').remove();
          cj('#onbehalf\\['+ customFld +'\\]-t option[value=""]').remove();

          for (var selectedOption in data[ele].value) {
            // remove selected values from left and selected values to right
            cj('#onbehalf\\['+ customFld +'\\]-f option[value="' + selectedOption + '"]').remove()
              .appendTo('#onbehalf\\['+ customFld +'\\]-t');
            cj('#onbehalf_'+ customFld).val(selectedOption);
          }
        }
        else {
          // do not set defaults to file type fields
          if (cj('#' + ele).attr('type') != 'file') {
            cj('#' + ele ).val(data[ele].value).change();
          }
        }
      }
    },
    error       : function(XMLHttpRequest, textStatus, errorThrown) {
      CRM.console('error', "HTTP error status: ", textStatus);
    }
  });
}

cj("input:radio[name='org_option']").click( function( ) {
  var orgOption = cj(this).val();
  selectCreateOrg(orgOption, true);
});

cj('#onbehalfof_id').change(function() {
  setLocationDetails(cj(this).val());
}).change();

function selectCreateOrg( orgOption, reset ) {
  if (orgOption == 0) {
    cj("#onbehalfof_id").show().change();
    cj("input#onbehalf_organization_name").hide();
  }
  else if ( orgOption == 1 ) {
    cj("input#onbehalf_organization_name").show();
    cj("#onbehalfof_id").hide();
  }

  if ( reset ) {
    resetValues();
  }
}

{/literal}
{if ($relatedOrganizationFound or $onBehalfRequired) and $reset and $organizationName}
  setOrgName( );
{else}
  cj("#orgOptions").show( );
  var orgOption = cj("input:radio[name=org_option]:checked").val( );
  selectCreateOrg(orgOption, false);
{/if}

{* If mid present in the url, take the required action (poping up related existing contact ..etc) *}
{if $membershipContactID}
{literal}
  CRM.$(function($) {
    $('#organization_id').val("{/literal}{$membershipContactName}{literal}");
    $('#organization_name').val("{/literal}{$membershipContactName}{literal}");
    $('#onbehalfof_id').val("{/literal}{$membershipContactID}{literal}");
    setLocationDetails( "{/literal}{$membershipContactID}{literal}" );
  });
{/literal}
{/if}

</script>
