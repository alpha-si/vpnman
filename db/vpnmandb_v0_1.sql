CREATE DATABASE  IF NOT EXISTS `vpnmandb` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `vpnmandb`;
-- MySQL dump 10.13  Distrib 5.6.13, for Win32 (x86)
--
-- Host: 127.0.0.1    Database: vpnmandb
-- ------------------------------------------------------
-- Server version	5.5.27

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL,
  `passwd` varchar(40) NOT NULL,
  `description` text,
  `hw_serial` text,
  `type` enum('UNKWOWN','CLIENT','NODE') NOT NULL DEFAULT 'UNKWOWN',
  `status` enum('UNKWOWN','CONNECTING','ESTABLISHED','DISCONNECTED') NOT NULL DEFAULT 'UNKWOWN',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `coordinates` varchar(45) DEFAULT NULL,
  `links` varchar(45) NOT NULL,
  `auth_key` text,
  `auth_crt` text,
  `auth_csr` text,
  `auth_type` enum('PASS_ONLY','CERT_ONLY','CERT_PASS') NOT NULL DEFAULT 'CERT_ONLY',
  `vpn_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC COMMENT='VPN users';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config` (
  `param_name` varchar(64) NOT NULL,
  `param_value` text NOT NULL,
  `param_descr` text,
  PRIMARY KEY (`param_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config`
--

LOCK TABLES `config` WRITE;
/*!40000 ALTER TABLE `config` DISABLE KEYS */;
INSERT INTO `config` VALUES ('CA_CRT_FILE','/etc/openvpn/ca.crt','Pathname of certification autoriry certificate file'),('CA_KEY_FILE','/etc/openvpn/ca.key','Pathname of certification autoriry private key file'),('CA_KEY_PASSPHRASE','','Passphrase of certification autoriry private key'),('SERVER_ADDR','localhost','Domain name or ip address of openvpn server'),('SERVER_COUNTRY_CODE','IT','Country code of VPNMAN server'),('SERVER_EMAIL','',NULL),('SERVER_LOCALITY','','City/Locality of VPNMAN server'),('SERVER_ORG_NAME','','Organization name for VPNMAN server'),('SERVER_ORG_UNIT','','Organization unit for VPNMAN server'),('SERVER_STATE_PROV','','State/Provence of VPNMAN server'),('VPN_ROOT_PATH','/etc/openvpn/','Pathname of vpn configuration directory');
/*!40000 ALTER TABLE `config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `connection_history`
--

DROP TABLE IF EXISTS `connection_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `connection_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `start_time` timestamp NULL DEFAULT NULL COMMENT 'time at which client connected',
  `end_time` timestamp NULL DEFAULT NULL COMMENT 'time at which client disconnected =\ntime_unix + time_duration',
  `bytes_received` bigint(20) unsigned DEFAULT NULL COMMENT 'Total number of bytes received from client during VPN session',
  `bytes_sent` bigint(20) unsigned DEFAULT NULL COMMENT 'Total number of bytes sent to client during VPN session',
  `trusted_ip` varchar(20) DEFAULT NULL,
  `trusted_port` smallint(5) unsigned DEFAULT NULL COMMENT 'port the client connected from',
  `ifconfig_pool_remote_ip` varchar(20) DEFAULT NULL,
  `cid` int(10) unsigned DEFAULT NULL,
  `kid` int(10) unsigned DEFAULT NULL,
  `vpn_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id_idx` (`user_id`),
  CONSTRAINT `fk_connection_history_user_id` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='History of connections from clients';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `connection_history`
--

LOCK TABLES `connection_history` WRITE;
/*!40000 ALTER TABLE `connection_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `connection_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `networks`
--

DROP TABLE IF EXISTS `networks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `networks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `network` varchar(20) NOT NULL,
  `netmask` varchar(20) NOT NULL,
  `description` text,
  `mapped_to` varchar(29) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `vpn_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id_idx` (`user_id`),
  CONSTRAINT `fk_networks_user_id` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Networks connected to vpn users';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `networks`
--

LOCK TABLES `networks` WRITE;
/*!40000 ALTER TABLE `networks` DISABLE KEYS */;
/*!40000 ALTER TABLE `networks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `server_info`
--

DROP TABLE IF EXISTS `server_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `server_info` (
  `attribute` varchar(45) NOT NULL,
  `value` varchar(45) NOT NULL,
  `vpn_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`vpn_id`,`attribute`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `server_info`
--

LOCK TABLES `server_info` WRITE;
/*!40000 ALTER TABLE `server_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `server_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user2vpn`
--

DROP TABLE IF EXISTS `user2vpn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user2vpn` (
  `user_id` int(11) NOT NULL,
  `vpn_id` int(11) NOT NULL,
  `role` enum('USER','ADMIN','MANAGER') NOT NULL DEFAULT 'USER',
  PRIMARY KEY (`user_id`,`vpn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user2vpn`
--

LOCK TABLES `user2vpn` WRITE;
/*!40000 ALTER TABLE `user2vpn` DISABLE KEYS */;
/*!40000 ALTER TABLE `user2vpn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(45) DEFAULT NULL,
  `password` varchar(45) DEFAULT NULL,
  `role` enum('USER','MANAGER','ADMIN') DEFAULT 'USER',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username_UNIQUE` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','alpha0000','ADMIN');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vpn`
--

DROP TABLE IF EXISTS `vpn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vpn` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(45) DEFAULT NULL,
  `srv_port` smallint(5) unsigned NOT NULL,
  `mng_port` smallint(5) unsigned NOT NULL,
  `template` tinyint(4) DEFAULT '0',
  `srv_cfg_file` text,
  `proto_type` enum('tcp','udp') NOT NULL DEFAULT 'udp',
  `auth_type` enum('PASS_ONLY','CERT_ONLY','CERT_PASS') NOT NULL DEFAULT 'CERT_PASS',
  `org_name` varchar(45) NOT NULL,
  `org_unit` varchar(45) NOT NULL,
  `org_mail` varchar(45) NOT NULL,
  `org_country` varchar(45) NOT NULL,
  `org_prov` varchar(45) NOT NULL,
  `org_city` varchar(45) NOT NULL,
  `key_file` text NOT NULL,
  `crt_file` text NOT NULL,
  `dh_file` text NOT NULL,
  `net_addr` varchar(20) NOT NULL,
  `net_mask` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vpn`
--

LOCK TABLES `vpn` WRITE;
/*!40000 ALTER TABLE `vpn` DISABLE KEYS */;
/*!40000 ALTER TABLE `vpn` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-03-18 17:36:22
