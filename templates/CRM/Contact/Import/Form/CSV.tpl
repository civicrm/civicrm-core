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
            <div class="description">{ts}File format must be comma-separated-values (CSV). File must be UTF8 encoded if it contains special characters (e.g. accented letters, etc.).{/ts}</div>
            {ts 1=$uploadSize}Maximum Upload File Size: %1 MB{/ts}
        </td>
    </tr>
    <tr>
        <td></td>
        <td>{$form.skipColumnHeader.html} {$form.skipColumnHeader.label}
            <div class="description">{ts}Check this box if the first row of your file consists of field names (Example: 'First Name','Last Name','Email'){/ts}</div>
        </td>
    </tr>
  </table>

