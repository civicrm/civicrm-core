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
        {if $legacyprofiles}
          <tr class="crm-uf-advancesetting-form-block-group">
              <td class="label">{$form.group.label} {help id='group'}</td>
              <td>{$form.group.html}</td>
          </tr>
        {/if}
        <tr class="crm-uf-advancesetting-form-block-add_contact_to_group">
            <td class="label">{$form.add_contact_to_group.label} {help id='add_contact_to_group'}</td>
            <td>{$form.add_contact_to_group.html}</td>
        </tr>
        <tr class="crm-uf-advancesetting-form-block-notify">
            <td class="label">{$form.notify.label} {help id='notify'}</td>
            <td>{$form.notify.html}</td>
        </tr>
        <tr class="crm-uf-advancesetting-form-block-post_url">
            <td class="label">{$form.post_url.label} {help id='post_url'}</td>
            <td>{$form.post_url.html}</td>
        </tr>
        <tr class="crm-uf-advancesetting-form-block-add_cancel_button">
            <td class="label">{help id='add_cancel_button'}</td>
            <td>{$form.add_cancel_button.html} {$form.add_cancel_button.label}</td>
        </tr>
        <tr class="cancel_button_section crm-uf-advancesetting-form-block-cancel_url">
            <td class="label">{$form.cancel_url.label} {help id='cancel_url'}</td>
            <td>{$form.cancel_url.html}</td>
        </tr>
        {foreach from=$advancedFieldsConverted item=fieldName}
          {assign var=fieldSpec value=$entityFields.$fieldName}
          <tr class="crm-{$entityInClassFormat}-form-block-{$fieldName} {$fieldSpec.class}">
            {include file="CRM/Core/Form/Field.tpl"}
          </tr>
        {/foreach}
        <tr class="crm-uf-advancesetting-form-block-is_cms_user">
                <td class="label">{$form.is_cms_user.label} {help id='is_cms_user'}</td>
                <td>{$form.is_cms_user.html}</td>
        </tr>
        <tr class="crm-uf-advancesetting-form-block-is_update_dupe">
            <td class="label">{$form.is_update_dupe.label} {help id='is_update_dupe'}</td>
            <td>{$form.is_update_dupe.html}</td>
        </tr>
        {if $legacyprofiles}
          <tr class="crm-uf-advancesetting-form-block-is_proximity_search">
              <td class="label">{$form.is_proximity_search.label} {help id='is_proximity_search'}</td>
              <td>{$form.is_proximity_search.html}</td>
          </tr>
          <tr class="crm-uf-advancesetting-form-block-is_map">
              <td class="label">{help id='is_map'}</td>
              <td>{$form.is_map.html} {$form.is_map.label}</td>
          </tr>
          <tr class="crm-uf-advancesetting-form-block-is_edit_link">
              <td class="label">{help id='is_edit_link'}</td>
              <td>{$form.is_edit_link.html} {$form.is_edit_link.label}</td>
          </tr>
          <tr class="crm-uf-advancesetting-form-block-is_uf_link">
            <td class="label">{help id='is_uf_link'}</td>
            <td>{$form.is_uf_link.html} {$form.is_uf_link.label}</td>
          </tr>
        {/if}
        <tr class="crm-uf-advancesetting-form-block-add_captcha">
            <td class="label">{help id='add_captcha'}</td>
            <td>{$form.add_captcha.html} {$form.add_captcha.label}</td>
        </tr>
      </table>
    </div>
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
