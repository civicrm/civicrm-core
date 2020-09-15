{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* tpl for building Organization related fields *}
<table class="form-layout-compressed">
  <tr>
    <td>{
      $form.organization_name.label}<br/>
      {$form.organization_name.html}
    </td>
    <td>
      {$form.legal_name.label}<br/>
      {$form.legal_name.html}
    </td>
    <td>
      {$form.nick_name.label}<br/>
      {$form.nick_name.html}
    </td>
    <td>
      {$form.sic_code.label}<br/>
      {$form.sic_code.html}
    </td>
    <td>
      {$form.contact_sub_type.label}<br />
      {$form.contact_sub_type.html}
    </td>
  </tr>
</table>
