{* file to handle db changes in 4.5.beta2 during upgrade *}

--CRM-14948 To delete list of outdated Russian provinces
DELETE FROM `civicrm_state_province` WHERE `name` IN ('Komi-Permyatskiy avtonomnyy okrug','Taymyrskiy (Dolgano-Nenetskiy)','Evenkiyskiy avtonomnyy okrug','Koryakskiy avtonomnyy okrug','Ust\'-Ordynskiy Buryatskiy','Aginskiy Buryatskiy avtonomnyy');

--CRM-14948 To update new list of new Russian provinance
UPDATE `civicrm_state_province` SET `name`='Permskiy kray',`abbreviation`='PEK',`country_id`= 1177 WHERE `id` = 4270;

UPDATE `civicrm_state_province` SET `name`='Kamchatskiy kray',`country_id`= 1177 WHERE `id` = 4252;

UPDATE `civicrm_state_province` SET `name`='Zabaykal skiy kray',`abbreviation`='ZSK',`country_id`= 1177 WHERE `id` = 4247;

-- Sample CiviMail Responsive Newsletter message templates CRM-14940
INSERT INTO civicrm_msg_template
  (msg_title,      msg_subject,                  msg_text,                  msg_html,                  workflow_id,        is_default, is_reserved)
  VALUES
  ('Sample Responsive Design Newsletter - Single Column', 'Sample Responsive Design Newsletter - Single Column', '', '<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
  <meta content="width=device-width, initial-scale=1.0" name="viewport" />
  <title></title>
  {literal}
  <style type="text/css">img {height: auto !important;}
           /* Client-specific Styles */
           #outlook a {padding:0;} /* Force Outlook to provide a "view in browser" menu link. */
           body{width:100% !important; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; margin:0; padding:0;}

           /* Prevent Webkit and Windows Mobile platforms from changing default font sizes, while not breaking desktop design. */
           .ExternalClass {width:100%;} /* Force Hotmail to display emails at full width */
           .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height: 100%;} /* Force Hotmail to display normal line spacing. */
           #backgroundTable {margin:0; padding:0; width:100% !important; line-height: 100% !important;}
           img {outline:none; text-decoration:none;border:none; -ms-interpolation-mode: bicubic;}
           a img {border:none;}
           .image_fix {display:block;}
           p {margin: 0px 0px !important;}
           table td {border-collapse: collapse;}
           table { border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; }
           a {text-decoration: none;text-decoration:none;}
           /*STYLES*/
           table[class=full] { width: 100%; clear: both; }
           /*IPAD STYLES*/
           @media only screen and (max-width: 640px) {
           a[href^="tel"], a[href^="sms"] {
           text-decoration: none;
           color:#136388; /* or whatever your want */
           pointer-events: none;
           cursor: default;
           }
           .mobile_link a[href^="tel"], .mobile_link a[href^="sms"] {
           text-decoration: default;
           color:#136388;
           pointer-events: auto;
           cursor: default;
           }
           table[class=devicewidth] {width: 440px!important;text-align:center!important;}
           table[class=devicewidthmob] {width: 416px!important;text-align:center!important;}
           table[class=devicewidthinner] {width: 416px!important;text-align:center!important;}
           img[class=banner] {width: 440px!important;auto!important;}
           img[class=col2img] {width: 440px!important;height:auto!important;}
           table[class="cols3inner"] {width: 100px!important;}
           table[class="col3img"] {width: 131px!important;}
           img[class="col3img"] {width: 131px!important;height: auto!important;}
           table[class="removeMobile"]{width:10px!important;}
           img[class="blog"] {width: 440px!important;height: auto!important;}
           }

           /*IPHONE STYLES*/
           @media only screen and (max-width: 480px) {
           a[href^="tel"], a[href^="sms"] {
           text-decoration: none;
           color: #136388; /* or whatever your want */
           pointer-events: none;
           cursor: default;
           }
           .mobile_link a[href^="tel"], .mobile_link a[href^="sms"] {
           text-decoration: none;
           color:#136388;  
           pointer-events: auto;
           cursor: default;
           }
           table[class=devicewidth] {width: 280px!important;text-align:center!important;}
           table[class=devicewidthmob] {width: 260px!important;text-align:center!important;}
           table[class=devicewidthinner] {width: 260px!important;text-align:center!important;}
           img[class=banner] {width: 280px!important;height:100px!important;}
           img[class=col2img] {width: 280px!important;height:auto!important;}
           table[class="cols3inner"] {width: 260px!important;}
           img[class="col3img"] {width: 280px!important;height: auto!important;}
           table[class="col3img"] {width: 280px!important;}
           img[class="blog"] {width: 280px!important;auto!important;}
           td[class="padding-top-right15"]{padding:15px 15px 0 0 !important;}
           td[class="padding-right15"]{padding-right:15px !important;}
           }

  		 @media only screen and (max-device-width: 800px)
  { td[class="padding-top-right15"]{padding:15px 15px 0 0 !important;}
           td[class="padding-right15"]{padding-right:15px !important;}}		 
  		 @media only screen and (max-device-width: 769px) {
  			 .devicewidthmob {font-size:16px;}
  			 }

  			  @media only screen and (max-width: 640px) {
  				 .desktop-spacer {display:none !important;} 
  			  }
  </style>
  {/literal}
  <!-- Start of preheader --><!-- Start of preheader -->
  <table bgcolor="#89c66b" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" width="100%">
  	<tbody>
  		<tr>
  			<td>
  			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
  				<tbody>
  					<tr>
  						<td width="100%">
  						<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
  							<tbody><!-- Spacing -->
  								<tr>
  									<td height="20" width="100%">&nbsp;</td>
  								</tr>
  								<!-- Spacing -->
  								<tr>
  									<td>
  									<table align="left" border="0" cellpadding="0" cellspacing="0" class="devicewidthinner" width="310">
  										<tbody>
  											<tr>
  												<td align="left" style="font-family: Helvetica, arial, sans-serif; font-size: 16px; line-height:120%; color: #f8f8f8;padding-left:15px; padding-bottom:5px;" valign="middle">Organization or Program Name Here</td>
  											</tr>
  										</tbody>
  									</table>

  									<table align="right" border="0" cellpadding="0" cellspacing="0" class="emhide" width="310">
  										<tbody>
  											<tr>
  												<td align="right" style="font-family: Helvetica, arial, sans-serif; font-size: 16px;color: #f8f8f8;padding-right:15px;" valign="middle">Month and Year</td>
  											</tr>
  										</tbody>
  									</table>
  									</td>
  								</tr>
  								<!-- Spacing -->
  								<tr>
  									<td height="20" width="100%">&nbsp;</td>
  								</tr>
  								<!-- Spacing -->
  							</tbody>
  						</table>
  						</td>
  					</tr>
  				</tbody>
  			</table>
  			</td>
  		</tr>
  	</tbody>
  </table>
  <!-- End of main-banner-->

  <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="100%">
  	<tbody>
  		<tr>
  			<td>
  			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
  				<tbody>
  					<tr>
  						<td width="100%">
  						<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
  							<tbody><!-- Spacing -->
  								<tr>
  									<td height="20" width="100%">
  									<table align="center" border="0" cellpadding="2" cellspacing="0" width="93%">
  										<tbody>
  											<tr>
  												<td rowspan="2" style="padding-top:10px; padding-bottom:10px;" width="38%"><img src="https://civicrm.org/sites/default/files/civicrm/custom/images/top-logo_2.png" alt="Replace with Your Logo" /></td>
  												<td align="right" width="62%">
  												<h6 class="collapse">&nbsp;</h6>
  												</td>
  											</tr>
  											<tr>
  												<td align="right">
  												<h5 style="font-family: Gill Sans, Gill Sans MT, Myriad Pro, DejaVu Sans Condensed, Helvetica, Arial, sans-serif; color:#136388;">&nbsp;</h5>
  												</td>
  											</tr>
  										</tbody>
  									</table>
  									</td>
  								</tr>
  								<tr>
  									<td>
  									<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
  										<tbody>
  											<tr>
  												<td width="100%">
  												<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
  													<tbody><!-- /Spacing -->
  														<tr>
  															<td style="font-family: Helvetica, arial, sans-serif; font-size: 23px; color:#f8f8f8; text-align:left; line-height: 32px; padding:5px 15px; background-color:#136388;">Headline Here</td>
  														</tr>
  														<!-- Spacing -->
  														<tr>
  															<td>
  															<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidthinner" width="650">
  																<tbody><!-- hero story -->
  																	<tr>
  																		<td align="center" class="devicewidthinner" width="100%">
  																		<div class="imgpop"><a href="#"><img alt="" border="0" class="blog" height="auto" src="https://civicrm.org/sites/default/files/civicrm/custom/images/650x396.png" style="display:block; border:none; outline:none; text-decoration:none; padding:0; line-height:0;" width="650" /></a></div>
  																		</td>
  																	</tr>
  																	<!-- /hero image --><!-- Spacing -->
  																	<tr>
  																		<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
  																	</tr>
  																	<!-- /Spacing -->
  																	<tr>
  																		<td style="font-family: Helvetica, arial, sans-serif; font-size: 18px;  text-align:left; line-height: 26px; padding:0 15px; color:#89c66b;"><a href="#" style="color:#89c66b;">Your Heading Here</a></td>
  																	</tr>
  																	<!-- Spacing -->
  																	<tr>
  																		<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
  																	</tr>
  																	<!-- /Spacing --><!-- content -->
  																	<tr>
  																		<td style="padding:0 15px;">
  																		<p style="font-family: Helvetica, arial, sans-serif; font-size: 16px; color: #7a6e67; text-align:left; line-height: 26px; padding-bottom:10px;">{literal}{contact.email_greeting}{/literal},																		</p>
  																		<p style="font-family: Helvetica, arial, sans-serif; font-size: 16px; color: #7a6e67; text-align:left; line-height: 26px; padding-bottom:10px;"><span class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 24px;">Replace with your text and images, and remember to link the facebook and twitter links in the footer to your pages. Have fun!</span></p>
  																		</td>
  																	</tr>
  																	<tr>
  																		<td class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 16px; font-weight:bold; color: #333333; text-align:left;line-height: 24px; padding-top:10px; padding-left:15px;"><a href="#" style="color:#136388;text-decoration:none;font-weight:bold;" target="_blank" title="read more">Read More</a></td>
  																	</tr>
  																	<!-- /button --><!-- Spacing -->
  																	<tr>
  																		<td height="20" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
  																	</tr>
  																	<!-- Spacing --><!-- end of content -->
  																</tbody>
  															</table>
  															</td>
  														</tr>
  													</tbody>
  												</table>
  												</td>
  											</tr>
  										</tbody>
  									</table>
  									</td>
  								</tr>
  							</tbody>
  						</table>
  						</td>
  					</tr>
  				</tbody>
  			</table>
  			</td>
  		</tr>
  	</tbody>
  </table>
  <!-- end of hero image and story --><!-- story 1 -->

  <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="100%">
  	<tbody>
  		<tr>
  			<td>
  			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
  				<tbody>
  					<tr>
  						<td width="100%">
  						<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
  							<tbody><!-- Spacing -->
  								<tr>
  									<td>
  									<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
  										<tbody>
  											<tr>
  												<td width="100%">
  												<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
  													<tbody>
  														<tr>
  															<td>
  															<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidthinner" width="650">
  																<tbody><!-- image -->
  																	<tr>
  																		<td align="center" class="devicewidthinner" width="100%">
  																		<div class="imgpop"><a href="#" target="_blank"><img alt="" border="0" class="blog" height="250" src="https://civicrm.org/sites/default/files/civicrm/custom/images/banner-image-650-250.png" style="display:block; border:none; outline:none; text-decoration:none; padding:0; line-height:0;" width="650" /></a></div>
  																		</td>
  																	</tr>
  																	<!-- /image --><!-- Spacing -->
  																	<tr>
  																		<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
  																	</tr>
  																	<!-- /Spacing -->
  																	<tr>
  																		<td style="font-family: Helvetica, arial, sans-serif; font-size: 18px;  text-align:left; line-height: 26px; padding:0 15px;"><a href="#" style="color:#89c66b;">Your Heading  Here</a></td>
  																	</tr>
  																	<!-- Spacing -->
  																	<tr>
  																		<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
  																	</tr>
  																	<!-- /Spacing --><!-- content -->
  																	<tr>
  																		<td style="padding:0 15px;">
  																		<p style="font-family: Helvetica, arial, sans-serif; font-size: 16px; color: #7a6e67; text-align:left; line-height: 26px; padding-bottom:10px;">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna </p>
  																		</td>
  																	</tr>
  																	<tr>
  																		<td class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 16px; font-weight:bold; color: #333333; text-align:left;line-height: 24px; padding-top:10px; padding-left:15px;"><a href="#" style="color:#136388;text-decoration:none;font-weight:bold;" target="_blank" title="read more">Read More</a></td>
  																	</tr>
  																	<!-- /button --><!-- Spacing -->
  																	<tr>
  																		<td height="20" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
  																	</tr>
  																	<!-- Spacing --><!-- end of content -->
  																</tbody>
  															</table>
  															</td>
  														</tr>
  													</tbody>
  												</table>
  												</td>
  											</tr>
  										</tbody>
  									</table>
  									</td>
  								</tr>
  							</tbody>
  						</table>
  						</td>
  					</tr>
  				</tbody>
  			</table>
  			</td>
  		</tr>
  	</tbody>
  </table>
  <!-- /story 2--><!-- banner1 -->

  <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="100%">
  	<tbody>
  		<tr>
  			<td>
  			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
  				<tbody>
  					<tr>
  						<td width="100%">
  						<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
  							<tbody><!-- Spacing -->
  								<tr>
  									<td>
  									<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
  										<tbody>
  											<tr>
  												<td width="100%">
  												<table align="center" bgcolor="#89c66b" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
  													<tbody>
  														<tr>
  															<td>
  															<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidthinner" width="650">
  																<tbody><!-- image -->
  																	<tr>
  																		<td align="center" class="devicewidthinner" width="100%">
  																		<div class="imgpop"><a href="#" target="_blank"><img alt="" border="0" class="blog" height="auto" src="https://civicrm.org/sites/default/files/civicrm/custom/images/banner-image-650-250.png" style="display:block; border:none; outline:none; text-decoration:none; padding:0; line-height:0;" width="650" /></a></div>
  																		</td>
  																	</tr>
  																	<!-- /image --><!-- content --><!-- Spacing -->
  																	<tr>
  																		<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
  																	</tr>
  																	<!-- /Spacing -->
  																	<tr>
  																		<td style="padding:15px;">
  																		<p style="font-family: Helvetica, arial, sans-serif; font-size: 16px; color: #f0f0f0; text-align:left; line-height: 26px; padding-bottom:10px;">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna </p>
  																		</td>
  																	</tr>
  																	<!-- /button --><!-- white button -->
  																	<tr>
  																		<td class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 16px; font-weight:bold; color: #333333; text-align:left;line-height: 24px; padding-top:10px; padding-bottom:10px; padding-left:15px;"><a href="#" style="color:#ffffff;text-decoration:none;font-weight:bold;" target="_blank" title="read more">Read More</a></td>
  																	</tr>
  																	<!-- /button --><!-- Spacing --><!-- end of content -->
  																</tbody>
  															</table>
  															</td>
  														</tr>
  													</tbody>
  												</table>
  												</td>
  											</tr>
  										</tbody>
  									</table>
  									</td>
  								</tr>
  							</tbody>
  						</table>
  						</td>
  					</tr>
  				</tbody>
  			</table>
  			</td>
  		</tr>
  	</tbody>
  </table>
  <!-- /banner 1--><!-- banner 2 -->

  <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="100%">
  	<tbody>
  		<tr>
  			<td>
  			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
  				<tbody>
  					<tr>
  						<td width="100%">
  						<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
  							<tbody><!-- Spacing -->
  								<tr>
  									<td>
  									<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
  										<tbody>
  											<tr>
  												<td width="100%">
  												<table align="center" bgcolor="#136388" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
  													<tbody>
  														<tr>
  															<td>
  															<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidthinner" width="650">
  																<tbody><!-- image -->
  																	<tr>
  																		<td align="center" class="devicewidthinner" width="100%">
  																		<div class="imgpop"><a href="#" target="_blank"><img alt="" border="0" class="blog" height="auto" src="https://civicrm.org/sites/default/files/civicrm/custom/images/banner-image-650-250.png" style="display:block; border:none; outline:none; text-decoration:none; padding:0; line-height:0;" width="650" /></a></div>
  																		</td>
  																	</tr>
  																	<!-- /image --><!-- content --><!-- Spacing -->
  																	<tr>
  																		<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
  																	</tr>
  																	<!-- /Spacing -->
  																	<tr>
  																		<td style="padding: 15px;">
  																		<p style="font-family: Helvetica, arial, sans-serif; font-size: 16px; color: #f0f0f0; text-align:left; line-height: 26px; padding-bottom:10px;">Remember to link the facebook and twitter links below to your pages!</p>
  																		</td>
  																	</tr>
  																	<!-- /button --><!-- white button -->
  																	<tr>
  																		<td class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 16px; font-weight:bold; color: #333333; text-align:left;line-height: 24px; padding-top:10px; padding-bottom:10px; padding-left:15px;"><a href="#" style="color:#ffffff;text-decoration:none;font-weight:bold;" target="_blank" title="read more">Read More</a></td>
  																	</tr>
  																	<!-- /button --><!-- Spacing --><!-- end of content -->
  																</tbody>
  															</table>
  															</td>
  														</tr>
  													</tbody>
  												</table>
  												</td>
  											</tr>
  										</tbody>
  									</table>
  									</td>
  								</tr>
  							</tbody>
  						</table>
  						</td>
  					</tr>
  				</tbody>
  			</table>
  			</td>
  		</tr>
  	</tbody>
  </table>
  <!-- /banner2 --><!-- footer -->

  <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="footer" width="100%">
  	<tbody>
  		<tr>
  			<td>
  			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
  				<tbody>
  					<tr>
  						<td width="100%">
  						<table align="center" bgcolor="#89c66b"  border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
  							<tbody><!-- Spacing -->
  								<tr>
  									<td height="10" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
  								</tr>
  								<!-- Spacing -->
  								<tr>
  									<td><!-- logo -->
  									<table align="left" border="0" cellpadding="0" cellspacing="0" width="250">
  										<tbody>
  											<tr>
  												<td width="20">&nbsp;</td>
  												<td align="left" height="40" width="250"><span style="font-family: Helvetica, arial, sans-serif; font-size: 13px; text-align:left; line-height: 26px; padding-bottom:10px;"><a href="{literal}{action.unsubscribeUrl}{/literal}" style="color: #f0f0f0; ">Unsubscribe | </a><a href="{literal}{action.subscribeUrl}{/literal}"  style="color: #f0f0f0;">Subscribe |</a> <a href="{literal}{action.optOutUrl}{/literal}" style="color: #f0f0f0;">Opt out</a></span></td>
  											</tr>
  											<tr>
  												<td width="20">&nbsp;</td>
  												<td align="left" height="40" width="250"><span style="font-family: Helvetica, arial, sans-serif; font-size: 13px; text-align:left; line-height: 26px; padding-bottom:10px; color: #f0f0f0;">{literal}{domain.address}{/literal}</span></td>
  											</tr>
  										</tbody>
  									</table>
  									<!-- end of logo --><!-- start of social icons -->

  									<table align="right" border="0" cellpadding="0" cellspacing="0" height="40" vaalign="middle" width="60">
  										<tbody>
  											<tr>
  												<td align="left" height="22" width="22">
  												<div class="imgpop"><a href="#" target="_blank"><img alt="" border="0" height="22" src="https://civicrm.org/sites/default/files/civicrm/custom/images/facebook.png" style="display:block; border:none; outline:none; text-decoration:none;" width="22" /> </a></div>
  												</td>
  												<td align="left" style="font-size:1px; line-height:1px;" width="10">&nbsp;</td>
  												<td align="right" height="22" width="22">
  												<div class="imgpop"><a href="#" target="_blank"><img alt="" border="0" height="22" src="https://civicrm.org/sites/default/files/civicrm/custom/images/twitter.png" style="display:block; border:none; outline:none; text-decoration:none;" width="22" /> </a></div>
  												</td>
  												<td align="left" style="font-size:1px; line-height:1px;" width="20">&nbsp;</td>
  											</tr>
  										</tbody>
  									</table>
  									<!-- end of social icons --></td>
  								</tr>
  								<!-- Spacing -->
  								<tr>
  									<td height="10" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
  								</tr>
  								<!-- Spacing -->
  							</tbody>
  						</table>
  						</td>
  					</tr>
  				</tbody>
  			</table>
  			</td>
  		</tr>
  	</tbody>
  </table>
  <!-- end of footer -->', NULL, 1, 0);
  
  INSERT INTO civicrm_msg_template
    (msg_title,      msg_subject,                  msg_text,                  msg_html,                  workflow_id,        is_default, is_reserved)
    VALUES
    ('Sample Responsive Design Newsletter - Two Column', 'Sample Responsive Design Newsletter - Two Column', '', '<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title></title>
    {literal}
    <style type="text/css">img {height: auto !important;}
             /* Client-specific Styles */
             #outlook a {padding:0;} /* Force Outlook to provide a "view in browser" menu link. */
             body{width:100% !important; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; margin:0; padding:0;}

             /* Prevent Webkit and Windows Mobile platforms from changing default font sizes, while not breaking desktop design. */
             .ExternalClass {width:100%;} /* Force Hotmail to display emails at full width */
             .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height: 100%;} /* Force Hotmail to display normal line spacing. */
             #backgroundTable {margin:0; padding:0; width:100% !important; line-height: 100% !important;}
             img {outline:none; text-decoration:none;border:none; -ms-interpolation-mode: bicubic;}
             a img {border:none;}
             .image_fix {display:block;}
             p {margin: 0px 0px !important;}
             table td {border-collapse: collapse;}
             table { border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; }
             a {color: #33b9ff;text-decoration: none;text-decoration:none!important;}
             /*STYLES*/
             table[class=full] { width: 100%; clear: both; }
             /*IPAD STYLES*/
             @media only screen and (max-width: 640px) {
             a[href^="tel"], a[href^="sms"] {
             text-decoration: none;
             color: #0a8cce; /* or whatever your want */
             pointer-events: none;
             cursor: default;
             }
             .mobile_link a[href^="tel"], .mobile_link a[href^="sms"] {
             text-decoration: default;
             color: #0a8cce !important;
             pointer-events: auto;
             cursor: default;
             }
             table[class=devicewidth] {width: 440px!important;text-align:center!important;}
             table[class=devicewidthmob] {width: 414px!important;text-align:center!important;}
             table[class=devicewidthinner] {width: 414px!important;text-align:center!important;}
             img[class=banner] {width: 440px!important;auto!important;}
             img[class=col2img] {width: 440px!important;height:auto!important;}
             table[class="cols3inner"] {width: 100px!important;}
             table[class="col3img"] {width: 131px!important;}
             img[class="col3img"] {width: 131px!important;height: auto!important;}
             table[class="removeMobile"]{width:10px!important;}
             img[class="blog"] {width: 440px!important;height: auto!important;}
             }

             /*IPHONE STYLES*/
             @media only screen and (max-width: 480px) {
             a[href^="tel"], a[href^="sms"] {
             text-decoration: none;
             color: #0a8cce; /* or whatever your want */
             pointer-events: none;
             cursor: default;
             }
             .mobile_link a[href^="tel"], .mobile_link a[href^="sms"] {
             text-decoration: default;
             color: #0a8cce !important; 
             pointer-events: auto;
             cursor: default;
             }
             table[class=devicewidth] {width: 280px!important;text-align:center!important;}
             table[class=devicewidthmob] {width: 260px!important;text-align:center!important;}
             table[class=devicewidthinner] {width: 260px!important;text-align:center!important;}
             img[class=banner] {width: 280px!important;height:100px!important;}
             img[class=col2img] {width: 280px!important;height:auto!important;}
             table[class="cols3inner"] {width: 260px!important;}
             img[class="col3img"] {width: 280px!important;height: auto!important;}
             table[class="col3img"] {width: 280px!important;}
             img[class="blog"] {width: 280px!important;auto!important;}
             td[class="padding-top-right15"]{padding:15px 15px 0 0 !important;}
             td[class="padding-right15"]{padding-right:15px !important;}
             }

    		 @media only screen and (max-device-width: 800px)
    { td[class="padding-top-right15"]{padding:15px 15px 0 0 !important;}
             td[class="padding-right15"]{padding-right:15px !important;}}		 
    		 @media only screen and (max-device-width: 769px) {
    			 .devicewidthmob {font-size:14px;}
    			 }

    			  @media only screen and (max-width: 640px) {
    				 .desktop-spacer {display:none !important;} 
    			  }
    </style>
    {/literal}
    <!-- Start of preheader --><!-- Start of preheader -->
    <table bgcolor="#0B4151" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" width="100%">
    	<tbody>
    		<tr>
    			<td>
    			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
    				<tbody>
    					<tr>
    						<td width="100%">
    						<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
    							<tbody><!-- Spacing -->
    								<tr>
    									<td height="20" width="100%">&nbsp;</td>
    								</tr>
    								<!-- Spacing -->
    								<tr>
    									<td>
    									<table align="left" border="0" cellpadding="0" cellspacing="0" class="devicewidthinner" width="360">
    										<tbody>
    											<tr>
    												<td align="left" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; line-height:120%; color: #f8f8f8;padding-left:15px;" valign="middle">Organization or Program Name Here</td>
    											</tr>
    										</tbody>
    									</table>

    									<table align="right" border="0" cellpadding="0" cellspacing="0" class="emhide" width="320">
    										<tbody>
    											<tr>
    												<td align="right" style="font-family: Helvetica, arial, sans-serif; font-size: 16px;color: #f8f8f8;padding-right:15px;" valign="middle">Month Year</td>
    											</tr>
    										</tbody>
    									</table>
    									</td>
    								</tr>
    								<!-- Spacing -->
    								<tr>
    									<td height="20" width="100%">&nbsp;</td>
    								</tr>
    								<!-- Spacing -->
    							</tbody>
    						</table>
    						</td>
    					</tr>
    				</tbody>
    			</table>
    			</td>
    		</tr>
    	</tbody>
    </table>
    <!-- End of preheader --><!-- start of logo -->

    <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="100%">
    	<tbody>
    		<tr>
    			<td>
    			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
    				<tbody>
    					<tr>
    						<td width="100%">
    						<table align="center" bgcolor="#f8f8f8" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
    							<tbody><!-- Spacing -->
    								<tr>
    									<td height="20" width="100%">
    									<table align="center" border="0" cellpadding="2" cellspacing="0" width="93%">
    										<tbody>
    											<tr>
    												<td rowspan="2" width="38%"><a href="#"><img border="0" src="https://civicrm.org/sites/default/files/civicrm/custom/images/top-logo_2.png" /></a></td>
    												<td align="right" width="62%">
    												<h6 class="collapse">&nbsp;</h6>
    												</td>
    											</tr>
    											<tr>
    												<td align="right">

    												</td>
    											</tr>
    										</tbody>
    									</table>
    									</td>
    								</tr>

    							</tbody>
    						</table>
    						</td>
    					</tr>
    				</tbody>
    			</table>
    			</td>
    		</tr>
    	</tbody>
    </table>
    <!-- end of logo --> <!-- hero story 1 -->

    <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="101%">
    	<tbody>
    		<tr>
    			<td>
    			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
    				<tbody>
    					<tr>
    						<td width="100%">
    						<table align="center" bgcolor="#f8f8f8" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
    							<tbody>
    								<tr>
    									<td>
    									<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
    										<tbody>
    											<tr>
    												<td width="100%">
    												<table align="center" bgcolor="#f8f8f8" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
    													<tbody><!-- /Spacing -->
    														<tr>
    															<td style="font-family: Helvetica, arial, sans-serif; font-size: 24px; color:#f8f8f8; text-align:left; line-height: 26px; padding:5px 15px; background-color: #80C457">Hero Story Heading</td>
    														</tr>
    														<!-- Spacing -->
    														<tr>
    															<td>
    															<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidthinner" width="700">
    																<tbody><!-- image -->
    																	<tr>
    																		<td align="center" class="devicewidthinner" width="100%">
    																		<div class="imgpop"><a href="#" target="_blank"><img alt="" border="0" class="blog" height="396" src="https://civicrm.org/sites/default/files/civicrm/custom/images/700x396.png" style="display:block; border:none; outline:none; text-decoration:none; padding:0; line-height:0;" width="700" /></a></div>
    																		</td>
    																	</tr>
    																	<!-- /image --><!-- Spacing -->
    																	<tr>
    																		<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
    																	</tr>
    																	<!-- /Spacing --><!-- hero story -->
    																	<tr>
    																		<td style="font-family: Helvetica, arial, sans-serif; font-size: 18px;  text-align:left; line-height: 26px; padding:0 15px;"><a href="#" style="color:#076187; text-decoration:none; " target="_blank">Subheading Here</a></td>
    																	</tr>
    																	<!-- Spacing -->
    																	<tr>
    																		<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
    																	</tr><!-- /Spacing -->
    																	<tr>
    																		<td style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 26px; padding:0 15px;"><span class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 24px;">Replace with your text and images, and remember to link the facebook and twitter links in the footer to your pages. Have fun!</span></td>
    																	</tr>

    <!-- Spacing -->
    																	<tr>
    																		<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
    																	</tr><!-- /Spacing -->

              <!-- /Spacing --><!-- /hero story -->

    																	<!-- Spacing -->                                                            <!-- Spacing -->



    																	<!-- Spacing --><!-- end of content -->
    																</tbody>
    															</table>
    															</td>
    														</tr>
    													</tbody>
    												</table>
    												</td>
    											</tr>
    										</tbody>
    									</table>
    									</td>
    								</tr>
    								<!-- Section Heading -->
    								<tr>
    									<td style="font-family: Helvetica, arial, sans-serif; font-size: 24px; color:#f8f8f8; text-align:left; line-height: 26px; padding:5px 15px; background-color: #80C457">Section Heading Here</td>
    								</tr>
    								<!-- /Section Heading -->
    							</tbody>
    						</table>
    						</td>
    					</tr>
    				</tbody>
    			</table>
    			</td>
    		</tr>
    	</tbody>
    </table>
    <!-- /hero story 1 --><!-- story one -->

    <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="100%">
    	<tbody>
    		<tr>
    			<td>
    			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
    				<tbody>
    					<tr>
    						<td width="100%">
    						<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
    							<tbody><!-- Spacing -->
    								<tr>
    									<td class="desktop-spacer" height="20" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
    								</tr>
    								<!-- Spacing -->
    								<tr>
    									<td>
    									<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="660">
    										<tbody>
    											<tr>
    												<td><!-- Start of left column -->
    												<table align="left" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="330">
    													<tbody><!-- image -->
    														<tr>
    															<td align="center" class="devicewidth" height="150" valign="top" width="330"><a href="#"><img alt="" border="0" class="col2img" src="https://civicrm.org/sites/default/files/civicrm/custom/images/330x150.png" style="display:block; border:none; outline:none; text-decoration:none; display:block;" width="330" /></a></td>
    														</tr>
    														<!-- /image -->
    													</tbody>
    												</table>
    												<!-- end of left column --><!-- spacing for mobile devices-->

    												<table align="left" border="0" cellpadding="0" cellspacing="0" class="mobilespacing">
    													<tbody>
    														<tr>
    															<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
    														</tr>
    													</tbody>
    												</table>
    												<!-- end of for mobile devices--><!-- start of right column -->

    												<table align="right" border="0" cellpadding="0" cellspacing="0" class="devicewidthmob" width="310">
    													<tbody>
    														<tr>
    															<td class="padding-top-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 18px; text-align:left; line-height: 24px;"><a href="#" style="color:#076187; text-decoration:none; " target="_blank">Heading Here</a><a href="#" style="color:#076187; text-decoration:none;" target="_blank" title="CiviCRM helps keep the “City Beautiful” Movement”going strong"></a></td>
    														</tr>
    														<!-- end of title --><!-- Spacing -->
    														<tr>
    															<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
    														</tr>
    														<!-- /Spacing --><!-- content -->
    														<tr>
    															<td class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 24px;"><span class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 24px;">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
                                                                tempor incididunt ut labore et dolore magna </span></td>
    														</tr>
    														<tr>
    															<td class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; font-weight:bold; color: #333333; text-align:left;line-height: 24px; padding-top:10px;"><a href="#" style="color:#80C457;text-decoration:none;font-weight:bold;" target="_blank" title="CiviCRM helps keep the “City Beautiful” Movement”going strong">Read More</a></td>
    														</tr>
    														<!-- /button --><!-- end of content -->
    													</tbody>
    												</table>
    												<!-- end of right column --></td>
    											</tr>
    										</tbody>
    									</table>
    									</td>
    								</tr>
    								<!-- Spacing -->
    								<tr>
    									<td height="20" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
    								</tr>
    							</tbody>
    						</table>
    						</td>
    					</tr>
    				</tbody>
    			</table>
    			</td>
    		</tr>
    	</tbody>
    </table>
    <!-- /story one -->
    <!-- story two -->

    <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="100%">
    	<tbody>
    		<tr>
    			<td>
    			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
    				<tbody>
    					<tr>
    						<td width="100%">
    						<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
    							<tbody><!-- Spacing -->
    								<tr>
    									<td bgcolor="#076187" height="0" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
    								</tr>
    								<!-- Spacing --><!-- Spacing -->
    								<tr>
    									<td class="desktop-spacer" height="20" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
    								</tr>
    								<!-- Spacing -->
    								<tr>
    									<td>
    									<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="660">
    										<tbody>
    											<tr>
    												<td><!-- Start of left column -->
    												<table align="left" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="330">
    													<tbody><!-- image -->
    														<tr>
    															<td align="center" class="devicewidth" height="150" valign="top" width="330"><a href="#"><img alt="" border="0" class="col2img" src="https://civicrm.org/sites/default/files/civicrm/custom/images/330x150.png" style="display:block; border:none; outline:none; text-decoration:none; display:block;" width="330" /></a></td>
    														</tr>
    														<!-- /image -->
    													</tbody>
    												</table>
    												<!-- end of left column --><!-- spacing for mobile devices-->

    												<table align="left" border="0" cellpadding="0" cellspacing="0" class="mobilespacing">
    													<tbody>
    														<tr>
    															<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
    														</tr>
    													</tbody>
    												</table>
    												<!-- end of for mobile devices--><!-- start of right column -->

    												<table align="right" border="0" cellpadding="0" cellspacing="0" class="devicewidthmob" width="310">
    													<tbody>
    														<tr>
    															<td class="padding-top-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 18px; text-align:left; line-height: 24px;"><a href="#" style="color:#076187; text-decoration:none; " target="_blank">Heading Here</a><a href="#" style="color:#076187; text-decoration:none;" target="_blank" title="How CiviCRM will take Tribodar Eco Learning Center to another level"></a></td>
    														</tr>
    														<!-- end of title --><!-- Spacing -->
    														<tr>
    															<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
    														</tr>
    														<!-- /Spacing --><!-- content -->
    														<tr>
    															<td class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 24px;"><span class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 24px;">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
                                                                tempor incididunt ut labore et dolore magna </span></td>
    														</tr>
    														<tr>
    															<td class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; font-weight:bold; color: #333333; text-align:left;line-height: 24px; padding-top:10px;"><a href="#" style="color:#80C457;text-decoration:none;font-weight:bold;" target="_blank" title="How CiviCRM will take Tribodar Eco Learning Center to another level">Read More</a></td>
    														</tr>
    														<!-- /button --><!-- end of content -->
    													</tbody>
    												</table>
    												<!-- end of right column --></td>
    											</tr>
    										</tbody>
    									</table>
    									</td>
    								</tr>
    								<!-- Spacing -->
    								<tr>
    									<td height="20" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
    								</tr>
    							</tbody>
    						</table>
    						</td>
    					</tr>
    				</tbody>
    			</table>
    			</td>
    		</tr>
    	</tbody>
    </table>
    <!-- /story two --><!-- story three -->

    <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="100%">
    	<tbody>
    		<tr>
    			<td>
    			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
    				<tbody>
    					<tr>
    						<td width="100%">
    						<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
    							<tbody><!-- Spacing -->
    								<tr>
    									<td bgcolor="#076187" height="0" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
    								</tr>
    								<!-- Spacing --><!-- Spacing -->
    								<tr>
    									<td height="20" class="desktop-spacer" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
    								</tr>
    								<!-- Spacing -->
    								<tr>
    									<td>
    									<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="660">
    										<tbody>
    											<tr>
    												<td><!-- Start of left column -->
    												<table align="left" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="330">
    													<tbody><!-- image -->
    														<tr>
    															<td align="center" class="devicewidth" height="150" valign="top" width="330"><a href="#"><img alt="" border="0" class="col2img" src="https://civicrm.org/sites/default/files/civicrm/custom/images/330x150.png" style="display:block; border:none; outline:none; text-decoration:none; display:block;" width="330" /></a></td>
    														</tr>
    														<!-- /image -->
    													</tbody>
    												</table>
    												<!-- end of left column --><!-- spacing for mobile devices-->

    												<table align="left" border="0" cellpadding="0" cellspacing="0" class="mobilespacing">
    													<tbody>
    														<tr>
    															<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
    														</tr>
    													</tbody>
    												</table>
    												<!-- end of for mobile devices--><!-- start of right column -->

    												<table align="right" border="0" cellpadding="0" cellspacing="0" class="devicewidthmob" width="310">
    													<tbody>
    														<tr>
    															<td class="padding-top-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 18px;  text-align:left; line-height: 24px;"><a href="#" style="color:#076187; text-decoration:none; " target="_blank">Heading Here</a><a href="#" style="color:#076187; text-decoration:none;" target="_blank" title="CiviCRM provides a soup-to-nuts open-source solution for Friends of the San Pedro River"></a></td>
    														</tr>
    														<!-- end of title --><!-- Spacing -->
    														<tr>
    															<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
    														</tr>
    														<!-- /Spacing --><!-- content -->
    														<tr>
    															<td class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 24px;"><span class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 24px;">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
                                                                tempor incididunt ut labore et dolore magna </span></td>
    														</tr>
    														<tr>
    															<td style="font-family: Helvetica, arial, sans-serif; font-size: 14px; font-weight:bold; color: #333333; text-align:left;line-height: 24px; padding-top:10px;"><a href="#" style="color:#80C457;text-decoration:none;font-weight:bold;" target="_blank" title="CiviCRM provides a soup-to-nuts open-source solution for Friends of the San Pedro River">Read More</a></td>
    														</tr>
    														<!-- /button --><!-- end of content -->
    													</tbody>
    												</table>
    												<!-- end of right column --></td>
    											</tr>
    										</tbody>
    									</table>
    									</td>
    								</tr>
    								<!-- Spacing -->
    								<tr>
    									<td height="20" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
    								</tr>
    								<!-- Spacing -->
    							</tbody>
    						</table>
    						</td>
    					</tr>
    				</tbody>
    			</table>
    			</td>
    		</tr>
    	</tbody>
    </table>
    <!-- /story three -->





    <!-- story four -->
    <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="100%">
    	<tbody>
    		<tr>
    			<td>
    			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
    				<tbody>
    					<tr>
    						<td width="100%">
    						<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
    							<tbody>
                                <!-- Spacing -->
    								<tr>
    									<td bgcolor="#076187" height="0" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
    								</tr>
    								<!-- Spacing -->
                                <!-- Spacing -->
    								<tr>
    									<td class="desktop-spacer" height="20" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
    								</tr>
    								<!-- Spacing -->
    								<tr>
    									<td>
    									<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="660">
    										<tbody>
    											<tr>
    												<td><!-- Start of left column -->
    												<table align="left" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="330">
    													<tbody><!-- image -->
    														<tr>
    															<td align="center" class="devicewidth" height="150" valign="top" width="330"><a href="#"><img alt="" border="0" class="col2img" src="https://civicrm.org/sites/default/files/civicrm/custom/images/330x150.png" style="display:block; border:none; outline:none; text-decoration:none; display:block;" width="330" /></a></td>
    														</tr>
    														<!-- /image -->
    													</tbody>
    												</table>
    												<!-- end of left column --><!-- spacing for mobile devices-->

    												<table align="left" border="0" cellpadding="0" cellspacing="0" class="mobilespacing">
    													<tbody>
    														<tr>
    															<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
    														</tr>
    													</tbody>
    												</table>
    												<!-- end of for mobile devices--><!-- start of right column -->

    												<table align="right" border="0" cellpadding="0" cellspacing="0" class="devicewidthmob" width="310">
    													<tbody>
    														<tr>
    															<td class="padding-top-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 18px;text-align:left; line-height: 24px;"><a href="#" style="color:#076187; text-decoration:none; " target="_blank">Heading Here</a><a href="#" style="color:#076187; text-decoration:none;" target="_blank" title="Google Summer of Code"></a></td>
    														</tr>
    														<!-- end of title --><!-- Spacing -->
    														<tr>
    															<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
    														</tr>
    														<!-- /Spacing --><!-- content -->
    														<tr>
    															<td class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 24px;"><span class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 24px;">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
                                                                tempor incididunt ut labore et dolore magna </span></td>
    														</tr>
    														<tr>
    															<td style="font-family: Helvetica, arial, sans-serif; font-size: 14px; font-weight:bold; color: #333333; text-align:left;line-height: 24px; padding-top:10px;"><a href="#" style="color:#80C457;text-decoration:none;font-weight:bold;" target="_blank" title="Google Summer of Code">Read More</a></td>
    														</tr>
    														<!-- /button --><!-- end of content -->
    													</tbody>
    												</table>
    												<!-- end of right column --></td>
    											</tr>
    										</tbody>
    									</table>
    									</td>
    								</tr>
    								<!-- Spacing -->
    								<tr>
    									<td height="20" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
    								</tr>
    								<!-- Spacing -->
    							</tbody>
    						</table>
    						</td>
    					</tr>
    				</tbody>
    			</table>
    			</td>
    		</tr>
    	</tbody>
    </table>
    <!-- /story four -->

    <!-- footer -->

    <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" width="100%">
    	<tbody>
    		<tr>
    			<td>
    			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
    				<tbody><!-- Spacing -->
    					<tr>
    						<td bgcolor="#076187" height="10" style="font-size:1px; line-height:1px; padding-top:10px; mso-line-height-rule: exactly;">&nbsp;</td>
    					</tr>
    					<!-- Spacing -->
    					<tr>
    						<td width="100%">
    						<table align="center" bgcolor="#076187" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
    							<tbody>
    								<tr>
    									<td><!-- logo -->
    									<table align="right" border="0" cellpadding="0" cellspacing="0" height="40" vaalign="middle" width="60">
    										<tbody>
    											<tr>
    												<td align="left" height="22" width="22">
    												<div class="imgpop"><a href="#"><img alt="" border="0" height="22" src="https://civicrm.org/sites/default/files/civicrm/custom/images/facebook.png" style="display:block; border:none; outline:none; text-decoration:none;" width="22" /></a> </div>
    											  </td>
    												<td align="left" style="font-size:1px; line-height:1px;" width="10">&nbsp;</td>
    												<td align="right" height="22" width="22">
    												<div class="imgpop"><a href="#" target="_blank"><img alt="" border="0" height="22" src="https://civicrm.org/sites/default/files/civicrm/custom/images/twitter.png" style="display:block; border:none; outline:none; text-decoration:none;" width="22" /> </a></div>
    												</td>
    												<td align="left" style="font-size:1px; line-height:1px;" width="20">&nbsp;</td>
    											</tr>
    										</tbody>
    									</table>
    									<!-- end of logo --><!-- start of social icons -->

    									<table align="right" border="0" cellpadding="0" cellspacing="0" height="40" vaalign="middle" width="120">
    										<tbody>
    											<tr>
    												<td valign="top" width="120">
    												<div style="width:110px;"><img alt="Sent with CiviMail" height="100" src="http://civicrm.org/sites/civicrm.org/files/civicrm/custom/image/newsletter-stamp.png" style="display:block; outline:none; text-decoration:none; -ms-interpolation-mode: bicubic;" width="100" /></div>
    												</td>
    											</tr>
    										</tbody>
    									</table>
    									<!-- end of social icons --></td>
    								</tr>
    								<!-- Spacing -->
    								<tr>
    									<td height="10" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
    								</tr>
    								<!-- Spacing -->
    							</tbody>
    						</table>
    						</td>
    					</tr>
    				</tbody>
    			</table>
    			</td>
    		</tr>
    	</tbody>
    </table>
    <!-- End of footer --><!-- Start of postfooter -->

    <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="100%">
    	<tbody>
    		<tr>
    			<td>
    			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
    				<tbody>
    					<tr>
    						<td width="100%">
    						<table align="center" bgcolor="#f8f8f8" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
    							<tbody><!-- Spacing -->
    								<tr bgcolor="#80C457">
    									<td height="10" width="100%">&nbsp;</td>
    								</tr>
    								<!-- Spacing -->
    								<tr bgcolor="#80C457">
    									<td align="center" st-content="viewonline" style="font-family: Helvetica, arial, sans-serif; font-size: 13px;color: #7a6e67;text-align:center;" valign="middle"><a href="#" style="color:#f8f8f8; text-decoration:none; font-family:Tahoma, Verdana, Arial, Sans-serif;">Go to our website</a><span>&nbsp;|&nbsp;</span><a href="{literal}{action.unsubscribeUrl}{/literal}" style="color:#f8f8f8; text-decoration:none; font-family:Tahoma, Verdana, Arial, Sans-serif;">Unsubscribe from this mailing</a><span>&nbsp;|&nbsp;</span><a href="{literal}{action.subscribeUrl}{/literal}" style="color:#f8f8f8; text-decoration:none; font-family:Tahoma, Verdana, Arial, Sans-serif;">Subscribe to this mailing</a><span>&nbsp;|&nbsp;</span><a href="{literal}{action.optOutUrl}{/literal}" style="color:#f8f8f8; text-decoration:none; font-family:Tahoma, Verdana, Arial, Sans-serif;">Opt out of all mailings</a></td>
    								</tr>
    								<tr bgcolor="#80C457">
    									<td align="center" st-content="viewonline" style="font-family: Helvetica, arial, sans-serif; font-size: 13px;color: #7a6e67;text-align:center;" valign="middle"><span style="color:#f8f8f8; text-decoration:none; font-family:Tahoma, Verdana, Arial, Sans-serif;">{literal}{domain.address}{/literal}</span></td>
    								</tr>
    								<!-- Spacing -->
    								<tr>
    									<td bgcolor="#80C457" height="10" width="100%">&nbsp;</td>
    								</tr>
    								<!-- Spacing -->
    							</tbody>
    						</table>
    						</td>
    					</tr>
    				</tbody>
    			</table>
    			</td>
    		</tr>
    	</tbody>
    </table>
    <!-- End of footer -->', NULL, 1, 0);