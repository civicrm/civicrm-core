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
{**
 * This file provides the HTML for the on-behalf-of form. 
 * Also used for related contact edit form.
 * FIXME: This is way more complex than it needs to be
 * FIXME: Why are we not just using the dynamic form tpl to display this profile?
 *}

{if $buildOnBehalfForm or $onBehalfRequired}
<fieldset id="for_organization" class="for_organization-group">
<legend>{$fieldSetTitle}</legend>
  {if ( $relatedOrganizationFound or $onBehalfRequired ) and !$organizationName}
    <div id='orgOptions' class="section crm-section">
      <div class="content">
        {$form.org_option.html}
      </div>
    </div>
  {/if}

<div id="select_org" class="crm-section">
  {foreach from=$onBehalfOfFields item=onBehaldField key=fieldName}
    {if $onBehalfOfFields.$fieldName.skipDisplay}
      {continue}
    {/if}
    {if $onBehalfOfFields.$fieldName.field_type eq "Formatting"}
      {$onBehalfOfFields.$fieldName.help_pre}
      {continue}
    {/if}
    <div class="crm-section {$onBehalfOfFields.$fieldName.name}-section">
      {if $onBehalfOfFields.$fieldName.help_pre}
        &nbsp;&nbsp;<span class='description'>{$onBehalfOfFields.$fieldName.help_pre}</span>
      {/if}

      {if ( $fieldName eq 'organization_name' ) and $organizationName}
        <div id='org_name' class="label">{$form.onbehalf.$fieldName.label}</div>
        <div class="content">
          {$form.onbehalf.$fieldName.html|crmAddClass:big}
          <span>
              ( <a id='createNewOrg' href="#" onclick="createNew( ); return false;">{ts}Enter a new organization{/ts}</a> )
          </span>
          <div id="id-onbehalf-orgname-enter-help" class="description">
            {ts}Organization details have been prefilled for you. If this is not the organization you want to use, click "Enter a new organization" above.{/ts}
          </div>
          {if $onBehalfOfFields.$fieldName.help_post}
            <span class='description'>{$onBehalfOfFields.$fieldName.help_post}</span>
          {/if}
        </div>
      {else}
        {if $onBehalfOfFields.$fieldName.options_per_line}
          <div class="label option-label">{$form.onbehalf.$fieldName.label}</div>
          <div class="content">
            {assign var="count" value="1"}
            {strip}
              <table class="form-layout-compressed">
              <tr>
              {* sort by fails for option per line. Added a variable to iterate through the element array*}
                {assign var="index" value="1"}
                {foreach name=outer key=key item=item from=$form.onbehalf.$fieldName}
                  {if $index < 10}
                    {assign var="index" value=`$index+1`}
                  {else}
                    <td class="labels font-light">{$form.onbehalf.$fieldName.$key.html}</td>
                    {if $count == $onBehalfOfFields.$fieldName.options_per_line}
                      </tr>
                      <tr>
                      {assign var="count" value="1"}
                    {else}
                      {assign var="count" value=`$count+1`}
                    {/if}
                  {/if}
                {/foreach}
              </tr>
              </table>
            {/strip}
            {if $onBehalfOfFields.$fieldName.help_post}
              <span class='description'>{$onBehalfOfFields.$fieldName.help_post}</span>
            {/if}
          </div>
        {else}
          <div class="label">{$form.onbehalf.$fieldName.label}</div>
          <div class="content">
            {if $fieldName eq 'organization_name' and !empty($form.onbehalfof_id)}
              {$form.onbehalfof_id.html}
            {/if}
            {$form.onbehalf.$fieldName.html}
            {if !empty($onBehalfOfFields.$fieldName.html_type)  && $onBehalfOfFields.$fieldName.html_type eq 'Autocomplete-Select'}
              {assign var=elementName value=onbehalf[$fieldName]}
            {include file="CRM/Custom/Form/AutoComplete.tpl" element_name=$elementName}
            {/if}
            {if $onBehalfOfFields.$fieldName.name|substr:0:5 eq 'phone'}
              {assign var="phone_ext_field" value=$onBehalfOfFields.$fieldName.name|replace:'phone':'phone_ext'}
              {if $form.onbehalf.$phone_ext_field.html}
                &nbsp;{$form.onbehalf.$phone_ext_field.html}
              {/if}
            {/if} 
	    {if $onBehalfOfFields.$fieldName.data_type eq 'Date'}
            {assign var=elementName value=onbehalf[$fieldName]}
	       {include file="CRM/common/jcalendar.tpl" elementName=$elementName elementId=onbehalf_$fieldName}
            {/if}
            {if $onBehalfOfFields.$fieldName.help_post}
              <br /><span class='description'>{$onBehalfOfFields.$fieldName.help_post}</span>
            {/if}
          </div>
        {/if}
      {/if}
      <div class="clear"></div>
    </div>
  {/foreach}
</div>
<div>{$form.mode.html}</div>
</fieldset>
{/if}
{if empty($snippet)}
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
        else if (data[ele].type == 'Multi-Select') {
          for (var selectedOption in data[ele].value) {
            cj('#' + ele + " option[value='" + selectedOption + "']").prop('selected', true);
          }
        }
        else if (data[ele].type == 'Autocomplete-Select') {
          cj('#' + ele ).val( data[ele].value );
          cj('#' + ele + '_id').val(data[ele].id);
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
{/if}
