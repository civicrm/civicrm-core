-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 04, 2021 at 06:30 AM
-- Server version: 5.7.32
-- PHP Version: 7.4.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS=0;

--
-- Database: `civicrm_tests_dev`
--

--
-- Dumping data for table `civicrm_value_extension_tng55`
--

CREATE TABLE `civicrm_value_extension_tng55` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
