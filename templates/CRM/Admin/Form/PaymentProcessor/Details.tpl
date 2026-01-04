{*
 @param string[] $fieldNames Ex: ["user_name", "url_recur"] or ["test_user_name", "test_url_recur"]
 @param string $ppTypeName Ex: "AuthNet" or "PayPal_Express"
 *}
<table class="form-layout-compressed">
  {foreach from=$fieldNames item=fieldName}
    {if !empty($form.$fieldName)}
      <tr class="crm-paymentProcessor-form-block-{$fieldName}">
        <td class="label">
          {$form.$fieldName.label}
          {help id="`$ppTypeName`_`$fieldName`" title=$form.$fieldName.textLabel file="CRM/Admin/Page/PaymentProcessor.hlp"}
        </td>
        <td>
          {$form.$fieldName.html}
        </td>
      </tr>
    {/if}
  {/foreach}
</table>
