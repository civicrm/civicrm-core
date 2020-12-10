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
<td>
  <span class="crm-age-range">
    <span class="crm-age-range-min">
      {assign var=minName   value=$fieldName|cat:$from}
      {$form.$minName.label}
      {$form.$minName.html}
    </span>
    <span class="crm-age-range-max">
      {assign var=maxName   value=$fieldName|cat:$to}
      {$form.$maxName.label}
      {$form.$maxName.html}
    </span>
  </span>
  <span class="crm-age-range-asofdate">
    {assign var=dateName value=$fieldName|cat:$date}
    {$form.$dateName.label}
    {$form.$dateName.html}
  </span>
  {literal}
    <script type="text/javascript">
      CRM.$(function($) {
        var $form = $('form.{/literal}{$form.formClass}{literal}');
        function toggleDate() {
          $(".crm-age-range-asofdate").toggle(!!($('.crm-age-range-min input', $form).val() || $('.crm-age-range-max input', $form).val()));
        }
        $('.crm-age-range input', $form).on('keyup change', toggleDate);
        toggleDate();
      });

    </script>
  {/literal}
</td>
