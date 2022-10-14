-- MariaDB dump 10.19  Distrib 10.5.15-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: db    Database: wordpress
-- ------------------------------------------------------
-- Server version	10.3.30-MariaDB-1:10.3.30+maria~focal

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `wp_groups_capability`
--

DROP TABLE IF EXISTS `wp_groups_capability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_groups_capability` (
  `capability_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `capability` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `class` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `object` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`capability_id`),
  UNIQUE KEY `capability` (`capability`(100)),
  KEY `capability_kco` (`capability`(20),`class`(20),`object`(20))
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_groups_capability`
--

LOCK TABLES `wp_groups_capability` WRITE;
/*!40000 ALTER TABLE `wp_groups_capability` DISABLE KEYS */;
INSERT INTO `wp_groups_capability` VALUES (1,'switch_themes',NULL,NULL,NULL,NULL),(2,'edit_themes',NULL,NULL,NULL,NULL),(3,'activate_plugins',NULL,NULL,NULL,NULL),(4,'edit_plugins',NULL,NULL,NULL,NULL),(5,'edit_users',NULL,NULL,NULL,NULL),(6,'edit_files',NULL,NULL,NULL,NULL),(7,'manage_options',NULL,NULL,NULL,NULL),(8,'moderate_comments',NULL,NULL,NULL,NULL),(9,'manage_categories',NULL,NULL,NULL,NULL),(10,'manage_links',NULL,NULL,NULL,NULL),(11,'upload_files',NULL,NULL,NULL,NULL),(12,'import',NULL,NULL,NULL,NULL),(13,'unfiltered_html',NULL,NULL,NULL,NULL),(14,'edit_posts',NULL,NULL,NULL,NULL),(15,'edit_others_posts',NULL,NULL,NULL,NULL),(16,'edit_published_posts',NULL,NULL,NULL,NULL),(17,'publish_posts',NULL,NULL,NULL,NULL),(18,'edit_pages',NULL,NULL,NULL,NULL),(19,'read',NULL,NULL,NULL,NULL),(20,'level_10',NULL,NULL,NULL,NULL),(21,'level_9',NULL,NULL,NULL,NULL),(22,'level_8',NULL,NULL,NULL,NULL),(23,'level_7',NULL,NULL,NULL,NULL),(24,'level_6',NULL,NULL,NULL,NULL),(25,'level_5',NULL,NULL,NULL,NULL),(26,'level_4',NULL,NULL,NULL,NULL),(27,'level_3',NULL,NULL,NULL,NULL),(28,'level_2',NULL,NULL,NULL,NULL),(29,'level_1',NULL,NULL,NULL,NULL),(30,'level_0',NULL,NULL,NULL,NULL),(31,'edit_others_pages',NULL,NULL,NULL,NULL),(32,'edit_published_pages',NULL,NULL,NULL,NULL),(33,'publish_pages',NULL,NULL,NULL,NULL),(34,'delete_pages',NULL,NULL,NULL,NULL),(35,'delete_others_pages',NULL,NULL,NULL,NULL),(36,'delete_published_pages',NULL,NULL,NULL,NULL),(37,'delete_posts',NULL,NULL,NULL,NULL),(38,'delete_others_posts',NULL,NULL,NULL,NULL),(39,'delete_published_posts',NULL,NULL,NULL,NULL),(40,'delete_private_posts',NULL,NULL,NULL,NULL),(41,'edit_private_posts',NULL,NULL,NULL,NULL),(42,'read_private_posts',NULL,NULL,NULL,NULL),(43,'delete_private_pages',NULL,NULL,NULL,NULL),(44,'edit_private_pages',NULL,NULL,NULL,NULL),(45,'read_private_pages',NULL,NULL,NULL,NULL),(46,'delete_users',NULL,NULL,NULL,NULL),(47,'create_users',NULL,NULL,NULL,NULL),(48,'unfiltered_upload',NULL,NULL,NULL,NULL),(49,'edit_dashboard',NULL,NULL,NULL,NULL),(50,'update_plugins',NULL,NULL,NULL,NULL),(51,'delete_plugins',NULL,NULL,NULL,NULL),(52,'install_plugins',NULL,NULL,NULL,NULL),(53,'update_themes',NULL,NULL,NULL,NULL),(54,'install_themes',NULL,NULL,NULL,NULL),(55,'update_core',NULL,NULL,NULL,NULL),(56,'list_users',NULL,NULL,NULL,NULL),(57,'remove_users',NULL,NULL,NULL,NULL),(58,'promote_users',NULL,NULL,NULL,NULL),(59,'edit_theme_options',NULL,NULL,NULL,NULL),(60,'delete_themes',NULL,NULL,NULL,NULL),(61,'export',NULL,NULL,NULL,NULL),(62,'groups_access',NULL,NULL,NULL,NULL),(63,'groups_admin_groups',NULL,NULL,NULL,NULL),(64,'groups_admin_options',NULL,NULL,NULL,NULL),(65,'groups_restrict_access',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `wp_groups_capability` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_groups_group`
--

DROP TABLE IF EXISTS `wp_groups_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_groups_group` (
  `group_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) DEFAULT NULL,
  `creator_id` bigint(20) DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  PRIMARY KEY (`group_id`),
  UNIQUE KEY `group_n` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_groups_group`
--

LOCK TABLES `wp_groups_group` WRITE;
/*!40000 ALTER TABLE `wp_groups_group` DISABLE KEYS */;
INSERT INTO `wp_groups_group` VALUES 
(1,NULL,0,'2022-10-10 13:49:11','Registered',NULL),

(2,NULL,1,'2022-10-14 13:18:36','Ballincollig Training Centre',NULL),
(3,2,1,'2022-10-10 13:53:48','Cork City',''),
(4,2,1,'2022-10-10 13:55:53','Cork County Council',''),
(5,2,1,'2022-10-10 13:59:33','Kerry County Council',''),

(6,NULL,1,'2022-10-14 13:21:39','Ballycoolin Training Centre',NULL),
(7,6,1,'2022-10-10 14:15:59','Dublin City Council',NULL),
(8,6,1,'2022-10-10 14:15:59','Dun Laoghaire County Council',NULL),
(9,6,1,'2022-10-10 14:15:59','Fingal County Council',NULL),
(10,6,1,'2022-10-10 14:15:59','Kildare County Council',NULL),
(11,6,1,'2022-10-10 14:15:59','Louth County Council',NULL),
(12,6,1,'2022-10-10 14:15:59','Meath County Council',NULL),
(13,6,1,'2022-10-10 14:15:59','South Dublin County Council',NULL),
(14,6,1,'2022-10-10 14:15:59','Westmeath County Council',NULL),
(15,6,1,'2022-10-10 14:15:59','Wicklow County Council',NULL),

(16,NULL,1,'2022-10-14 13:22:00','Castlebar Training Centre',NULL),
(17,16,1,'2022-10-10 14:15:59','Galway City',NULL),
(18,16,1,'2022-10-10 14:15:59','Galway County',NULL),
(19,16,1,'2022-10-10 14:15:59','Leitrim County Council',NULL),
(20,16,1,'2022-10-10 14:15:59','Longford County Council',NULL),
(21,16,1,'2022-10-10 14:15:59','Mayo County Council',NULL),
(22,16,1,'2022-10-10 14:15:59','Roscommon County Council',NULL),

(23,NULL,1,'2022-10-14 13:22:23','Roscrea Training Centre',NULL),
(24,23,1,'2022-10-10 14:15:59','Carlow County Council',NULL),
(25,23,1,'2022-10-10 14:15:59','Clare County Council',NULL),
(26,23,1,'2022-10-10 14:15:59','Kilkenny County Council',NULL),
(27,23,1,'2022-10-10 14:15:59','Laois County Council',NULL),
(28,23,1,'2022-10-10 14:15:59','Limerick City & County Council',NULL),
(29,23,1,'2022-10-10 14:15:59','Offaly County Council',NULL),
(30,23,1,'2022-10-10 14:15:59','Tipperary County Council',NULL),
(31,23,1,'2022-10-10 14:15:59','Waterford City & County Council',NULL),
(32,23,1,'2022-10-10 14:15:59','Wexford County Council',NULL),

(33,NULL,1,'2022-10-14 13:22:40','Stranorlar Training Centre',NULL);
(34,33,1,'2022-10-10 14:15:59','Cavan County Council',NULL),
(35,33,1,'2022-10-10 14:15:59','Donegal County Council',NULL),
(36,33,1,'2022-10-10 14:15:59','Monaghan County Council',NULL),
(37,33,1,'2022-10-10 14:15:59','Sligo County Council',NULL);

/*!40000 ALTER TABLE `wp_groups_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_groups_group_capability`
--

DROP TABLE IF EXISTS `wp_groups_group_capability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_groups_group_capability` (
  `group_id` bigint(20) unsigned NOT NULL,
  `capability_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`group_id`,`capability_id`),
  KEY `group_capability_cg` (`capability_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_groups_group_capability`
--

LOCK TABLES `wp_groups_group_capability` WRITE;
/*!40000 ALTER TABLE `wp_groups_group_capability` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_groups_group_capability` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_groups_user_capability`
--

DROP TABLE IF EXISTS `wp_groups_user_capability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_groups_user_capability` (
  `user_id` bigint(20) unsigned NOT NULL,
  `capability_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`capability_id`),
  KEY `user_capability_cu` (`capability_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_groups_user_capability`
--

LOCK TABLES `wp_groups_user_capability` WRITE;
/*!40000 ALTER TABLE `wp_groups_user_capability` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_groups_user_capability` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_groups_user_group`
--

DROP TABLE IF EXISTS `wp_groups_user_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_groups_user_group` (
  `user_id` bigint(20) unsigned NOT NULL,
  `group_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`group_id`),
  KEY `user_group_gu` (`group_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_groups_user_group`
--

LOCK TABLES `wp_groups_user_group` WRITE;
/*!40000 ALTER TABLE `wp_groups_user_group` DISABLE KEYS */;
INSERT INTO `wp_groups_user_group` VALUES (1,1),(1,64);
/*!40000 ALTER TABLE `wp_groups_user_group` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-10-14 13:29:46
