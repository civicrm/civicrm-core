{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<h3>{ts}Upload CSV File{/ts}</h3>
  <table class="form-layout">
    <tr>
        <td class="label">{$form.uploadFile.label}</td>
        <td>{$form.uploadFile.html}<br />
            <div class="description">
              {ts}File format must be comma-separated-values (CSV). File must be UTF8 encoded if it contains special characters (e.g. accented letters, etc.).{/ts}<br />
              {ts 1=$uploadSize}Maximum Upload File Size: %1 MB{/ts}
            </div>
        </td>
    </tr>
    <tr>
        <td></td>
        <td>{$form.skipColumnHeader.html} {$form.skipColumnHeader.label}</td>
    </tr>
    <tr class="crm-import-datasource-form-block-fieldSeparator">
      <td class="label">{$form.fieldSeparator.label} {help id='fieldSeparator' file='CRM/Contact/Import/Form/DataSource'}</td>
      <td>{$form.fieldSeparator.html}</td>
    </tr>
  </table>

