{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This included tpl checks if a given username is taken or available. *}
{crmSigner var=checkUserSig for="civicrm/ajax/cmsuser"}
{literal}
var lastName = null;
cj("#checkavailability").click(function() {
   var cmsUserName = cj.trim(cj("#cms_name").val());
   if ( lastName == cmsUserName) {
   /*if user checking the same user name more than one times. avoid the ajax call*/
   return;
   }
   /*don't allow special character and for joomla minimum username length is two*/

   var spchar = "\<|\>|\"|\'|\%|\;|\(|\)|\&|\\\\|\/";

   {/literal}{if $config->userSystem->is_drupal == "1"}{literal}
   spchar = spchar + "|\~|\`|\:|\!|\=|\#|\$|\^|\*|\{|\}|\\[|\\]|\+|\?|\,";
   {/literal}{/if}{literal}
   var r = new RegExp( "["+spchar+"]", "i");
   /*regular expression \\ matches a single backslash. this becomes r = /\\/ or r = new RegExp("\\\\").*/
   if ( r.exec(cmsUserName) ) {
   alert('{/literal}{ts escape="js"}Your username contains invalid characters{/ts}{literal}');
      return;
   }
   {/literal}{if $config->userFramework == "Joomla"}{literal}
   else if ( cmsUserName && cmsUserName.length < 2 ) {
      alert('{/literal}{ts escape="js"}Your username is too short{/ts}{literal}');
      return;
   }
   {/literal}{/if}{literal}
   if (cmsUserName) {
   /*take all messages in javascript variable*/
   var check        = "{/literal}{ts escape='js'}Checking...{/ts}{literal}";
   var available    = "{/literal}{ts escape='js'}This username is currently available.{/ts}{literal}";
   var notavailable = "{/literal}{ts escape='js'}This username is taken.{/ts}{literal}";
   var errorMsg     = "{/literal}{ts escape='js'}Error checking username. Please reload the form and try again.{/ts}{literal}";

      //remove all the class add the messagebox classes and start fading
      cj("#msgbox").removeClass().addClass('cmsmessagebox').css({"color":"#000","backgroundColor":"#FFC","border":"1px solid #c93"}).text(check).fadeIn("slow");

      //check the username exists or not from ajax
   var contactUrl = {/literal}"{crmURL p='civicrm/ajax/cmsuser' h=0}"{literal};

   var checkUserParams = {
       cms_name: cj("#cms_name").val(),
       ts: {/literal}"{$checkUserSig.ts}"{literal},
       sig: {/literal}"{$checkUserSig.signature}"{literal},
       for: 'civicrm/ajax/cmsuser'
   };
   cj.post(contactUrl, checkUserParams ,function(data) {
      if ( data.name == "no") {/*if username not avaiable*/
         cj("#msgbox").fadeTo(200,0.1,function() {
      cj(this).html(notavailable).addClass('cmsmessagebox').css({"color":"#CC0000","backgroundColor":"#F7CBCA","border":"1px solid #CC0000"}).fadeTo(900,1);
         });
      } else if ( data.name == "error") {/*if username not avaiable*/
         cj("#msgbox").fadeTo(200,0.1,function() {
             cj(this).html(errorMsg).addClass('cmsmessagebox').css({"color":"#CC0000","backgroundColor":"#F7CBCA","border":"1px solid #CC0000"}).fadeTo(900,1);
         });
      } else {
         cj("#msgbox").fadeTo(200,0.1,function() {
      cj(this).html(available).addClass('cmsmessagebox').css({"color":"#008000","backgroundColor":"#C9FFCA", "border": "1px solid #349534"}).fadeTo(900,1);
         });
      }
   }, "json");
   lastName = cmsUserName;
   } else {
   cj("#msgbox").removeClass().text('').css({"backgroundColor":"#FFFFFF", "border": "0px #FFFFFF"}).fadeIn("fast");
   }
});
{/literal}
