-- CRM-7088 giving respect to 'gotv campaign contacts' permission.
UPDATE   civicrm_navigation 
   SET   permission = CONCAT( permission, ',gotv campaign contacts' )
 WHERE   name in ( 'Other', 'Campaigns', 'Voter Listing' );

-- CRM-7151
SELECT @domainID        := min(id) FROM civicrm_domain;
SELECT @reportlastID    := id FROM civicrm_navigation where name = 'Reports';
SELECT @nav_max_weight  := MAX(ROUND(weight)) from civicrm_navigation WHERE parent_id = @reportlastID;

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES 
    ( @domainID, 'Mail Bounce Report', 'Mailing/bounce', 'Bounce Report for mailings', 'access CiviMail', '{literal}a:30:{s:6:"fields";a:4:{s:2:"id";s:1:"1";s:10:"first_name";s:1:"1";s:9:"last_name";s:1:"1";s:5:"email";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:9:"source_op";s:3:"has";s:12:"source_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:15:"mailing_name_op";s:2:"eq";s:18:"mailing_name_value";s:0:"";s:19:"bounce_type_name_op";s:2:"eq";s:22:"bounce_type_name_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"custom_1_op";s:2:"in";s:14:"custom_1_value";a:0:{}s:11:"custom_2_op";s:2:"in";s:14:"custom_2_value";a:0:{}s:17:"custom_3_relative";s:1:"0";s:13:"custom_3_from";s:0:"";s:11:"custom_3_to";s:0:"";s:11:"description";s:26:"Bounce Report for mailings";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:9:"domain_id";i:1;}{/literal}');

SET @instanceID:=LAST_INSERT_ID( );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'), '{ts escape="sql"}Mail Bounce Report{/ts}', '{literal}Mail Bounce Report {/literal}', 'access CiviMail', '',@reportlastID, '1', NULL,@nav_max_weight+1  );

UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES 
    ( @domainID, 'Mail Summary Report', 'Mailing/summary','Summary statistics for mailings','access CiviMail','{literal}a:21:{s:6:"fields";a:1:{s:4:"name";s:1:"1";}s:15:"is_completed_op";s:2:"eq";s:18:"is_completed_value";s:1:"1";s:9:"status_op";s:3:"has";s:12:"status_value";s:8:"Complete";s:11:"is_test_min";s:0:"";s:11:"is_test_max";s:0:"";s:10:"is_test_op";s:3:"lte";s:13:"is_test_value";s:1:"0";s:19:"start_date_relative";s:9:"this.year";s:15:"start_date_from";s:0:"";s:13:"start_date_to";s:0:"";s:17:"end_date_relative";s:9:"this.year";s:13:"end_date_from";s:0:"";s:11:"end_date_to";s:0:"";s:11:"description";s:31:"Summary statistics for mailings";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:9:"domain_id";i:1;}{/literal}');

SET @instanceID:=LAST_INSERT_ID( );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'), '{ts escape="sql"}Mail Summary Report{/ts}', '{literal}Mail Summary Report{/literal}', 'access CiviMail', '',@reportlastID, '1', NULL,@nav_max_weight+2 );

UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES 
    ( @domainID, 'Mail Opened Report', 'Mailing/opened', 'Display contacts who opened emails from a mailing', 'access CiviMail', '{literal}a:28:{s:6:"fields";a:4:{s:2:"id";s:1:"1";s:10:"first_name";s:1:"1";s:9:"last_name";s:1:"1";s:5:"email";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:9:"source_op";s:3:"has";s:12:"source_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:15:"mailing_name_op";s:2:"eq";s:18:"mailing_name_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"custom_1_op";s:2:"in";s:14:"custom_1_value";a:0:{}s:11:"custom_2_op";s:2:"in";s:14:"custom_2_value";a:0:{}s:17:"custom_3_relative";s:1:"0";s:13:"custom_3_from";s:0:"";s:11:"custom_3_to";s:0:"";s:11:"description";s:49:"Display contacts who opened emails from a mailing";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:9:"domain_id";i:1;}{/literal}');

SET @instanceID:=LAST_INSERT_ID( );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'), '{ts escape="sql"}Mail Opened Report{/ts}', '{literal}Mail Opened Report{/literal}', 'access CiviMail', '',@reportlastID, '1', NULL, @nav_max_weight+3 );

UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;
INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES 
    ( @domainID, 'Mail Clickthrough Report', 'Mailing/clicks', 'Display clicks from each mailing', 'access CiviMail', '{literal}a:28:{s:6:"fields";a:4:{s:2:"id";s:1:"1";s:10:"first_name";s:1:"1";s:9:"last_name";s:1:"1";s:5:"email";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:9:"source_op";s:3:"has";s:12:"source_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:15:"mailing_name_op";s:2:"eq";s:18:"mailing_name_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"custom_1_op";s:2:"in";s:14:"custom_1_value";a:0:{}s:11:"custom_2_op";s:2:"in";s:14:"custom_2_value";a:0:{}s:17:"custom_3_relative";s:1:"0";s:13:"custom_3_from";s:0:"";s:11:"custom_3_to";s:0:"";s:11:"description";s:32:"Display clicks from each mailing";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:9:"domain_id";i:1;}{/literal}');

SET @instanceID:=LAST_INSERT_ID( );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'), '{ts escape="sql"}Mail Clickthrough Report{/ts}', '{literal}Mail Clickthrough Report{/literal}', 'access CiviMail', '',@reportlastID, '1', NULL, @nav_max_weight+4 );

UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;

-- CRM-7123
SELECT @option_group_id_languages := MAX(id) FROM civicrm_option_group WHERE name = 'languages';
UPDATE civicrm_option_value SET name = 'af_ZA' WHERE value = 'af' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'sq_AL' WHERE value = 'sq' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ar_EG' WHERE value = 'ar' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'bg_BG' WHERE value = 'bg' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ca_ES' WHERE value = 'ca' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'zh_CN' WHERE value = 'zh' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'cs_CZ' WHERE value = 'cs' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'da_DK' WHERE value = 'da' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'nl_NL' WHERE value = 'nl' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'en_US' WHERE value = 'en' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'et_EE' WHERE value = 'et' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'fi_FI' WHERE value = 'fi' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'fr_FR' WHERE value = 'fr' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'de_DE' WHERE value = 'de' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'el_GR' WHERE value = 'el' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'he_IL' WHERE value = 'he' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'hi_IN' WHERE value = 'hi' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'hu_HU' WHERE value = 'hu' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'id_ID' WHERE value = 'id' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'it_IT' WHERE value = 'it' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ja_JP' WHERE value = 'ja' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'km_KH' WHERE value = 'km' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'lt_LT' WHERE value = 'lt' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'no_NO' WHERE value = 'no' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'pl_PL' WHERE value = 'pl' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'pt_PT' WHERE value = 'pt' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ro_RO' WHERE value = 'ro' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ru_RU' WHERE value = 'ru' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'sk_SK' WHERE value = 'sk' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'sl_SI' WHERE value = 'sl' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'es_ES' WHERE value = 'es' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'sv_SE' WHERE value = 'sv' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'te_IN' WHERE value = 'te' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'th_TH' WHERE value = 'th' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'tr_TR' WHERE value = 'tr' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'vi_VN' WHERE value = 'vi' AND option_group_id = @option_group_id_languages;

