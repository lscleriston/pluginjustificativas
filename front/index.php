<?php

// Fallback for direct access URL
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(dirname(dirname(dirname(__FILE__)))));
}

if (!file_exists(GLPI_ROOT . '/inc/includes.php')) {
    die("Error: GLPI includes.php not found");
}

include_once GLPI_ROOT . '/inc/includes.php';

if (!(int) Session::getLoginUserID()) {
    Html::redirect(GLPI_ROOT . '/index.php');
    exit;
}

$plugin = new Plugin();
if (!$plugin->isActivated('justificativas')) {
    Html::displayNotFoundError();
    exit;
}

if (!Session::haveRight('justificativas', READ)) {
    Html::displayRightError();
    exit;
}

global $DB;

// Debug de PHP para evitar tela branca
ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * Parse date values from common CSV/Excel formats.
 *
 * @param mixed $value
 * @return string|null
 */
function plugin_justificativas_parse_date($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    if (is_numeric($value) && (int)$value > 20000) {
        $timestamp = ((int)$value - 25569) * 86400;
        return gmdate('Y-m-d', $timestamp);
    }

    $formats = [
        'Y-m-d',
        'Y-m-d H:i',
        'Y-m-d H:i:s',
        'd/m/Y',
        'd/m/Y H:i',
        'd/m/Y H:i:s',
        'd-m-Y',
        'd-m-Y H:i',
        'd-m-Y H:i:s',
        'm/d/Y',
        'm/d/Y H:i',
        'm/d/Y H:i:s',
    ];

    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $value);
        if ($dt instanceof DateTime && $dt->format($format) === $value) {
            return $dt->format('Y-m-d');
        }
    }

    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})(?:\s+(\d{1,2}:\d{2}(?::\d{2})?))?$/', $value, $m)) {
        $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
        $year = strlen($m[3]) === 2 ? ('20'.$m[3]) : $m[3];
        if (checkdate((int)$month, (int)$day, (int)$year)) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }

    return null;
}

/**
 * Resolve operation id from ID or name.
 *
 * @param mixed $value
 * @return int|null
 */
function plugin_justificativas_resolve_operation_id($value) {
    global $DB;

    $value = plugin_justificativas_normalize_utf8($value);
    $query = $DB->request("SELECT id, name FROM glpi_plugin_justificativas_operations");
    while ($row = $query->next()) {
        if ($row['id'] == $value || mb_strtolower($row['name']) === mb_strtolower(trim($value))) {
            return (int)$row['id'];
        }
    }

    if (is_numeric($value)) {
        $id = (int)$value;
        $exists = $DB->request("SELECT id FROM glpi_plugin_justificativas_operations WHERE id = $id")->next();
        if ($exists) {
            return $id;
        }
    }

    return null;
}

/**
 * Normalize imported text to UTF-8.
 *
 * @param mixed $value
 * @return string
 */
function plugin_justificativas_normalize_utf8($value) {
    $value = (string) $value;
    if ($value === '' || mb_check_encoding($value, 'UTF-8')) {
        return $value;
    }

    $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $value);
    if ($converted !== false && $converted !== '') {
        return $converted;
    }

    $converted = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $value);
    if ($converted !== false && $converted !== '') {
        return $converted;
    }

    return $value;
}

