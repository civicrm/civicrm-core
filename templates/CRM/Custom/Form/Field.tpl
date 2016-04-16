{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
{*Javascript function controls showing and hiding of form elements based on html type.*}
{literal}
<script type="text/Javascript">
function custom_option_html_type( ) {
    var html_type_name = document.getElementsByName("data_type[1]")[0].value;
    var data_type_id   = document.getElementsByName("data_type[0]")[0].value;

    if ( !html_type_name && !data_type_id ) {
        return;
    }

    if ( data_type_id == 11) {
      toggleContactRefFilter( );
    } else {
      cj('#field_advance_filter').hide();
      cj('#contact_reference_group').hide();
    }

    if ( data_type_id < 4 ) {
        if ( html_type_name != "Text" ) {
            cj("#showoption").show();
            cj("#hideDefault").hide();
            cj("#hideDesc").hide();
            cj("#searchByRange").hide();
            cj("#searchable").show();
        } else {
            cj("#showoption").hide();
            cj("#hideDefault").show();
            cj("#hideDesc").show();
            cj("#searchable").show();
        }
    } else {
        if ( data_type_id == 9 ) {
            document.getElementById("default_value").value = '';
            cj("#hideDefault").hide();
            cj("#searchable").hide();
            cj("#hideDesc").hide();
        } else if ( data_type_id == 11 ) {
            cj("#hideDefault").hide();
        } else {
            cj("#hideDefault").show();
            cj("#searchable").show();
            cj("#hideDesc").show();
        }
        cj("#showoption").hide();
    }

    var radioOption, checkBoxOption;

    for ( var i=1; i<=11; i++) {
        radioOption = 'radio'+i;
        checkBoxOption = 'checkbox'+i
        if ( data_type_id < 4 ) {
            if ( html_type_name != "Text") {
                if ( html_type_name == "CheckBox" || html_type_name == "Multi-Select") {
                    cj("#"+checkBoxOption).show();
                    cj("#"+radioOption).hide();
                } else {
                    cj("#"+radioOption).show();
                    cj("#"+checkBoxOption).hide();
                }
            }
        }
    }

    if ( data_type_id < 4 ) {
        if (html_type_name == "CheckBox" || html_type_name == "Radio") {
            cj("#optionsPerLine").show();
        } else {
            cj("#optionsPerLine").hide();
        }
    }

    if ( data_type_id == 5) {
        cj("#startDateRange").show();
        cj("#endDateRange").show();
        cj("#includedDatePart").show();
    } else {
        cj("#startDateRange").hide();
        cj("#endDateRange").hide();
        cj("#includedDatePart").hide();
    }

    if ( data_type_id == 0 ) {
        cj("#textLength").show();
    } else {
        cj("#textLength").hide();
    }

    if ( data_type_id == 4 ) {
        cj("#noteColumns").show();
        cj("#noteRows").show();
        cj("#noteLength").show();
    } else {
        cj("#noteColumns").hide();
        cj("#noteRows").hide();
        cj("#noteLength").hide();
    }

    if ( data_type_id > 3) {
        cj("#optionsPerLine").hide();
    }

    {/literal}{if $action eq 1}{literal}
    clearSearchBoxes( );
    {/literal}{/if}{literal}
}
</script>
{/literal}
<div class="crm-block crm-form-block crm-custom-field-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    <table class="form-layout">
        <tr class="crm-custom-field-form-block-label">
            <td class="label">{$form.label.label}
            {if $action == 2}
                {include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_custom_field' field='label' id=$id}
            {/if}
            </td>
            <td class="html-adjust">{$form.label.html}</td>
        </tr>
        <tr class="crm-custom-field-form-block-data_type">
            <td class="label">{$form.data_type.label}</td>
            <td class="html-adjust">{$form.data_type.html}
                {if $action neq 4 and $action neq 2}
                    <br /><span class="description">{ts}Select the type of data you want to collect and store for this contact. Then select from the available HTML input field types (choices are based on the type of data being collected).{/ts}</span>
                {/if}
    {if $action eq 2 and $changeFieldType}
    <br />
    <a class="action-item crm-hover-button" href='{crmURL p="civicrm/admin/custom/group/field/changetype" q="reset=1&id=`$id`"}'>
      <i class="crm-i fa-wrench"></i>
      {ts}Change Input Field Type{/ts}
    </a>
    <div class='clear'></div>
    {/if}
            </td>
        </tr>
       {if $form.in_selector}
       <tr class='crm-custom-field-form-block-in_selector'>
          <td class='label'>{$form.in_selector.label}</td>
          <td class='html-adjust'>{$form.in_selector.html} {help id="id-in_selector"}</td>
       </tr>
       {/if}
       <tr class="crm-custom-field-form-block-text_length"  id="textLength" {if !( $action eq 1 || $action eq 2 ) && ($form.data_type.value.0.0 != 0)}class="hiddenElement"{/if}>
            <td class="label">{$form.text_length.label}</td>
            <td class="html-adjust">{$form.text_length.html}</td>
        </tr>

        <tr id='showoption' {if $action eq 1 or $action eq 2 }class="hiddenElement"{/if}>
            <td colspan="2">
            <table class="form-layout-compressed">
                {* Conditionally show table for setting up selection options - for field types = radio, checkbox or select *}
                {include file="CRM/Custom/Form/Optionfields.tpl"}
            </table>
            </td>
        </tr>
        <tr id='contact_reference_group'>
            <td class="label">{$form.group_id.label}</td>
            <td class="html-adjust">
              {$form.group_id.html}
            &nbsp;&nbsp;<span><a href="#" onclick="toggleContactRefFilter('Advance'); return false;">{ts}Advanced Filter{/ts}</a></span>
              {capture assign=searchPreferences}{crmURL p="civicrm/admin/setting/search" q="reset=1"}{/capture}
            <div class="messages status no-popup"><i class="crm-i fa-exclamation-triangle"></i> {ts 1=$searchPreferences}If you are planning on using this field in front-end profile, event registration or contribution forms, you should 'Limit List to Group' or configure an 'Advanced Filter'  (so that you do not unintentionally expose your entire set of contacts). Users must have either 'access contact reference fields' OR 'access CiviCRM' permission in order to use contact reference autocomplete fields. You can assign 'access contact reference fields' to the anonymous role if you want un-authenticated visitors to use this field. Use <a href='%1'>Search Preferences - Contact Reference Options</a> to control the fields included in the search results.{/ts}
          </td>
        </tr>
      <tr id='field_advance_filter'>
            <td class="label">{$form.filter.label}</td>
            <td class="html-adjust">
              {$form.filter.html}
              &nbsp;&nbsp;<span><a href="#" onclick="toggleContactRefFilter('Group'); return false;">{ts}Filter by Group{/ts}</a></span>
        <br />
        <span class="description">{ts}Filter contact search results for this field using Contact get API parameters. EXAMPLE: To list Students in group 3:{/ts} "action=get&group=3&contact_sub_type=Student" {docURL page="Using the API" resource="wiki"}</span>
            </td>
        </tr>
        <tr  class="crm-custom-field-form-block-options_per_line" id="optionsPerLine" {if $action neq 2 && ($form.data_type.value.0.0 >= 4 && $form.data_type.value.1.0 neq 'CheckBox' || $form.data_type.value.1.0 neq 'Radio' )}class="hiddenElement"{/if}>
            <td class="label">{$form.options_per_line.label}</td>
            <td class="html-adjust">{$form.options_per_line.html|crmAddClass:two}</td>
        </tr>
      <tr  class="crm-custom-field-form-block-start_date_years" id="startDateRange" {if $action neq 2 && ($form.data_type.value.0.0 != 5)}class="hiddenElement"{/if}>
            <td class="label">{$form.start_date_years.label}</td>
            <td class="html-adjust">{$form.start_date_years.html} {ts}years prior to current date.{/ts}</td>
        </tr>
        <tr class="crm-custom-field-form-block-end_date_years" id="endDateRange" {if $action neq 2 && ($form.data_type.value.0.0 != 5)}class="hiddenElement"{/if}>
            <td class="label">{$form.end_date_years.label}</td>
            <td class="html-adjust">{$form.end_date_years.html} {ts}years after the current date.{/ts}</td>
        </tr>
        <tr  class="crm-custom-field-form-block-date_format"  id="includedDatePart" {if $action neq 2 && ($form.data_type.value.0.0 != 5)}class="hiddenElement"{/if}>
            <td class="label">{$form.date_format.label}</td>
            <td class="html-adjust">{$form.date_format.html}&nbsp;&nbsp;&nbsp;{$form.time_format.label}&nbsp;&nbsp;{$form.time_format.html}</td>
        </tr>
        <tr  class="crm-custom-field-form-block-note_rows"  id="noteRows" {if $action neq 2 && ($form.data_type.value.0.0 != 4)}class="hiddenElement"{/if}>
            <td class="label">{$form.note_rows.label}</td>
            <td class="html-adjust">{$form.note_rows.html}</td>
        </tr>
      <tr class="crm-custom-field-form-block-note_columns" id="noteColumns" {if $action neq 2 && ($form.data_type.value.0.0 != 4)}class="hiddenElement"{/if}>
            <td class="label">{$form.note_columns.label}</td>
            <td class="html-adjust">{$form.note_columns.html}</td>
        </tr>
        <tr class="crm-custom-field-form-block-note_length" id="noteLength" {if $action neq 2 && ($form.data_type.value.0.0 != 4)}class="hiddenElement"{/if}>
            <td class="label">{$form.note_length.label}</td>
            <td class="html-adjust">{$form.note_length.html} <span class="description">{ts}Leave blank for unlimited. This limit is not implemented by all browsers and rich text editors.{/ts}</span></td>
        </tr>
        <tr class="crm-custom-field-form-block-weight" >
            <td class="label">{$form.weight.label}</td>
            <td>{$form.weight.html|crmAddClass:two}
                {if $action neq 4}
                <span class="description">{ts}Weight controls the order in which fields are displayed in a group. Enter a positive or negative integer - lower numbers are displayed ahead of higher numbers.{/ts}</span>
                {/if}
            </td>
        </tr>
        <tr class="crm-custom-field-form-block-default_value" id="hideDefault" {if $action eq 2 && ($form.data_type.value.0.0 < 4 && $form.data_type.value.1.0 NEQ 'Text')}class="hiddenElement"{/if}>
            <td title="hideDefaultValTxt" class="label">{$form.default_value.label}</td>
            <td title="hideDefaultValDef" class="html-adjust">{$form.default_value.html}</td>
        </tr>
        <tr  class="crm-custom-field-form-block-description"  id="hideDesc" {if $action neq 4 && $action eq 2 && ($form.data_type.value.0.0 < 4 && $form.data_type.value.1.0 NEQ 'Text')}class="hiddenElement"{/if}>
            <td title="hideDescTxt" class="label">&nbsp;</td>
            <td title="hideDescDef" class="html-adjust"><span class="description">{ts}If you want to provide a default value for this field, enter it here. For date fields, format is YYYY-MM-DD.{/ts}</span></td>
        </tr>
        <tr class="crm-custom-field-form-block-help_pre">
            <td class="label">{$form.help_pre.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_custom_field' field='help_pre' id=$id}{/if}</td>
            <td class="html-adjust">{$form.help_pre.html|crmAddClass:huge}</td>
        </tr>
        <tr class="crm-custom-field-form-block-help_post">
            <td class="label">{$form.help_post.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_custom_field' field='help_post' id=$id}{/if}</td>
            <td class="html-adjust">{$form.help_post.html|crmAddClass:huge}
                {if $action neq 4}
                    <span class="description">{ts}Explanatory text displayed on back-end forms. Pre help is displayed inline on the form (above the field). Post help is displayed in a pop-up - users click the help balloon to view help text.{/ts}</span>
                {/if}
            </td>
        </tr>
        <tr class="crm-custom-field-form-block-is_required">
            <td class="label">{$form.is_required.label}</td>
            <td class="html-adjust">{$form.is_required.html}
            {if $action neq 4}
                <br /><span class="description">{ts}Do not make custom fields required unless you want to force all users to enter a value anytime they add or edit this type of record. You can always make the field required when used in a specific Profile form.{/ts}</span>
            {/if}
            </td>
        </tr>
        <tr id ="searchable" class="crm-custom-field-form-block-is_searchable">
            <td class="label">{$form.is_searchable.label}</td>
            <td class="html-adjust">{$form.is_searchable.html}
                {if $action neq 4}
                    <br /><span class="description">{ts}Can you search on this field in the Advanced and component search forms? Also determines whether you can include this field as a display column and / or filter in related detail reports.{/ts}</span>
                {/if}
            </td>
        </tr>
        <tr id="searchByRange" class="crm-custom-field-form-block-is_search_range">
      <td class="label">{$form.is_search_range.label}</td>
            <td class="html-adjust">{$form.is_search_range.html}</td>
        </tr>
        <tr class="crm-custom-field-form-block-is_active">
            <td class="label">{$form.is_active.label}</td>
            <td class="html-adjust">{$form.is_active.html}</td>
        </tr>
        <tr class="crm-custom-field-form-block-is_view">
            <td class="label">{$form.is_view.label}</td>
            <td class="html-adjust">{$form.is_view.html}
                <span class="description">{ts}Is this field set by PHP code (via a custom hook). This field will not be updated by CiviCRM.{/ts}</span>
            </td>
        </tr>
    </table>
         {if $action ne 4}
         <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
      {else}
         <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
      {/if} {* $action ne view *}
    </div>
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var $form = $('form.{/literal}{$form.formClass}{literal}'),
      deprecatedNotice;
    function deprecatedWidgets() {
      deprecatedNotice && deprecatedNotice.close && deprecatedNotice.close();
      switch ($('#data_type_1', $form).val()) {
        case 'AdvMulti-Select':
          deprecatedNotice = CRM.alert({/literal}'{ts escape="js"}The old "Advance Multi-Select" widget is being phased out and will be removed in a future version of CiviCRM. "Multi-Select" is the recommended substitute.{/ts}', '{ts escape="js"}Obsolete widget{/ts}'{literal}, 'alert', {expires: 0});
          break;
      }
    }
    $('#data_type_1', $form).each(deprecatedWidgets).change(deprecatedWidgets);
  });

    //when page is reload, build show hide boxes
    //as per data type and html type selected.
    custom_option_html_type( );

    function showSearchRange(chkbox) {
        var html_type = document.getElementsByName("data_type[1]")[0].value;
      var data_type = document.getElementsByName("data_type[0]")[0].value;

        if ( ((data_type == 1 || data_type == 2 || data_type == 3) && (html_type == "Text")) || data_type == 5) {
            if (chkbox.checked) {
            document.getElementsByName("is_search_range")[0].checked = true;
                cj("#searchByRange").show();
            } else {
                clearSearchBoxes( );
            }
        }
    }

    //should not clear search boxes for update mode.
    function clearSearchBoxes( ) {
      document.getElementsByName("is_searchable")[0].checked   = false;
      document.getElementsByName("is_search_range")[1].checked = true;
        cj("#searchByRange").hide();
    }

    function toggleContactRefFilter(setSelected) {
      if ( !setSelected ) {
        setSelected =  cj('#filter_selected').val();
      } else {
        cj('#filter_selected').val(setSelected);
      }
      if ( setSelected == 'Advance' ) {
        cj('#contact_reference_group').hide( );
        cj('#field_advance_filter').show( );
      } else {
        cj('#field_advance_filter').hide( );
        cj('#contact_reference_group').show( );
      }
    }
</script>
{/literal}
{* Give link to view/edit choice options if in edit mode and html_type is one of the multiple choice types *}
{if $action eq 2 AND ($form.data_type.value.1.0 eq 'CheckBox' OR ($form.data_type.value.1.0 eq 'Radio' AND $form.data_type.value.0.0 neq 6) OR $form.data_type.value.1.0 eq 'Select' OR ($form.data_type.value.1.0 eq 'Multi-Select' AND $dontShowLink neq 1 ) ) }
    <div class="action-link">
      {crmButton p="civicrm/admin/custom/group/field/option" q="reset=1&action=browse&fid=`$id`&gid=`$gid`" icon="pencil"}{ts}View / Edit Multiple Choice Options{/ts}{/crmButton}
    </div>
{/if}
