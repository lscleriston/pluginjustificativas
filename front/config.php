<?php

// Fallback for direct access URL (when GLPI_ROOT is not set)
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(dirname(dirname(dirname(__FILE__)))));
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

if (!Session::haveRight('justificativas', UPDATE) && !Session::haveRight('config', UPDATE)) {
    Html::displayRightError();
    exit;
}

global $DB;
$errors = [];
$message = '';

// Criação de nova operação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_operation'])) {
    $newOperationName = trim($_POST['new_operation_name'] ?? '');
    if ($newOperationName === '') {
        $errors[] = __('Informe um nome de operação.');
    } else {
        $existing = $DB->request("SELECT id FROM glpi_plugin_justificativas_operations WHERE name = '" . $DB->escape($newOperationName) . "'")->next();
        if ($existing) {
            $errors[] = __('Operação já existente.');
        } else {
            $DB->insert('glpi_plugin_justificativas_operations', [
                'name' => $newOperationName,
                'description' => trim($_POST['new_operation_description'] ?? ''),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $message = __('Operação cadastrada com sucesso.');
        }
    }
}

$operations = [];
foreach ($DB->request('SELECT id, name, description FROM glpi_plugin_justificativas_operations ORDER BY name') as $operationRow) {
    $operations[] = $operationRow;
}

Html::header(__('Cadastro de Operações'), $_SERVER['PHP_SELF'], 'tools', 'justificativas');
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
echo '<h3>'.__('Cadastrar nova operação').'</h3>';
echo '<form method="post">';
echo '<input type="hidden" name="_glpi_csrf_token" value="'.htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8').'" />';
echo '<label>'.__('Nome da operação').': <input type="text" name="new_operation_name" value="'.htmlspecialchars($_POST['new_operation_name'] ?? '', ENT_QUOTES, 'UTF-8').'" /></label>';
echo '<br><label>'.__('Descrição').': <input type="text" name="new_operation_description" value="'.htmlspecialchars($_POST['new_operation_description'] ?? '', ENT_QUOTES, 'UTF-8').'" /></label>';
echo '<br><button type="submit" name="create_operation" class="btn btn-secondary">'.__('Criar operação').'</button>';
echo '</form>';

echo '<h3>'.__('Operações cadastradas').'</h3>';
if (empty($operations)) {
    echo '<p>'.__('Nenhuma operação cadastrada ainda.').'</p>';
} else {
    echo '<table class="tab_cadre_fixe">';
    echo '<tr><th>'.__('ID').'</th><th>'.__('Nome').'</th><th>'.__('Descrição').'</th></tr>';
    foreach ($operations as $op) {
        echo '<tr><td>'.(int)$op['id'].'</td><td>'.htmlspecialchars($op['name'], ENT_QUOTES, 'UTF-8').'</td><td>'.htmlspecialchars($op['description'], ENT_QUOTES, 'UTF-8').'</td></tr>';
    }
    echo '</table>';
}
Html::footer();
