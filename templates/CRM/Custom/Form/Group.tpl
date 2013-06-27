{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
<div class="crm-block crm-form-block">
    <div id="help">{ts}Use Custom Field Sets to add logically related fields for a specific type of CiviCRM record (e.g. contact records, contribution records, etc.).{/ts} {help id="id-group_intro"}</div>
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
    <tr id="is_multiple" class="hiddenElement"> {* This section shown only when Used For = Contact, Individ, Org or Household. *}
        <td></td>
        <td class="html-adjust">{$form.is_multiple.html}&nbsp;{$form.is_multiple.label} {help id="id-is_multiple"}</td>
    </tr>
    <tr id="multiple" class="hiddenElement">
        {*<dt>{$form.min_multiple.label}</dt><dd>{$form.min_multiple.html}</dd>*}
        <td class="label">{$form.max_multiple.label}</td>
        <td>{$form.max_multiple.html} {help id="id-max_multiple"}</td>
    </tr>
    <tr id="style" class="hiddenElement">
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
    <a href="{crmURL p='civicrm/admin/custom/group/field' q="action=browse&reset=1&gid=$gid"}" class="button"><span>{ts}Custom Fields for this Set{/ts}</span></a>
    </div>
{/if}
{$initHideBlocks}
{literal}
<script type="text/Javascript">
cj(function($) {

  showHideStyle();
  cj('#extends_0').change(function() {
    showHideStyle();
  });

  var  isGroupEmpty = "{/literal}{$isGroupEmpty}{literal}";
  if (isGroupEmpty) {
    showRange(true);
  }
  cj('#is_multiple').click(function() {
    showRange();
  });

  function showHideStyle() {
    var isShow  = false;
    var extend  = cj('#extends_0').val();

    var contactTypes    = {/literal}{$contactTypes}{literal};
    var showStyle       = "{/literal}{$showStyle}{literal}";
    var showMultiple    = "{/literal}{$showMultiple}{literal}";
    var showMaxMultiple = "{/literal}{$showMaxMultiple}{literal}";

    if (cj.inArray(extend, contactTypes) >= 0) {
      isShow  = true;
    }

    if (isShow) {
      cj("tr#style").show();
      cj("tr#is_multiple").show();
      if (cj('#is_multiple :checked').length) {
        cj("tr#multiple").show();
      }
    }
    else {
      cj("tr#style").hide();
      cj("tr#is_multiple").hide();
      cj("tr#multiple").hide();
    }

    if (showStyle) {
      cj("tr#style").show();
    }

    if (showMultiple) {
      cj("tr#style").show();
      cj("tr#is_multiple").show();
    }

    if (!showMaxMultiple) {
      cj("tr#multiple").hide();
    }
    else if(cj( '#is_multiple').attr('checked')) {
      cj("tr#multiple").show();
    }
  }

  function showRange(onFormLoad) {
    if(cj("#is_multiple :checked").length) {
      cj("tr#multiple").show();
      cj("select#style option[value='Tab']").attr("selected", "selected");
    }
    else {
      cj("tr#multiple").hide();
      if (!onFormLoad) {
        cj("select#style option[value='Inline']").attr("selected", "selected");
      }
    }
  }

  // In update mode, when 'extends' is set to an option which doesn't have
  // any options in 2nd selector (for subtypes)  -
  var subtypes = document.getElementById('extends_1');
  if (subtypes) {
    if (subtypes.options.length <= 0) {
      subtypes.style.display = 'none';
    }
    else {
      subtypes.style.display = 'inline';
    }
  }
});

function warnDataLoss() {
  var submittedSubtypes = cj('#extends_1').val();
  var defaultSubtypes   = {/literal}{$defaultSubtypes}{literal};

  var warning = false;
  cj.each(defaultSubtypes, function(index, subtype) {
    if (cj.inArray(subtype, submittedSubtypes) < 0) {
      warning = true;
    }
  });

  if (warning) {
    return confirm( 'One or more subtypes has been un-selected from the list. Any custom data associated with un-selected subtype would be removed. Click OK to proceed.' );
  }
  return true;
}
</script>
{/literal}