UPDATE civicrm_option_value SET name = 'ab_GE' WHERE value = 'ab' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'aa_ET' WHERE value = 'aa' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ak_GH' WHERE value = 'ak' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'am_ET' WHERE value = 'am' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'an_ES' WHERE value = 'an' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'hy_AM' WHERE value = 'hy' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'as_IN' WHERE value = 'as' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'av_RU' WHERE value = 'av' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ae_XX' WHERE value = 'ae' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ay_BO' WHERE value = 'ay' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'az_AZ' WHERE value = 'az' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'bm_ML' WHERE value = 'bm' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ba_RU' WHERE value = 'ba' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'eu_ES' WHERE value = 'eu' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'be_BY' WHERE value = 'be' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'bn_BD' WHERE value = 'bn' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'bh_IN' WHERE value = 'bh' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'bi_VU' WHERE value = 'bi' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'bs_BA' WHERE value = 'bs' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'br_FR' WHERE value = 'br' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'my_MM' WHERE value = 'my' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ch_GU' WHERE value = 'ch' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ny_MW' WHERE value = 'ny' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'cv_RU' WHERE value = 'cv' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'kw_GB' WHERE value = 'kw' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'co_FR' WHERE value = 'co' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'cr_CA' WHERE value = 'cr' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'hr_HR' WHERE value = 'hr' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'dv_MV' WHERE value = 'dv' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'dz_BT' WHERE value = 'dz' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'eo_XX' WHERE value = 'eo' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ee_GH' WHERE value = 'ee' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'fo_FO' WHERE value = 'fo' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'fj_FJ' WHERE value = 'fj' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ff_SN' WHERE value = 'ff' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'gl_ES' WHERE value = 'gl' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ka_GE' WHERE value = 'ka' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'gn_PY' WHERE value = 'gn' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'gu_IN' WHERE value = 'gu' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ht_HT' WHERE value = 'ht' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ha_NG' WHERE value = 'ha' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'hz_NA' WHERE value = 'hz' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ho_PG' WHERE value = 'ho' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ia_XX' WHERE value = 'ia' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ie_XX' WHERE value = 'ie' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ga_IE' WHERE value = 'ga' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ig_NG' WHERE value = 'ig' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ik_US' WHERE value = 'ik' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'io_XX' WHERE value = 'io' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'is_IS' WHERE value = 'is' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'iu_CA' WHERE value = 'iu' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'jv_ID' WHERE value = 'jv' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'kl_GL' WHERE value = 'kl' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'kn_IN' WHERE value = 'kn' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'kr_NE' WHERE value = 'kr' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ks_IN' WHERE value = 'ks' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'kk_KZ' WHERE value = 'kk' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ki_KE' WHERE value = 'ki' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'rw_RW' WHERE value = 'rw' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ky_KG' WHERE value = 'ky' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'kv_RU' WHERE value = 'kv' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'kg_CD' WHERE value = 'kg' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ko_KR' WHERE value = 'ko' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ku_IQ' WHERE value = 'ku' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'kj_NA' WHERE value = 'kj' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'la_VA' WHERE value = 'la' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'lb_LU' WHERE value = 'lb' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'lg_UG' WHERE value = 'lg' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'li_NL' WHERE value = 'li' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ln_CD' WHERE value = 'ln' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'lo_LA' WHERE value = 'lo' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'lu_CD' WHERE value = 'lu' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'lv_LV' WHERE value = 'lv' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'gv_IM' WHERE value = 'gv' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'mk_MK' WHERE value = 'mk' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'mg_MG' WHERE value = 'mg' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ms_MY' WHERE value = 'ms' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ml_IN' WHERE value = 'ml' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'mt_MT' WHERE value = 'mt' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'mi_NZ' WHERE value = 'mi' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'mr_IN' WHERE value = 'mr' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'mh_MH' WHERE value = 'mh' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'mn_MN' WHERE value = 'mn' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'na_NR' WHERE value = 'na' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'nv_US' WHERE value = 'nv' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'nb_NO' WHERE value = 'nb' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'nd_ZW' WHERE value = 'nd' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ne_NP' WHERE value = 'ne' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ng_NA' WHERE value = 'ng' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'nn_NO' WHERE value = 'nn' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ii_CN' WHERE value = 'ii' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'nr_ZA' WHERE value = 'nr' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'oc_FR' WHERE value = 'oc' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'oj_CA' WHERE value = 'oj' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'cu_BG' WHERE value = 'cu' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'om_ET' WHERE value = 'om' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'or_IN' WHERE value = 'or' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'os_GE' WHERE value = 'os' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'pa_IN' WHERE value = 'pa' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'pi_KH' WHERE value = 'pi' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'fa_IR' WHERE value = 'fa' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ps_AF' WHERE value = 'ps' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'qu_PE' WHERE value = 'qu' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'rm_CH' WHERE value = 'rm' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'rn_BI' WHERE value = 'rn' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'sa_IN' WHERE value = 'sa' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'sc_IT' WHERE value = 'sc' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'sd_IN' WHERE value = 'sd' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'se_NO' WHERE value = 'se' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'sm_WS' WHERE value = 'sm' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'sg_CF' WHERE value = 'sg' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'sr_RS' WHERE value = 'sr' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'gd_GB' WHERE value = 'gd' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'sn_ZW' WHERE value = 'sn' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'si_LK' WHERE value = 'si' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'so_SO' WHERE value = 'so' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'st_ZA' WHERE value = 'st' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'su_ID' WHERE value = 'su' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'sw_TZ' WHERE value = 'sw' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ss_ZA' WHERE value = 'ss' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ta_IN' WHERE value = 'ta' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'tg_TJ' WHERE value = 'tg' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ti_ET' WHERE value = 'ti' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'bo_CN' WHERE value = 'bo' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'tk_TM' WHERE value = 'tk' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'tl_PH' WHERE value = 'tl' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'tn_ZA' WHERE value = 'tn' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'to_TO' WHERE value = 'to' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ts_ZA' WHERE value = 'ts' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'tt_RU' WHERE value = 'tt' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'tw_GH' WHERE value = 'tw' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ty_PF' WHERE value = 'ty' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ug_CN' WHERE value = 'ug' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'uk_UA' WHERE value = 'uk' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'ur_PK' WHERE value = 'ur' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'uz_UZ' WHERE value = 'uz' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 've_ZA' WHERE value = 've' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'vo_XX' WHERE value = 'vo' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'wa_BE' WHERE value = 'wa' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'cy_GB' WHERE value = 'cy' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'wo_SN' WHERE value = 'wo' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'fy_NL' WHERE value = 'fy' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'xh_ZA' WHERE value = 'xh' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'yi_US' WHERE value = 'yi' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'yo_NG' WHERE value = 'yo' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'za_CN' WHERE value = 'za' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET name = 'zu_ZA' WHERE value = 'zu' AND option_group_id = @option_group_id_languages;

