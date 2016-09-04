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
<div class="crm-block crm-form-block crm-custom-field-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    <div class='status'><div class="icon inform-icon"></div>
        &nbsp;{ts}Warning: This functionality is currently in beta stage. Consider backing up your database before using it. Click "Cancel" to return to the "edit custom field" form without making changes.{/ts}
    </div>
    <table class="form-layout">
        <tr class="crm-custom-src-field-form-block-label">
            <td class="label">{$form.src_html_type.label}</td>
            <td class="html-adjust">{$form.src_html_type.html}</td>
        </tr>
        <tr class="crm-custom-dst-field-form-block-label">
            <td class="label">{$form.dst_html_type.label}</td>
            <td class="html-adjust">{$form.dst_html_type.html}</td>
        </tr>
    </table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{literal}
<script type="text/Javascript">
  function checkCustomDataField( ) {
    var srcHtmlType = '{/literal}{$srcHtmlType}{literal}';
    var singleValOps = ['Text', 'Select', 'Radio', 'Autocomplete-Select'];
    var multiValOps  = ['CheckBox', 'Multi-Select', 'AdvMulti-Select'];
    var dstHtmlType = cj('#dst_html_type').val( );
    if ( !dstHtmlType ) {
      return true;
    }

    if ( ( cj.inArray(srcHtmlType, multiValOps) > -1 ) &&
         ( cj.inArray(dstHtmlType, singleValOps) > -1 ) ) {
    return confirm( "{/literal}{ts escape='js'}Changing a 'multi option' html type to a 'single option' html type, might results in a data loss. Please consider to take db backup before change the html type. Click 'Ok' to continue.{/ts}{literal}" );
    }
    return true;
  }
</script>
{/literal}

