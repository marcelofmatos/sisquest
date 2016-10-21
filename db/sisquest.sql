-- MySQL dump 10.13  Distrib 5.5.52, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: sisquest
-- ------------------------------------------------------
-- Server version	5.5.52-0ubuntu0.12.04.1

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
-- Current Database: `sisquest`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `sisquest` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `sisquest`;

--
-- Table structure for table `campos`
--

DROP TABLE IF EXISTS `campos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campos` (
  `idcampo` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID Campo',
  `idpergunta` int(11) NOT NULL COMMENT 'ID Pergunta',
  `tipo` varchar(20) NOT NULL COMMENT 'Tipo',
  `nome` varchar(50) NOT NULL COMMENT 'Nome',
  `rotulo` varchar(255) NOT NULL COMMENT 'Rótulo',
  `valor` varchar(255) DEFAULT NULL COMMENT 'Valor',
  `iddep` int(11) COMMENT 'ID Denpendência',
  `params` text NOT NULL COMMENT 'Parâmetros',
  `ordem` int(11) COMMENT 'Ordem',
  PRIMARY KEY (`idcampo`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Campos de perguntas';
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `dados`
--

DROP TABLE IF EXISTS `dados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dados` (
  `idvalor` int(11) NOT NULL AUTO_INCREMENT,
  `idresposta` int(11) NOT NULL,
  `idpergunta` int(11) NOT NULL,
  `idcampo` int(11) NOT NULL,
  `valor` text NOT NULL,
  PRIMARY KEY (`idvalor`),
  KEY `idcampo` (`idcampo`,`idresposta`,`valor`(20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Respostas dos questionários';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `grupos`
--

DROP TABLE IF EXISTS `grupos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grupos` (
  `idgrupo` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID Grupo',
  `idquest` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL COMMENT 'Título',
  `descricao` text NOT NULL COMMENT 'Descrição',
  `ordem` int(11) COMMENT 'Ordem',
  PRIMARY KEY (`idgrupo`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Grupos de perguntas';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `perguntas`
--

DROP TABLE IF EXISTS `perguntas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perguntas` (
  `idpergunta` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID Pergunta',
  `idquest` int(11) NOT NULL COMMENT 'ID Questionário',
  `idgrupo` int(11) NOT NULL COMMENT 'ID Grupo',
  `identificador` varchar(10) NOT NULL COMMENT 'Identificador',
  `texto` text NOT NULL COMMENT 'Texto',
  `iddep` int(11) COMMENT 'ID Denpendência',
  `ordem` int(11) COMMENT 'Ordem',
  PRIMARY KEY (`idpergunta`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Perguntas';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `questionarios`
--

DROP TABLE IF EXISTS `questionarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `questionarios` (
  `idquest` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID Questionário',
  `titulo` varchar(255) NOT NULL COMMENT 'Título',
  `descricao` text NOT NULL COMMENT 'Descrição',
  `ativo` tinyint(1) NOT NULL COMMENT 'Ativo',
  PRIMARY KEY (`idquest`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Questionários';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `respostas`
--

DROP TABLE IF EXISTS `respostas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `respostas` (
  `idresposta` int(11) NOT NULL AUTO_INCREMENT,
  `idquest` int(11) NOT NULL COMMENT 'ID Questionário',
  `usuario` varchar(255) NOT NULL COMMENT 'Usuário',
  `data` datetime NOT NULL COMMENT 'Data de Cadastro',
  PRIMARY KEY (`idresposta`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Respostas dos questionários';
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
