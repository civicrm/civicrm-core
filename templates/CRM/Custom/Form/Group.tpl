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
{* add/update/view custom data group *}
<div class="help">{ts}Use Custom Field Sets to add logically related fields for a specific type of CiviCRM record (e.g. contact records, contribution records, etc.).{/ts} {help id="id-group_intro"}</div>
<div class="crm-block crm-form-block">
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    <table class="form-layout">
    <tr>
        <td class="label">{$form.title.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_custom_group' field='title' id=$gid}{/if}</td>
        <td class="html-adjust">{$form.title.html} {help id="id-title"}</td>
    </tr>
    <tr>
        <td class="label">{$form.extends.label}</td>
        <td>{$form.extends.html} {help id="id-extends"}</td>
    </tr>
    <tr>
        <td class="label">{$form.weight.label}</td>
        <td>{$form.weight.html} {help id="id-weight"}</td>
    </tr>
    <tr id="is_multiple_row" class="hiddenElement"> {* This section shown only when Used For = Contact, Individ, Org or Household. *}
        <td></td>
        <td class="html-adjust">{$form.is_multiple.html}&nbsp;{$form.is_multiple.label} {help id="id-is_multiple"}</td>
    </tr>
    <tr id="multiple_row" class="hiddenElement">
        <td class="label">{$form.max_multiple.label}</td>
        <td>{$form.max_multiple.html} {help id="id-max_multiple"}</td>
    </tr>
    <tr id="style_row" class="hiddenElement">
        <td class="label">{$form.style.label}</td>
        <td>{$form.style.html} {help id="id-display_style"}</td>
    </tr>
    <tr class="html-adjust">
        <td>&nbsp;</td>
        <td>{$form.collapse_display.html} {$form.collapse_display.label} {help id="id-collapse"}</td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td>{$form.collapse_adv_display.html} {$form.collapse_adv_display.label} {help id="id-collapse-adv"}</td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td>{$form.is_active.html} {$form.is_active.label}</td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td>{$form.is_public.html} {$form.is_public.label}</td>
    </tr>
    <tr class="html-adjust">
        <td class="label">{$form.help_pre.label} <!--{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_custom_group' field='help_pre' id=$gid}{/if}-->{help id="id-help_pre"}</td>
        <td>{$form.help_pre.html}</td>
    </tr>
    <tr class="html-adjust">
        <td class="label">{$form.help_post.label} <!--{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_custom_group' field='help_post' id=$gid}{/if}-->{help id="id-help_post"}</td>
        <td>{$form.help_post.html}</td>
    </tr>
    </table>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{if $action eq 2 or $action eq 4} {* Update or View*}
    <p></p>
    <div class="action-link">
      {crmButton p='civicrm/admin/custom/group/field' q="action=browse&reset=1&gid=$gid" icon="th-list"}{ts}Custom Fields for this Set{/ts}{/crmButton}
    </div>
{/if}
{$initHideBlocks}
{literal}
<script type="text/Javascript">
CRM.$(function($) {
  var tabWithTableOption;

  $('#extends_0').each(showHideStyle).change(showHideStyle);

  var isGroupEmpty = {/literal}{$isGroupEmpty|@json_encode}{literal};
  if (isGroupEmpty) {
    showRange(true);
  }
  $('input#is_multiple').change(showRange);

  // "Collapse" is a bad default for "Tab" display
  $("select#style").change(function() {
    if ($(this).val() == 'Tab') {
      $('#collapse_display').prop('checked', false);
    }
  });

  /**
   * Check if this is a contact-related set and show/hide other options accordingly
   */
  function showHideStyle() {
    var
      extend = $(this).val(),
      contactTypes = {/literal}{$contactTypes}{literal},
      showStyle = "{/literal}{$showStyle}{literal}",
      showMultiple = "{/literal}{$showMultiple}{literal}",
      showMaxMultiple = "{/literal}{$showMaxMultiple}{literal}",
      isContact = ($.inArray(extend, contactTypes) >= 0);

    if (isContact) {
      $("tr#style_row, tr#is_multiple_row").show();
      if ($('#is_multiple :checked').length) {
        $("tr#multiple_row").show();
      }
    }
    else {
      $("tr#style_row, tr#is_multiple_row, tr#multiple_row").hide();
    }

    if (showStyle) {
      $("tr#style_row").show();
    }

    if (showMultiple) {
      $("tr#style_row, tr#is_multiple_row").show();
    }

    if (!showMaxMultiple) {
      $("tr#multiple_row").hide();
    }
    else if ($('#is_multiple').prop('checked')) {
      $("tr#multiple_row").show();
    }
  }

  /**
   * Check if this set supports multiple records and adjust other options accordingly
   *
   * @param onFormLoad
   */
  function showRange(onFormLoad) {
    if($("#is_multiple").is(':checked')) {
      $("tr#multiple_row").show();
      if (onFormLoad !== true) {
        $('#collapse_display').prop('checked', false);
        $("select#style").append(tabWithTableOption);
        $("select#style").val('Tab with table');
      }
    }
    else {
      $("tr#multiple_row").hide();
      if ($("select#style").val() === 'Tab with table') {
        $("select#style").val('Inline');
      }
      tabWithTableOption = $("select#style option[value='Tab with table']").detach();
    }
  }

  // In update mode, when 'extends' is set to an option which doesn't have
  // any options in 2nd selector (for subtypes)
  var subtypes = document.getElementById('extends_1');
  if (subtypes) {
    if (subtypes.options.length <= 0) {
      subtypes.style.display = 'none';
    }
    else {
      subtypes.style.display = 'inline';
    }
  }

  // When removing sub-types
  $('.crm-warnDataLoss').on('click', function() {
    var submittedSubtypes = $('#extends_1').val();
    var defaultSubtypes = {/literal}{$defaultSubtypes}{literal};

    var warning = false;
    $.each(defaultSubtypes, function(index, subtype) {
      if ($.inArray(subtype, submittedSubtypes) < 0) {
        warning = true;
      }
    });

    if (warning) {
      return confirm({/literal}'{ts escape='js'}Warning: You have chosen to remove one or more subtypes. This will cause any custom data records associated with those subtypes to be removed as long as the contact does not have a contact subtype still selected.{/ts}'{literal});
    }
    return true;
  });
});
</script>
{/literal}
