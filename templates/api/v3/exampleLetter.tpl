


Dear {$result.values.0.display_name}

You have made {$result.values.0.api_Contribution_get.count} payments to us so far. You are well on your way
to a complementary free beer.


{assign var='contribution' value=$result.values.0.api_Contribution_get.values}
{foreach from=$contribution item=cont}
    {$cont.currency}  {$cont.total_amount}  {$cont.receive_date|date_format:"%A, %B %e, %Y"}
{/foreach}