UPDATE civicrm_option_value SET {localize field='label'}label = 'Chinese (China)'           {/localize} WHERE name = 'zh_CN' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET {localize field='label'}label = 'English (United States)'   {/localize} WHERE name = 'en_US' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET {localize field='label'}label = 'French (France)'           {/localize} WHERE name = 'fr_FR' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET {localize field='label'}label = 'Portuguese (Portugal)'     {/localize} WHERE name = 'pt_PT' AND option_group_id = @option_group_id_languages;
UPDATE civicrm_option_value SET {localize field='label'}label = 'Spanish; Castilian (Spain)'{/localize} WHERE name = 'es_ES' AND option_group_id = @option_group_id_languages;

SELECT @weight := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_languages;
INSERT INTO civicrm_option_value
  (option_group_id,            name,    value, {localize field='label'}label{/localize},           weight) VALUES
  (@option_group_id_languages, 'zh_TW', 'zh',  {localize}'Chinese (Taiwan)'{/localize},            @weight := @weight + 1),
  (@option_group_id_languages, 'en_AU', 'en',  {localize}'English (Australia)'{/localize},         @weight := @weight + 1),
  (@option_group_id_languages, 'en_CA', 'en',  {localize}'English (Canada)'{/localize},            @weight := @weight + 1),
  (@option_group_id_languages, 'en_GB', 'en',  {localize}'English (United Kingdom)'{/localize},    @weight := @weight + 1),
  (@option_group_id_languages, 'fr_CA', 'fr',  {localize}'French (Canada)'{/localize},             @weight := @weight + 1),
  (@option_group_id_languages, 'pt_BR', 'pt',  {localize}'Portuguese (Brazil)'{/localize},         @weight := @weight + 1),
  (@option_group_id_languages, 'es_MX', 'es',  {localize}'Spanish; Castilian (Mexico)'{/localize}, @weight := @weight + 1);

-- CRM-7119: switch civicrm_contact.preferred_language to the relevant xx_YY codes (special-casing language variants first)
UPDATE civicrm_contact SET preferred_language = 'en_US' WHERE preferred_language = 'en';
UPDATE civicrm_contact SET preferred_language = 'es_ES' WHERE preferred_language = 'es';
UPDATE civicrm_contact SET preferred_language = 'fr_FR' WHERE preferred_language = 'fr';
UPDATE civicrm_contact SET preferred_language = 'pt_PT' WHERE preferred_language = 'pt';
UPDATE civicrm_contact SET preferred_language = 'zh_CN' WHERE preferred_language = 'zh';
UPDATE civicrm_contact SET preferred_language = (SELECT name FROM civicrm_option_value WHERE value = preferred_language AND option_group_id = @option_group_id_languages LIMIT 1) WHERE LENGTH(preferred_language) = 2;

-- add logging report templates
SELECT @option_group_id_report := MAX(id) FROM civicrm_option_group WHERE name = 'report_template';
SELECT @weight := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_languages;
INSERT INTO civicrm_option_value
  (option_group_id,         {localize field='label'}label{/localize},                value,                     name,                                     weight,                 {localize field='description'}description{/localize},                                         is_active) VALUES
  (@option_group_id_report, {localize}'Contact Logging Report (Summary)'{/localize}, 'logging/contact/summary', 'CRM_Report_Form_Contact_LoggingSummary', @weight := @weight + 1, {localize}'Contact modification report for the logging infrastructure (summary).'{/localize}, 0),
  (@option_group_id_report, {localize}'Contact Logging Report (Detail)'{/localize},  'logging/contact/detail',  'CRM_Report_Form_Contact_LoggingDetail',  @weight := @weight + 1, {localize}'Contact modification report for the logging infrastructure (detail).'{/localize},  0);
