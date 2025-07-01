<?php
include ('../../../inc/includes.php');
require_once GLPI_ROOT . '/inc/plugin.class.php';

// Verifica permissões
if (!Session::haveRight('plugin_catalogo_config', READ)) {
    Html::displayNotFoundError();
}

$plugin = new Plugin();
$plugin->init('catalogo');

$page_name = $plugin->get('config_page_name');

Html::header('Configurações do Catálogo', $_SERVER['PHP_SELF'], 'plugins', 'catalogo');

echo '<div class="main_form">';
echo '<form method="post" action="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/config.php">';

$chatbot_enabled = Plugin::getData('catalogo', 'chatbot_enabled', 0);

echo '<div class="field">';
echo '<label for="chatbot_enabled">';
echo 'Ativar Chatbot:';
echo '</label>';
echo '<input type="checkbox" name="chatbot_enabled" id="chatbot_enabled" ' . ($chatbot_enabled ? 'checked' : '') . '>';
echo '</div>';

echo '<div class="form-actions">';
echo '<input type="submit" name="submit" value="Salvar" class="submit">';
echo '</div>';

Html::closeForm();
echo '</div>';

if (isset($_POST['submit'])) {
    $new_chatbot_enabled = isset($_POST['chatbot_enabled']) ? 1 : 0;
    Plugin::setData('catalogo', 'chatbot_enabled', $new_chatbot_enabled);
    Html::back();
}

Html::footer();
