{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{* This included tpl checks if a given username is taken or available. *}
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
   spchar = spchar + "|\~|\`|\:|\@|\!|\=|\#|\$|\^|\*|\{|\}|\\[|\\]|\+|\?|\,";
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

      //remove all the class add the messagebox classes and start fading
      cj("#msgbox").removeClass().addClass('cmsmessagebox').css({"color":"#000","backgroundColor":"#FFC","border":"1px solid #c93"}).text(check).fadeIn("slow");

      //check the username exists or not from ajax
   var contactUrl = {/literal}"{crmURL p='civicrm/ajax/cmsuser' h=0 }"{literal};

   cj.post(contactUrl,{ cms_name:cj("#cms_name").val() } ,function(data) {
      if ( data.name == "no") {/*if username not avaiable*/
         cj("#msgbox").fadeTo(200,0.1,function() {
      cj(this).html(notavailable).addClass('cmsmessagebox').css({"color":"#CC0000","backgroundColor":"#F7CBCA","border":"1px solid #CC0000"}).fadeTo(900,1);
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
