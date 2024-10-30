{assign var="greeting" value="{contact.email_greeting_display}"}{if $greeting}<p>{$greeting},</p>{/if}

<p>Thank you for signing {survey.title}.</p>

{capture assign=petitionURL}{crmURL p='civicrm/petition/sign' q="sid={survey.id}" a=1 fe=1 h=1}{/capture}
{include file="CRM/common/SocialNetwork.tpl" url=$petitionURL title='{survey.title}' pageURL=$petitionURL petition_id='{survey.id}' noscript=true emailMode=true}
