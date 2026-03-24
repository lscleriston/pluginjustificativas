<?php

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', dirname(dirname(dirname(dirname(__FILE__)))));
}

// Script for manual installation of database table (mainly for GLPI 11 compatibility)
// Usage:
// php -f plugins/justificativas/install/install.php

require_once GLPI_ROOT . '/inc/includes.php';

global $DB;

$sqlFile = GLPI_ROOT . '/plugins/justificativas/install/mysql/plugin_justificativas_entries.sql';
if (!file_exists($sqlFile)) {
   echo "SQL file not found: {$sqlFile}\n";
   exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
   echo "Failed to read SQL file\n";
   exit(1);
}

$statements = preg_split('/;\s*\n/', $sql, -1, PREG_SPLIT_NO_EMPTY);
foreach ($statements as $statement) {
   $statement = trim($statement);
   if ($statement === '') {
      continue;
   }
   $DB->query($statement);
}

echo "Tables glpi_plugin_justificativas_tickets, glpi_plugin_justificativas_ligacoes, glpi_plugin_justificativas_zabbix created/verified.\n";
