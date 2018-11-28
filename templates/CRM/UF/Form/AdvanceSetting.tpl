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
<div class="crm-accordion-wrapper collapsed">
 <div class="crm-accordion-header">
    Advanced Settings
  </div><!-- /.crm-accordion-header -->
  <div class="crm-accordion-body">
  <div class="crm-block crm-form-block crm-uf-advancesetting-form-block">
    <table class="form-layout">
        <tr class="crm-uf-advancesetting-form-block-group">
            <td class="label">{$form.group.label}</td>
            <td>{$form.group.html} {help id='id-limit_group' file="CRM/UF/Form/Group.hlp"}</td>
        </tr>

        <tr class="crm-uf-advancesetting-form-block-add_contact_to_group">
            <td class="label">{$form.add_contact_to_group.label}</td>
            <td>{$form.add_contact_to_group.html} {help id='id-add_group' file="CRM/UF/Form/Group.hlp"}</td>
        </tr>

        <tr class="crm-uf-advancesetting-form-block-notify">
            <td class="label">{$form.notify.label}</td>
            <td>{$form.notify.html} {help id='id-notify_email' file="CRM/UF/Form/Group.hlp"}</td>
        </tr>

        <tr class="crm-uf-advancesetting-form-block-post_URL">
            <td class="label">{$form.post_URL.label}</td>
            <td>{$form.post_URL.html} {help id='id-post_URL' file="CRM/UF/Form/Group.hlp"}</td>
        </tr>

        <tr class="crm-uf-advancesetting-form-block-add_cancel_button">
            <td class="label"></td>
            <td>{$form.add_cancel_button.html} {$form.add_cancel_button.label} {help id='id-add_cancel_button' file="CRM/UF/Form/Group.hlp"}</td>
        </tr>

        <tr class="cancel_button_section crm-uf-advancesetting-form-block-cancel_URL">
            <td class="label">{$form.cancel_URL.label}</td>
            <td>{$form.cancel_URL.html} {help id='id-cancel_URL' file="CRM/UF/Form/Group.hlp"}</td>
        </tr>

        <tr class="cancel_button_section crm-uf-advancesetting-form-block-cancel_button_text">
            <td class="label">{$form.cancel_button_text.label}</td>
            <td>{$form.cancel_button_text.html} {help id='id-cancel_button_text' file="CRM/UF/Form/Group.hlp"}</td>
        </tr>

        <tr class="crm-uf-advancesetting-form-block-submit_button_text">
            <td class="label">{$form.submit_button_text.label}</td>
            <td>{$form.submit_button_text.html} {help id='id-submit_button_text' file="CRM/UF/Form/Group.hlp"}</td>
        </tr>

        <tr class="crm-uf-advancesetting-form-block-add_captcha">
            <td class="label"></td>
            <td>{$form.add_captcha.html} {$form.add_captcha.label} {help id='id-add_captcha' file="CRM/UF/Form/Group.hlp"}</td>
        </tr>

        <tr class="crm-uf-advancesetting-form-block-is_cms_user">
                <td class="label">{$form.is_cms_user.label}</td>
                <td>{$form.is_cms_user.html} {help id='id-is_cms_user' file="CRM/UF/Form/Group.hlp"}</td>
        </tr>

        <tr class="crm-uf-advancesetting-form-block-is_update_dupe">
            <td class="label">{$form.is_update_dupe.label}</td>
            <td>{$form.is_update_dupe.html} {help id='id-is_update_dupe' file="CRM/UF/Form/Group.hlp"}</td>
        </tr>

        <tr class="crm-uf-advancesetting-form-block-is_proximity_search">
            <td class="label">{$form.is_proximity_search.label}</td>
            <td>{$form.is_proximity_search.html} {help id='id-is_proximity_search' file="CRM/UF/Form/Group.hlp"}</td></tr>

        <tr class="crm-uf-advancesetting-form-block-is_map">
            <td class="label"></td>
            <td>{$form.is_map.html} {$form.is_map.label} {help id='id-is_map' file="CRM/UF/Form/Group.hlp"}</td>
        </tr>

        <tr class="crm-uf-advancesetting-form-block-is_edit_link">
            <td class="label"></td>
            <td>{$form.is_edit_link.html} {$form.is_edit_link.label} {help id='id-is_edit_link' file="CRM/UF/Form/Group.hlp"}</td>
        </tr>

        <tr class="crm-uf-advancesetting-form-block-is_uf_link">
            <td class="label"></td>
            <td>{$form.is_uf_link.html} {$form.is_uf_link.label} {help id='id-is_uf_link' file="CRM/UF/Form/Group.hlp"}</td>
        </tr>
    </table>
    </div><!-- / .crm-block -->
  </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
{literal}
  <script type="text/javascript">
  CRM.$(function($) {
    $('.cancel_button_section').toggle($('#add_cancel_button').is(":checked"));
    $('#add_cancel_button').click(function() {
      $('.cancel_button_section').toggle($(this).is(":checked"));
    });
  });
  </script>
{/literal}
