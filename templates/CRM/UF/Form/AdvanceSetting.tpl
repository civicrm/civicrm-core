{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<details class="crm-accordion-bold">
 <summary>
    {ts}Advanced Settings{/ts}
  </summary>
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

        <tr class="crm-uf-advancesetting-form-block-post_url">
            <td class="label">{$form.post_url.label}</td>
            <td>{$form.post_url.html} {help id='id-post_url' file="CRM/UF/Form/Group.hlp"}</td>
        </tr>

        <tr class="crm-uf-advancesetting-form-block-add_cancel_button">
            <td class="label"></td>
            <td>{$form.add_cancel_button.html} {$form.add_cancel_button.label} {help id='id-add_cancel_button' file="CRM/UF/Form/Group.hlp"}</td>
        </tr>

        <tr class="cancel_button_section crm-uf-advancesetting-form-block-cancel_url">
            <td class="label">{$form.cancel_url.label}</td>
            <td>{$form.cancel_url.html} {help id='id-cancel_url' file="CRM/UF/Form/Group.hlp"}</td>
        </tr>

        {foreach from=$advancedFieldsConverted item=fieldName}
          {assign var=fieldSpec value=$entityFields.$fieldName}
          <tr class="crm-{$entityInClassFormat}-form-block-{$fieldName} {$fieldSpec.class}">
            {include file="CRM/Core/Form/Field.tpl"}
          </tr>
        {/foreach}

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
  </div>
</details>
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
