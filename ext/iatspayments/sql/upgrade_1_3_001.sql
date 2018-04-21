CREATE TABLE `civicrm_iats_ukdd_validate` (
  `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'UK DirectDebit validation Id',
  `customer_code` varchar(255) NOT NULL COMMENT 'Customer code returned from iATS',
  `acheft_reference_num` varchar(255) NOT NULL COMMENT 'Reference number returned from iATS',
  `cid` int(10) unsigned DEFAULT '0' COMMENT 'CiviCRM contact id',
  `recur_id` int(10) unsigned DEFAULT '0' COMMENT 'CiviCRM recurring_contribution table id',
  `validated` int(10) unsigned DEFAULT '0' COMMENT 'Status id of 0 or 1 (after validation)',
  `validated_datetime` datetime COMMENT 'Date time of validation',
  `xml` longtext COMMENT 'XML response to initial validation request',
  PRIMARY KEY ( `id` ),
  KEY (`customer_code`),
  KEY (`acheft_reference_num`),
  KEY (`cid`),
  KEY (`recur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table to store UK Direct Debit validation information';
