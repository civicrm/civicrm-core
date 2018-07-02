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
{* tpl for building Individual related fields *}
<script type="text/javascript">
{literal}
CRM.$(function($) {
  if ($('#contact_sub_type *').length == 0) {//if they aren't any subtype we don't offer the option
    $('#contact_sub_type').parent().hide();
  }
});
</script>
{/literal}

<table class="form-layout-compressed">
  <tr>
    {if $form.prefix_id}
    <td>
      {$form.prefix_id.label}<br/>
      {$form.prefix_id.html}
    </td>
    {/if}
    {if $form.formal_title}
    <td>
      {$form.formal_title.label}<br/>
      {$form.formal_title.html}
    </td>
    {/if}
    {if $form.first_name}
    <td>
      {$form.first_name.label}<br />
      {$form.first_name.html}
    </td>
    {/if}
    {if $form.middle_name}
    <td>
      {$form.middle_name.label}<br />
      {$form.middle_name.html}
    </td>
    {/if}
    {if $form.last_name}
    <td>
      {$form.last_name.label}<br />
      {$form.last_name.html}
    </td>
    {/if}
    {if $form.suffix_id}
    <td>
      {$form.suffix_id.label}<br/>
      {$form.suffix_id.html}
    </td>
    {/if}
  </tr>

  <tr>
    <td colspan="2">
      {$form.employer_id.label}&nbsp;{help id="id-current-employer" file="CRM/Contact/Form/Contact.hlp"}<br />
      {$form.employer_id.html}
    </td>
    <td>
      {$form.job_title.label}<br />
      {$form.job_title.html}
    </td>
    <td>
      {$form.nick_name.label}<br />
      {$form.nick_name.html}
    </td>
    <td>
      {$form.contact_sub_type.label}<br />
      {$form.contact_sub_type.html}
    </td>
  </tr>
</table>
