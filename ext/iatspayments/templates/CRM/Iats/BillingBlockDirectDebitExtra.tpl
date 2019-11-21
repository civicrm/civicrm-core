{*
 Extra fields for iats direct debit, template
*}
    <div id="iats-direct-debit-extra">
    <div class="description">You can find your Bank number and branch transit numbers by inspecting a cheque.</div>
                        <div class="crm-section {$form.bank_account_type.name}-section">
                            <div class="label">{$form.bank_account_type.label}</div>
                            <div class="content">{$form.bank_account_type.html}</div>
                            <div class="clear"></div>
                        </div>
    </div>

     <script type="text/javascript">
     {literal}

cj( function( ) { /* move my account type box up where it belongs */
  cj('.direct_debit_info-section').prepend(cj('#iats-direct-debit-extra'));
});
{/literal}
</script>
