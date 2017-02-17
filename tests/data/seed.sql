-- --------------------------------------------------------
-- Host:                         172.16.0.2
-- Server versie:                5.7.17 - MySQL Community Server (GPL)
-- Server OS:                    Linux
-- HeidiSQL Versie:              9.4.0.5125
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Structuur van  tabel komparu_dev._field wordt geschreven
CREATE TABLE IF NOT EXISTS `_field` (
  `_id` varchar(255) NOT NULL,
  `key` varchar(255) NOT NULL,
  `resource` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT '0',
  `order` int(11) DEFAULT '0',
  PRIMARY KEY (`_id`),
  KEY `version` (`version`),
  KEY `resource` (`resource`),
  KEY `key` (`key`),
  KEY `order` (`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumpen data van tabel komparu_dev._field: ~4 rows (ongeveer)
DELETE FROM `_field`;
/*!40000 ALTER TABLE `_field` DISABLE KEYS */;
INSERT INTO `_field` (`_id`, `key`, `resource`, `name`, `version`, `order`) VALUES
	('id1', 'field1', 'resource1', 'name', 1, 1),
	('id2', 'field2', 'resource1', 'label_old', 1, 2),
	('id3', 'field2', 'resource1', 'label', 2, 2),
	('id4', 'field3', 'resource1', 'id', 1, 0);
/*!40000 ALTER TABLE `_field` ENABLE KEYS */;

-- Structuur van  tabel komparu_dev._record wordt geschreven
CREATE TABLE IF NOT EXISTS `_record` (
  `_id` varchar(255) NOT NULL,
  `resource` varchar(255) NOT NULL,
  `key` varchar(255) NOT NULL,
  `version` int(11) NOT NULL DEFAULT '0',
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`_id`),
  KEY `resource` (`resource`),
  KEY `key` (`key`),
  KEY `deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumpen data van tabel komparu_dev._record: ~2 rows (ongeveer)
DELETE FROM `_record`;
/*!40000 ALTER TABLE `_record` DISABLE KEYS */;
INSERT INTO `_record` (`_id`, `resource`, `key`, `version`, `deleted`) VALUES
	('id1', 'resource1', 'record1', 1, 0),
	('id2', 'resource1', 'record2', 1, 0),
	('id3', 'resource1', 'record2', 2, 1);
/*!40000 ALTER TABLE `_record` ENABLE KEYS */;

-- Structuur van  tabel komparu_dev._resource wordt geschreven
CREATE TABLE IF NOT EXISTS `_resource` (
  `_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `version` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumpen data van tabel komparu_dev._resource: ~0 rows (ongeveer)
DELETE FROM `_resource`;
/*!40000 ALTER TABLE `_resource` DISABLE KEYS */;
INSERT INTO `_resource` (`_id`, `name`, `version`) VALUES
	('test1', 'resource1', 1);
/*!40000 ALTER TABLE `_resource` ENABLE KEYS */;

-- Structuur van  tabel komparu_dev._value wordt geschreven
CREATE TABLE IF NOT EXISTS `_value` (
  `_id` varchar(255) NOT NULL,
  `record` varchar(255) NOT NULL,
  `field` varchar(255) NOT NULL,
  `version` int(11) NOT NULL,
  `value` text NOT NULL,
  `type` char(50) NOT NULL,
  PRIMARY KEY (`_id`),
  KEY `field` (`field`),
  KEY `version` (`version`),
  KEY `record` (`record`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumpen data van tabel komparu_dev._value: ~7 rows (ongeveer)
DELETE FROM `_value`;
/*!40000 ALTER TABLE `_value` DISABLE KEYS */;
INSERT INTO `_value` (`_id`, `record`, `field`, `version`, `value`, `type`) VALUES
	('id1', 'record1', 'field1', 1, 'test', 'string'),
	('id2', 'record1', 'field2', 1, '123', 'integer'),
	('id3', 'record1', 'field3', 1, 'id1', 'string'),
	('id4', 'record1', 'field2', 2, '456', 'integer'),
	('id5', 'record2', 'field1', 1, 'foo', 'string'),
	('id6', 'record2', 'field2', 1, 'bar', 'string'),
	('id7', 'record2', 'field3', 1, 'id2', 'string');
/*!40000 ALTER TABLE `_value` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