// Garantir as tabelas
if (!$DB->tableExists('glpi_plugin_justificativas_operations')) {
    $DB->query("CREATE TABLE `glpi_plugin_justificativas_operations` ("
        . "`id` INT(11) NOT NULL AUTO_INCREMENT,"
        . "`name` VARCHAR(255) NOT NULL COMMENT 'Nome da operação',"
        . "`description` TEXT NULL COMMENT 'Descrição',"
        . "`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,"
        . "`updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,"
        . "PRIMARY KEY (`id`),"
        . "UNIQUE KEY (`name`)"
        . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

if (!$DB->tableExists('glpi_plugin_justificativas_entries')) {
    $DB->query("CREATE TABLE `glpi_plugin_justificativas_entries` ("
        . "`id` INT(11) NOT NULL AUTO_INCREMENT,"
        . "`ticket_id` INT(11) NOT NULL COMMENT 'Número do chamado',"
        . "`closing_date` DATE NOT NULL COMMENT 'Data de fechamento',"
        . "`justification` TEXT NOT NULL COMMENT 'Justificativa',"
        . "`operation_id` INT(11) NULL DEFAULT NULL COMMENT 'Operação associada',"
        . "`operation_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Nome da operação associada',"
        . "`user_id` INT(11) NULL DEFAULT NULL COMMENT 'Usuário que importou',"
        . "`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,"
        . "`updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,"
        . "PRIMARY KEY (`id`),"
        . "KEY (`ticket_id`),"
        . "KEY (`operation_id`)"
        . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} elseif (!$DB->fieldExists('glpi_plugin_justificativas_entries', 'operation_name')) {
    $DB->query("ALTER TABLE `glpi_plugin_justificativas_entries` ADD COLUMN `operation_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Nome da operação associada' AFTER `operation_id`");
}

$errors = [];
$message = '';

$imported = 0;
$skipped = 0;
$line = 0;

$operations = [];
foreach ($DB->request('SELECT id, name FROM glpi_plugin_justificativas_operations ORDER BY name') as $operationRow) {
    $operations[(int) $operationRow['id']] = $operationRow['name'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_justificativas'])) {
    if (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) {
        $errors[] = __('O envio falhou porque o corpo do POST está vazio, possivelmente devido a upload maior que post_max_size ou upload_max_filesize.');
    } else {
        $selectedOperationId = (int) ($_POST['operation_id'] ?? 0);
        if ($selectedOperationId <= 0 && empty($operations)) {
            $errors[] = __('Nenhuma operação selecionada e não há operações disponíveis.');
        }

        if (empty($_FILES['justificativa_file']['tmp_name'])) {
            $errors[] = __('Nenhum arquivo foi enviado. Selecione um arquivo válido e verifique o upload_max_filesize/post_max_size.');
        } else {
            $file = $_FILES['justificativa_file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                switch ($file['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $errors[] = __('Arquivo muito grande. Limite do servidor excedido.');
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $errors[] = __('Upload incompleto. Tente novamente.');
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $errors[] = __('Nenhum arquivo enviado.');
                        break;
                    default:
                        $errors[] = __('Erro no upload de arquivo');
                        break;
                }
            } else {
                $tmpName = $file['tmp_name'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $rows = [];

                if ($ext === 'csv') {
                    $lines = file($tmpName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    if ($lines === false || count($lines) === 0) {
                        $errors[] = __('Não foi possível ler o arquivo CSV ou está vazio');
                    } else {
                        $firstLine = trim($lines[0]);
                        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);
                        $delimiters = [';' => substr_count($firstLine, ';'), ',' => substr_count($firstLine, ','), '\t' => substr_count($firstLine, '\t'), '|' => substr_count($firstLine, '|')];
                        arsort($delimiters);
                        $delimiter = key($delimiters);

                        foreach ($lines as $line) {
                            $line = trim($line);
                            if ($line === '') {
                                continue;
                            }
                            $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
                            $parsed = str_getcsv($line, $delimiter, '"', '\\');
                            if (is_array($parsed) && count($parsed) > 0) {
                                $parsed = array_map('plugin_justificativas_normalize_utf8', $parsed);
                                $rows[] = $parsed;
                            }
                        }
                    }
                } else {
                    if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                        $errors[] = __('PhpSpreadsheet não encontrado. Use CSV ou instale a biblioteca.');
                    } else {
                        try {
                            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tmpName);
                            $spreadsheet = $reader->load($tmpName);
                            $sheet = $spreadsheet->getActiveSheet();
                            foreach ($sheet->getRowIterator() as $row) {
                                $cellIterator = $row->getCellIterator();
                                $cellIterator->setIterateOnlyExistingCells(false);
                                $rowData = [];
                                foreach ($cellIterator as $cell) {
                                    $rowData[] = trim((string) $cell->getValue());
                                }
                                    $rowData = array_map('plugin_justificativas_normalize_utf8', $rowData);
                                $rows[] = $rowData;
                            }
                        } catch (Exception $e) {
                            $errors[] = sprintf(__('Falha ao ler arquivo: %s'), $e->getMessage());
                        }
                    }
                }

                if (empty($errors)) {
                        $hasRowOperation = false;
                        foreach ($rows as $row) {
                            if (trim($row[3] ?? '') !== '') {
                                $hasRowOperation = true;
                                break;
                            }
                        }

                        if ($selectedOperationId <= 0 && !empty($operations) && !$hasRowOperation) {
                            $errors[] = __('Nenhuma operação selecionada e o arquivo não tem operação em colunas. Selecione uma operação ou preencha a 4ª coluna do arquivo.');
                        }
                    }

                    if (empty($errors)) {
                    $skipped = 0;
                    $line = 0;
                    $skipReasons = [];

                    foreach ($rows as $row) {
                        $line++;
                        if ($line === 1 && (stripos(implode(' ', $row), 'ticket') !== false || stripos(implode(' ', $row), 'chamado') !== false)) {
                            continue;
                        }
                        if (count($row) < 3) {
                            $skipped++;
                            $skipReasons[] = sprintf(__('Linha %d ignorada: menos de 3 colunas.'), $line);
                            continue;
                        }

                        $ticket_id = (int) $row[0];
                        $closing_date = plugin_justificativas_parse_date($row[1]);
                        $justification = trim($row[2]);
                        $rowOperation = trim($row[3] ?? '');
                        $operation_id = null;
                        $operation_name = null;

                        if ($rowOperation !== '') {
                            $operation_id = plugin_justificativas_resolve_operation_id($rowOperation);
                            if ($operation_id === null) {
                                $skipped++;
                                $skipReasons[] = sprintf(__('Linha %d ignorada: operação desconhecida (%s).'), $line, Html::clean($rowOperation));
                                continue;
                            }
                        } elseif (!empty($selectedOperationId)) {
                            $operation_id = $selectedOperationId;
                        }

                        if (!empty($operation_id)) {
                            $operation_name = $operations[$operation_id] ?? null;
                        }

                        if ($ticket_id <= 0 || $justification === '') {
                            $skipped++;
                            $skipReasons[] = sprintf(__('Linha %d ignorada: ticket ou justificativa inválidos.'), $line);
                            continue;
                        }

                        if ($closing_date === null) {
                            $skipped++;
                            $skipReasons[] = sprintf(__('Linha %d ignorada: data inválida (%s).'), $line, Html::clean((string) $row[1]));
                            continue;
                        }

                        if (empty($operation_id)) {
                            $skipped++;
                            $skipReasons[] = sprintf(__('Linha %d ignorada: operação não definida.'), $line);
                            continue;
                        }

                        $DB->insert('glpi_plugin_justificativas_entries', [
                            'ticket_id' => $ticket_id,
                            'closing_date' => $closing_date,
                            'justification' => $justification,
                            'operation_id' => $operation_id,
                            'operation_name' => $operation_name,
                            'user_id' => Session::getLoginUserID(),
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);

                        if ($DB->affectedRows() <= 0) {
                            $skipped++;
                            $skipReasons[] = sprintf(__('Linha %d ignorada: falha ao salvar no banco (%s).'), $line, Html::clean($DB->error()));
                            continue;
                        }

                        $imported++;
                    }

                    if ($imported === 0) {
                        if ($skipped === 0) {
                            $errors[] = __('Nenhuma linha válida encontrada no arquivo. Verifique se o arquivo contém dados na formatação esperada (ticket, data, justificativa, operação).');
                        } else {
                            $errors[] = __('Importação não gravou registros. Todas as linhas foram ignoradas. Verifique as razões exibidas abaixo.');
                        }
                        $message = '';
                    } else {
                        $message = sprintf(__('Importação concluída. %d importadas, %d ignoradas.'), $imported, $skipped);
                    }

                    if ($skipped > 0) {
                        $skippedMessages = array_slice($skipReasons, 0, 10);
                        $errors = array_merge($errors, $skippedMessages);
                        if (count($skipReasons) > 10) {
                            $errors[] = sprintf(__('Mais %d linhas foram ignoradas.'), count($skipReasons) - 10);
                        }
                    }
                }
            }
        }
    }
}

Html::header(__('Importar justificativas'), $_SERVER['PHP_SELF'], 'tools', 'justificativas');

if (!empty($message)) {
    echo '<div class="message ok" style="margin:10px 0;padding:10px;border:1px solid #2f8f2f;background:#e6f8e6;color:#2f8f2f;">'.htmlspecialchars($message, ENT_QUOTES, 'UTF-8').'</div>';
}
if (!empty($errors)) {
    echo '<div class="message error" style="margin:10px 0;padding:10px;border:1px solid #c62727;background:#f8e6e6;color:#c62727;"><ul style="margin:0;padding-left:20px;">';
    foreach ($errors as $error) {
        echo '<li>'.htmlspecialchars($error, ENT_QUOTES, 'UTF-8').'</li>';
    }
    echo '</ul></div>';
}

$csrf_token = Session::getNewCSRFToken();

// Formulário de importação padronizado
echo '<div class="center">';
echo '<form method="post" enctype="multipart/form-data" class="tab_cadre">';
echo '<table class="tab_cadre_fixe">';
echo '<tr><th colspan="2">'.__('Importar justificativas').'</th></tr>';

echo '<tr><td><label for="operation_id">'.__('Operação').'</label></td>';

echo '<td><select name="operation_id" id="operation_id">';
echo '<option value="">'.__('-- selecione --').'</option>';
foreach ($operations as $id => $name) {
    $selected = ((int)($_POST['operation_id'] ?? 0) === $id) ? ' selected' : '';
    echo '<option value="'.(int)$id.'"'.$selected.'>'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'</option>';
}
echo '</select></td></tr>';

echo '<tr><td><label for="justificativa_file">'.__('Arquivo').'</label></td>';

echo '<td><input type="file" name="justificativa_file" id="justificativa_file" size="60" /></td></tr>';

echo '<tr><td colspan="2">' . __('Selecione um arquivo CSV ou Excel (.xls/.xlsx) com colunas: ticket, data de fechamento, justificativa e opcionalmente operação (id ou nome).') . '</td></tr>';

echo '<tr><td colspan="2" class="center"><button type="submit" name="import_justificativas" class="submit">'.__('Importar').'</button></td></tr>';

echo '</table>';

echo '<input type="hidden" name="_glpi_csrf_token" value="'.htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8').'" />';

echo '</form>';

echo '</div>';

Html::footer();
