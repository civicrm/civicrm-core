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
      <table class="form-layout-compressed">
        <tr id="profile_visibility" class="crm-uf-field-form-block-visibility">
          <td class="label">{$form.visibility.label} {help id='visibility' file='CRM/Legacyprofiles/Form/Field.hlp'}</td>
          <td>{$form.visibility.html}</td>
        </tr>
        <tr class="crm-uf-field-form-block-is_searchable">
          <td class="label"><div id="is_search_label">{$form.is_searchable.label} {help id='is_searchable' file='CRM/Legacyprofiles/Form/Field.hlp'}</div></td>
          <td><div id="is_search_html">{$form.is_searchable.html}</div></td>
        </tr>
        <tr class="crm-uf-field-form-block-in_selector">
          <td class="label"><div id="in_selector_label">{$form.in_selector.label} {help id='in_selector' file='CRM/Legacyprofiles/Form/Field.hlp'}</div></td>
          <td><div id="in_selector_html">{$form.in_selector.html}</div></td>
        </tr>
      </table>
    </div>
  </div>
</details>
