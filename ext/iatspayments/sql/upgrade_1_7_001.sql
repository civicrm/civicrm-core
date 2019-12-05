/* placeholder for 1.7 upgrade */
/* TODO: 
 * 1. remove any remaining UKDD entries 
 * */
CREATE TABLE IF NOT EXISTS `civicrm_iats_faps_journal` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'CiviCRM Journal Id',
  `transactionId` int unsigned DEFAULT NULL COMMENT 'FAPS Transaction Id',
  `authCode` varchar(255) NOT NULL COMMENT 'Authentication code',
  `isAch` boolean DEFAULT '0' COMMENT 'Transaction type: is ACH',
  `cardType` varchar(255) NOT NULL COMMENT 'Card Type',
  `processorId` varchar(255) NOT NULL COMMENT 'Unique merchant account identifier',
  `cimRefNumber` varchar(255) NOT NULL COMMENT 'CIM Reference Number',
  `orderId` varchar(255) COMMENT 'Order Id = Invoice Number',
  `transDateAndTime` datetime NOT NULL COMMENT 'DateTime',
  `amount` decimal(20,2) COMMENT 'Amount',
  `authResponse` varchar(255) COMMENT 'Response',
  `currency` varchar(3) COMMENT 'Currency',
  `status_id` int(10) unsigned DEFAULT '0' COMMENT 'Status of the payment',
  `financial_trxn_id` int(10) unsigned DEFAULT '0' COMMENT 'Foreign key into CiviCRM financial trxn table id',
  `cid` int(10) unsigned DEFAULT '0' COMMENT 'CiviCRM contact id',
  `contribution_id` int(10) unsigned DEFAULT '0' COMMENT 'CiviCRM contribution table id',
  `recur_id` int(10) unsigned DEFAULT '0' COMMENT 'CiviCRM recurring_contribution table id',
  `verify_datetime` datetime COMMENT 'Date time of verification',
  PRIMARY KEY ( `id` ),
  UNIQUE KEY (`transactionId`,`processorId`,`authResponse`),
  KEY (`authCode`),
  KEY (`isAch`),
  KEY (`cardType`),
  KEY (`authResponse`),
  KEY (`orderId`),
  KEY (`transDateAndTime`),
  KEY (`financial_trxn_id`),
  KEY (`contribution_id`),
  KEY (`verify_datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table of iATS/FAPS transactions imported via the query api.'
