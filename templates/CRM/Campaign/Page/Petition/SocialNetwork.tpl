{*
Default Thank-you page for verified signers.
You might have a specific page that displays more information that the form.
Check SocialNetwork.drupal as an example
*}
{capture assign=petitionURL}{crmURL p='civicrm/petition/sign' q="sid=`$petition_id`" a=1 fe=1 h=1}{/capture}
{include file="CRM/common/SocialNetwork.tpl" url=$petitionURL title=$petitionTitle pageURL=$petitionURL}
