{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-uf-field-form-block">
{if $action eq 8}
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    {ts}WARNING: Deleting this profile field will remove it from Profile forms and listings. If this field is used in any 'stand-alone' Profile forms, you will need to update those forms to remove this field.{/ts} {ts}Do you want to continue?{/ts}
  </div>
{else}
  <table class="form-layout-compressed">
    <tr class="crm-uf-field-form-block-field_name">
      <td class="label">{$form.field_name.label} {help id='field_name'}</td>
      <td>{$form.field_name.html nofilter}<br />
        <span class="description">&nbsp;{ts}Select the type of CiviCRM record and the field you want to include in this Profile.{/ts}</span></td>
    </tr>
    <tr class="crm-uf-field-form-block-label">
      <td class="label">{$form.label.label} {help id='label'}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_uf_field' field='label' id=$fieldId}{/if}</td>
      <td>{$form.label.html}</td>
    </tr>
    <tr class="crm-uf-field-form-block-is_multi">
      <td class="label">{$form.is_multi_summary.label}{help id='is_multi_summary'}</td>
      <td>{$form.is_multi_summary.html}<br />
    </tr>
    <tr class="crm-uf-field-form-block-is_required">
      <td class="label">{$form.is_required.label} {help id='is_required'}</td>
      <td>{$form.is_required.html}</td>
    </tr>
    <tr class="crm-uf-field-form-block-is_view">
      <td class="label">{$form.is_view.label} {help id='is_view'}</td>
      <td>{$form.is_view.html}</td>
    </tr>
    {if $legacyprofiles}
      <tr  id="profile_visibility" class="crm-uf-field-form-block-visibility">
        <td class="label">{$form.visibility.label} {help id='visibility'}</td>
        <td>{$form.visibility.html}</td>
      </tr>
      <tr class="crm-uf-field-form-block-is_searchable">
        <td class="label"><div id="is_search_label">{$form.is_searchable.label} {help id='is_searchable'}</div></td>
        <td><div id="is_search_html">{$form.is_searchable.html}</td>
      </tr>
      <tr class="crm-uf-field-form-block-in_selector">
        <td class="label"><div id="in_selector_label">{$form.in_selector.label}{help id='in_selector'}</div></td>
        <td><div id="in_selector_html">{$form.in_selector.html}</div></td>
      </tr>
    {/if}
    <tr class="crm-uf-field-form-block-help_pre">
      <td class="label">{$form.help_pre.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_uf_field' field='help_pre' id=$fieldId}{/if} {help id='help_pre'}</td>
      <td>{$form.help_pre.html}</td>
    </tr>
    <tr class="crm-uf-field-form-block-help_post">
      <td class="label">{$form.help_post.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_uf_field' field='help_post' id=$fieldId}{/if} {help id='help_pre' title=$form.help_post.textLabel}</td>
      <td>{$form.help_post.html}</td>
    </tr>
    <tr class="crm-uf-field-form-block-weight">
      <td class="label">{$form.weight.label} {help id='weight'}</td>
      <td>&nbsp;{$form.weight.html}</td>
    </tr>
    <tr class="crm-uf-field-form-block-is_active">
      <td class="label">{$form.is_active.label} {help id='is_active'}</td>
      <td>{$form.is_active.html}</td>
    </tr>
  </table>
{/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{$initHideBoxes nofilter}

{literal}
<script type="text/javascript">

CRM.$(function($) {
  var otherModule = {/literal}{$otherModules|@json_encode nofilter}{literal};
  if ( $.inArray( "Profile", otherModule ) > -1 && $.inArray( "Search Profile", otherModule ) == -1 ){
    $('#profile_visibility').show();
  }
  else if( $.inArray( "Search Profile", otherModule ) > -1 ){
    $('#profile_visibility').show();
    $("#in_selector").prop('checked',true);
  }
  else if( $.inArray( "Profile", otherModule ) == -1 && $.inArray( "Search Profile", otherModule ) == -1 ){
    $('#profile_visibility').hide();
  }
  $('[id^=field_name]').change(function() {
    showLabel();
    mixProfile();
  });
});

var preHelpLabel = "";
function showLabel( ) {
  if (preHelpLabel) {
    cj(".crm-uf-field-form-block-help_pre .label").html(preHelpLabel);
  }
  var $elements = cj(".crm-uf-field-form-block-is_view, .crm-uf-field-form-block-is_required, .crm-uf-field-form-block-visibility, .crm-uf-field-form-block-is_searchable, .crm-uf-field-form-block-in_selector, .crm-uf-field-form-block-help_post");

  $elements.show();

  if (cj('[name="field_name[0]"]').val() == "Formatting") {
    if (!preHelpLabel) {
      preHelpLabel = cj(".crm-uf-field-form-block-help_post .label").html();
    }
    cj(".crm-uf-field-form-block-help_pre .label").html('<label for="help_pre">HTML Code</label>');
    $elements.hide();
  }

  // Set the Field Label
  var labelValue = '';
  if (cj('[name="field_name[0]"]').val()) {
    var fieldId = cj('[name="field_name[1]"]').val();
    if (fieldId) {
      labelValue = cj('[name="field_name[1]"] :selected').text().split(' :: ', 2)[0];
      if (cj('[name="field_name[3]"]').val()) {
        labelValue += '-' + cj('[name="field_name[3]"] :selected').text();
      }
      if (cj('[name="field_name[2]"]').val()) {
        labelValue += ' (' + cj('[name="field_name[2]"] :selected').text() + ')';
      }
    }
  }

  cj('#label').val(labelValue);

  /* Code to hide searchable attribute for no searchable fields */
  if (document.getElementsByName("field_name[1]")[0].selectedIndex == -1) {
    return;
  }
  var field2 = document.getElementsByName("field_name[1]")[0][document.getElementsByName("field_name[1]")[0].selectedIndex].text;
  {/literal}
  {foreach from=$noSearchable key=dnc item=val}
  {literal}
    if (field2 == "{/literal}{$val}{literal}") {
      cj('#is_search_label, #is_search_html').hide();
    }
  {/literal}
  {/foreach}
  {literal}

}

{/literal}{if $action neq 8}{literal}
showHideSelectorSearch();

function showHideSelectorSearch() {
  var is_search = cj('#is_search_label, #is_search_html');
  var in_selector = cj('#in_selector_label, #in_selector_html');
  if (cj("#visibility").val() == "User and User Admin Only") {
    is_search.hide();
    in_selector.hide();
    cj("#is_searchable").prop('checked',false);
  }
  else {
    if (!cj("#is_view").prop('checked')) {
      is_search.show();
    }
    var fldName = cj("#field_name_1").val();
    if (fldName == 'group' || fldName == 'tag') {
      in_selector.hide();
    }
    else {
      in_selector.show();
    }
  }
}

cj("#field_name_1").bind( 'change blur', function( ) {
  showHideSelectorSearch( );
});

CRM.$(function($) {
  cj("#field_name_1").addClass( 'huge' );
  viewOnlyShowHide( );
  cj("#is_view").click( function(){
    viewOnlyShowHide();
  });
});
{/literal}{/if}{literal}

CRM.$(function($) {
  $("#field_name_1").change(handleCustomField).each(handleCustomField);

  function hideMultiSummary() {
    $('#is_multi_summary').prop('checked', false);
    $('.crm-uf-field-form-block-is_multi').hide();
  }

  function handleCustomField() {
    const fieldName = $(this).val();
    if (fieldName && fieldName.match(/^custom_[\d]/)) {
      const customFieldId = fieldName.split('_')[1];

      CRM.api4('CustomField', 'get', {
        select: ['help_pre', 'help_post', 'custom_group_id.is_multiple'],
        where: [['id', '=', customFieldId]]
      }, 0).then(function(result) {
        if (result && result.help_pre) {
          $('#help_pre').val(result.help_pre);
        }
        if (result && result.help_post) {
          $('#help_post').val(result.help_post);
        }
        if (result && result['custom_group_id.is_multiple']) {
          $('.crm-uf-field-form-block-is_multi').show();
        } else {
          hideMultiSummary()
        }
      });
    } else {
      hideMultiSummary();
    }
  }
});

function viewOnlyShowHide() {
  var is_search = cj('#is_search_label, #is_search_html');
  if (cj("#is_view").prop('checked')) {
    is_search.hide();
    cj("#is_searchable").prop('checked', false);
  }
  else if (cj("#visibility").val() != "User and User Admin Only")  {
    is_search.show();
  }
}

//CRM-4363
function mixProfile( ) {
  var allMixTypes = ["Participant", "Membership", "Contribution"];
  var type = document.forms.Field['field_name[0]'].value;
  var alreadyMixProfile = {/literal}{if $alreadyMixProfile}true{else}false{/if}{literal};
  if (allMixTypes.indexOf( type ) != -1 || alreadyMixProfile) {
    if (document.getElementById("is_searchable").checked) {
      document.getElementById("is_searchable").checked = false;
      if ( alreadyMixProfile ) {
        var message = {/literal}'{ts escape="js"}You can not mark fields as Searchable in a profile that contains fields for multiple record types.{/ts}'{literal};
      }
      else {
        var message = type + {/literal}'{ts escape="js"} fields can not be marked as Searchable in a profile.{/ts}'{literal};
      }
      cj().crmError(message, {/literal}'{ts escape="js"}Error{/ts}'{literal});
    }
    if ( document.getElementById("in_selector").checked ) {
      document.getElementById("in_selector").checked = false;
      if ( alreadyMixProfile ) {
        var message = {/literal}'{ts escape="js"}You can not mark a field as a Result Column in a profile that contains fields from multiple record types.{/ts}'{literal};
      }
      else {
        var message = type + {/literal}'{ts escape="js"} can not be used as a Result Column for profile searches.{/ts}'{literal};
      }
      cj().crmError(message, {/literal}'{ts escape="js"}Error{/ts}'{literal});
    }
  }
}

function verify( ) {
  var allMixTypes = ["Participant", "Membership", "Contribution"];
  var type = document.forms.Field['field_name[0]'].value;
  if ( allMixTypes.indexOf( type ) != -1 ) {
    var message = {/literal}'{ts escape='js'}Oops. One or more fields in this profile are configured to be Searchable and / or shown in a Results Column, AND you are trying to add a {/ts}'
    + type + '{ts} field. Profiles with a mixture of field types can not include Searchable or Results Column fields. If you save this field now, the Seachable and Results Column settings will be removed for all fields in this profile. Do you want to continue?{/ts}'{literal};
    var ok = confirm( message );
    if ( !ok ) {
      return false;
    }
  }
}

</script>
{/literal}
