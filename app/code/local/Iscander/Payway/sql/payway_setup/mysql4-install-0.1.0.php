<?php

$installer = $this;

$installer->startSetup();

$installer->run("

-- DROP TABLE IF EXISTS {$this->getTable('payway_api_debug')};
CREATE TABLE {$this->getTable('payway_api_debug')} (
  `payway_id` int(11) unsigned NOT NULL auto_increment,
  `created_time` datetime NULL,  
  `request_body` text,
  `response_body` text,
  PRIMARY KEY (`payway_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");

$installer->endSetup(); 