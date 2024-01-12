{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<h3>{ts}Upload Spreadsheet{/ts}</h3>

<table class="form-layout">
  <tr>
    <td class="label">{$form.uploadFile.label}</td>
    <td>{$form.uploadFile.html}<br />
      <div class="description">
          {ts}The file must be of type ODS (LibreOffice), XLSX (Excel) or CSV.{/ts}<br />
          {ts 1=$uploadSize}Maximum Upload File Size: %1 MB{/ts}
      </div>
    </td>
  </tr>
  <tr>
    <td></td>
    <td>{$form.isFirstRowHeader.html} {$form.isFirstRowHeader.label}</td>
  </tr>
</table>

