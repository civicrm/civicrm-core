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
    {ts}Legacy Settings{/ts}
  </summary>
  <div class="crm-accordion-body">
    <div class="crm-block crm-form-block crm-uf-legacysetting-form-block">
      <table class="form-layout">
        <tr class="crm-uf-advancesetting-form-block-limit_listings_group_id">
          <td class="label">{$form.limit_listings_group_id.label} {help id='limit_listings_group_id' file='CRM/Legacyprofiles/Form/Group.hlp'}</td>
          <td>{$form.limit_listings_group_id.html}</td>
        </tr>
        <tr class="crm-uf-advancesetting-form-block-is_proximity_search">
          <td class="label">{$form.is_proximity_search.label} {help id='is_proximity_search' file='CRM/Legacyprofiles/Form/Group.hlp'}</td>
          <td>{$form.is_proximity_search.html}</td>
        </tr>
        <tr class="crm-uf-advancesetting-form-block-is_edit_link">
          <td class="label">{help id='is_edit_link' file='CRM/Legacyprofiles/Form/Group.hlp'}</td>
          <td>{$form.is_edit_link.html} {$form.is_edit_link.label}</td>
        </tr>
        <tr class="crm-uf-advancesetting-form-block-is_uf_link">
          <td class="label">{help id='is_uf_link' file='CRM/Legacyprofiles/Form/Group.hlp'}</td>
          <td>{$form.is_uf_link.html} {$form.is_uf_link.label}</td>
        </tr>
      </table>
    </div>
  </div>
</details>
