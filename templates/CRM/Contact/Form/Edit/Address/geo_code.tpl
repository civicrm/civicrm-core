{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
