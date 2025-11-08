{*
 @param string[] $fieldNames
 @param string $helpSet
 *}
<table class="form-layout-compressed">
  {foreach from=$fieldNames item=fieldName}
    {if !empty($form.$fieldName)}
      <tr class="crm-paymentProcessor-form-block-{$fieldName}">
        <td class="label">{$form.$fieldName.label}</td>
        <td>
          {$form.$fieldName.html}
          {help id="`$ppTypeName``$helpSet``$fieldName`" title=$form.$fieldName.label file="CRM/Admin/Page/PaymentProcessor.hlp"}
        </td>
      </tr>
    {/if}
  {/foreach}
</table>
