{assign var="greeting" value="{contact.email_greeting}"}{if $greeting}<p>{$greeting},</p>{/if}

<p>Thank you for signing {$petition.title}.</p>

{include file="CRM/Campaign/Page/Petition/SocialNetwork.tpl" petition_id=$survey_id noscript=true emailMode=true}
