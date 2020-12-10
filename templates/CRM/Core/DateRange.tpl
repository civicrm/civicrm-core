{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{*this is included inside a table row*}
{assign var=relativeName   value=$fieldName|cat:"_relative"}
<td colspan=2>
  {if $label}
   {ts}{$label}{/ts}<br />
  {/if}
  {$form.$relativeName.html}<br />
  <span class="crm-absolute-date-range">
    <span class="crm-absolute-date-from">
      {assign var=fromName   value=$fieldName|cat:$from}
      {$form.$fromName.label}
      {include file="CRM/common/jcalendar.tpl" elementName=$fromName}
    </span>
    <span class="crm-absolute-date-to">
      {assign var=toName   value=$fieldName|cat:$to}
      {$form.$toName.label}
      {include file="CRM/common/jcalendar.tpl" elementName=$toName}
    </span>
  </span>
  {literal}
    <script type="text/javascript">
      cj("#{/literal}{$relativeName}{literal}").change(function() {
        var n = cj(this).parent().parent();
        if (cj(this).val() == "0") {
          cj(".crm-absolute-date-range", n).show();
        } else {
          cj(".crm-absolute-date-range", n).hide();
          cj(':text', n).val('');
        }
      }).change();
    </script>
  {/literal}
</td>
