
Dear {$displayValues.contactName}:

Thank you for registering your organization - {$displayValues.organizationName} - as part of the
CiviCRM Register Your Site project. Registration information will be used to help improve CiviCRM
as a project, and help in building a stronger ecosystem.

Here are the values you entered on the form:

{foreach from=$profileValues item=value key=name}
{$name}: {$value|strip_tags:false}
{/foreach}

If you want to modify this information, please click this link: {$displayValues.hashLink}

NOTE: The link above will expire in 7 days. After that time, you will need to login into
CiviCRM.org and click on the "Contact Dashboard" link available at the top left hand corner
of your screen.

Regards

The CiviCRM Team

p.s. Have you done your part to "make it happen"? The "CiviCRM Make It Happen" Project gives every
member of the CiviCRM community an opportunity to support improvements that are important to them.
Consider making a contribution now at: http://civicrm.org/contribute