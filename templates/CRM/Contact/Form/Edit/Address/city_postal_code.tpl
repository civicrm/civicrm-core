{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
<tr><td colspan="3" style="padding:0;">
<table class="crm-address-element">
<tr>
    {if !empty($form.address.$blockId.city)}
       <td>
          {$form.address.$blockId.city.label}<br />
          {$form.address.$blockId.city.html}
       </td>
    {/if}
    {if !empty($form.address.$blockId.postal_code)}
       <td>
          {$form.address.$blockId.postal_code.label}{if !empty($form.address.$blockId.postal_code_suffix)}&nbsp;<span>{ts}Suffix{/ts}</span>{/if}<br />
          {$form.address.$blockId.postal_code.html}&nbsp;&nbsp;
          {if !empty($form.address.$blockId.postal_code_suffix)}
            {$form.address.$blockId.postal_code_suffix.html} {help id="id-postal-code-suffix" file="CRM/Contact/Form/Contact.hlp"}
          {/if}
       </td>
    {/if}
    <td colspan="2">&nbsp;&nbsp;</td>
</tr>
</table>
</td></tr>
