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

-- Structuur van  tabel komparu_dev._entity wordt geschreven
DROP TABLE IF EXISTS `_entity`;
CREATE TABLE IF NOT EXISTS `_entity` (
  `uuid` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id` varchar(255) NOT NULL,
  `version` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`uuid`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumpen data van tabel komparu_dev._entity: ~0 rows (ongeveer)
/*!40000 ALTER TABLE `_entity` DISABLE KEYS */;
INSERT INTO `_entity` (`uuid`, `id`, `version`) VALUES
	('test1', 'resource1', 1);
/*!40000 ALTER TABLE `_entity` ENABLE KEYS */;

-- Structuur van  tabel komparu_dev._field wordt geschreven
DROP TABLE IF EXISTS `_field`;
CREATE TABLE IF NOT EXISTS `_field` (
  `uuid` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id` varchar(255) NOT NULL,
  `entity` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT '0',
  `order` int(11) DEFAULT '0',
  `type` char(50) NOT NULL,
  `required` tinyint(4) NOT NULL,
  `collection` tinyint(4) NOT NULL,
  PRIMARY KEY (`uuid`),
  KEY `version` (`version`),
  KEY `entity` (`entity`),
  KEY `id` (`id`),
  KEY `order` (`order`),
  KEY `required` (`required`),
  KEY `collection` (`collection`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumpen data van tabel komparu_dev._field: ~5 rows (ongeveer)
/*!40000 ALTER TABLE `_field` DISABLE KEYS */;
INSERT INTO `_field` (`uuid`, `id`, `entity`, `name`, `version`, `order`, `type`, `required`, `collection`) VALUES
	('id1', 'field1', 'resource1', 'name', 1, 1, 'string', 1, 0),
	('id2', 'field2', 'resource1', 'label_old', 1, 2, 'string', 0, 0),
	('id3', 'field2', 'resource1', 'label', 2, 2, 'string', 0, 0),
	('id4', 'field3', 'resource1', 'id', 1, 0, 'string', 0, 0),
	('id5', 'field4', 'resource1', 'uses', 1, 5, 'json', 0, 0);
/*!40000 ALTER TABLE `_field` ENABLE KEYS */;

-- Structuur van  tabel komparu_dev._record wordt geschreven
DROP TABLE IF EXISTS `_record`;
CREATE TABLE IF NOT EXISTS `_record` (
  `uuid` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id` varchar(255) NOT NULL,
  `entity` varchar(255) NOT NULL,
  `version` int(11) NOT NULL DEFAULT '0',
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  `conditions` varchar(1024) DEFAULT NULL,
  `conditions_active` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`uuid`),
  KEY `entity` (`entity`),
  KEY `id` (`id`),
  KEY `deleted` (`deleted`),
  KEY `conditions` (`conditions`),
  KEY `conditions_active` (`conditions_active`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumpen data van tabel komparu_dev._record: ~5 rows (ongeveer)
/*!40000 ALTER TABLE `_record` DISABLE KEYS */;
INSERT INTO `_record` (`uuid`, `id`, `entity`, `version`, `deleted`, `conditions`, `conditions_active`) VALUES
	('id1', 'record1', 'resource1', 1, 0, NULL, 0),
	('id2', 'record2', 'resource1', 1, 0, NULL, 0),
	('id3', 'record2', 'resource1', 2, 1, NULL, 0),
	('id4', 'record1', 'resource1', 2, 0, NULL, 0),
	('id5', 'record1', 'resource1', 3, 0, '{"lang": "nl"}', 1);
/*!40000 ALTER TABLE `_record` ENABLE KEYS */;

-- Structuur van  tabel komparu_dev._value wordt geschreven
DROP TABLE IF EXISTS `_value`;
CREATE TABLE IF NOT EXISTS `_value` (
  `uuid` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `record` varchar(255) NOT NULL,
  `field` varchar(255) NOT NULL,
  `version` int(11) NOT NULL,
  `value` text NOT NULL,
  `conditions` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`uuid`),
  KEY `field` (`field`),
  KEY `version` (`version`),
  KEY `record` (`record`),
  KEY `conditions` (`conditions`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumpen data van tabel komparu_dev._value: ~9 rows (ongeveer)
/*!40000 ALTER TABLE `_value` DISABLE KEYS */;
INSERT INTO `_value` (`uuid`, `record`, `field`, `version`, `value`, `conditions`) VALUES
	('id1', 'record1', 'field1', 1, 'test', NULL),
	('id2', 'record1', 'field2', 1, '123', NULL),
	('id3', 'record1', 'field3', 1, 'id1', NULL),
	('id4', 'record1', 'field2', 2, '456', NULL),
	('id5', 'record2', 'field1', 1, 'foo', NULL),
	('id6', 'record2', 'field2', 1, 'bar', NULL),
	('id7', 'record2', 'field3', 1, 'id2', NULL),
	('id8', 'record1', 'field2', 3, 'Nederlandse vertaling', '{"lang": "nl"}'),
	('id9', 'record1', 'field4', 1, '["first", "second"]', NULL);
/*!40000 ALTER TABLE `_value` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
