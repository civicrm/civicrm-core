-- FIXME: the final release version is uncertain -- could 4.2.0 or 4.2.1; make sure this fixed before merging
-- CRM-10660
CREATE TABLE `civicrm_managed` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Surrogate Key',
     `module` varchar(127) NOT NULL   COMMENT 'Name of the module which declared this object',
     `name` varchar(127)    COMMENT 'Symbolic name used by the module to identify the object',
     `entity_type` varchar(64) NOT NULL   COMMENT 'API entity type',
     `entity_id` int unsigned NOT NULL   COMMENT 'Foreign key to the referenced item.',
    PRIMARY KEY ( `id` )
 
    ,     INDEX `UI_managed_module_name`(
        `module`
      , `name`
  )
  ,     INDEX `UI_managed_entity`(
        `entity_type`
      , `entity_id`
  )
  
 
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;
