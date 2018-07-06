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
{if !empty($form.address.$blockId.geo_code_1) && !empty($form.address.$blockId.geo_code_2)}
   <tr>
      <td colspan="2">
          {$form.address.$blockId.geo_code_1.label},&nbsp;{$form.address.$blockId.geo_code_2.label} {help id="id-geo-code" file="CRM/Contact/Form/Contact.hlp"}<br />
          {$form.address.$blockId.geo_code_1.html},&nbsp;{$form.address.$blockId.geo_code_2.html}<br />
      </td>
    </tr>
    {if !empty($form.address.$blockId.manual_geo_code)}
     <tr>
        <td colspan="2">
          {$form.address.$blockId.manual_geo_code.html}
          {$form.address.$blockId.manual_geo_code.label} {help id="id-geo-code-override" file="CRM/Contact/Form/Contact.hlp"}
        </td>
      </tr>
    {/if}
   </tr>
{/if}
