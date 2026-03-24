<?php

// Fallback for direct access URL (when GLPI_ROOT is not set)
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(dirname(dirname(dirname(__FILE__)))));
}

include_once GLPI_ROOT . '/inc/includes.php';

// redireciona para a mesma funcionalidade de importação
include GLPI_ROOT . '/plugins/justificativas/front/index.php';
