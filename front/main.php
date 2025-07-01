<?php

include ('../../../inc/includes.php');
require_once GLPI_ROOT . '/inc/plugin.class.php';

// Inicia a renderização da página do GLPI
Html::header('Catálogo de Serviços', $_SERVER['PHP_SELF'], 'plugins', 'catalogo');

echo '<div class="main_form">';
echo '<h2 class="section_header">Bem-vindo ao Catálogo de Serviços</h2>';

// Seção para usuários normais (visualizar o catálogo)
echo '<p>Explore os serviços disponíveis em nosso catálogo:</p>';
echo '<div class="row">';
echo '<div class="col-md-12">';
echo '<a href="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/view_catalog.php" class="btn btn-primary btn-lg btn-block">';
echo 'Visualizar Catálogo';
echo '</a>';
echo '</div>';
echo '</div>'; // Fecha row

// Seção de gerenciamento (apenas para usuários com permissão)
// A permissão 'plugin_catalogo_config' READ é um exemplo.
// Você pode criar uma permissão mais específica para gerenciar, como 'plugin_catalogo_manage'
if (Session::haveRight('plugin_catalogo_config', READ)) {
    echo '<hr>';
    echo '<h3 class="section_header">Gerenciamento do Catálogo</h3>';
    echo '<p>Selecione uma opção para gerenciar as informações do catálogo:</p>';

    echo '<div class="row">';
    echo '<div class="col-md-4">';
    echo '<a href="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/category.php" class="btn btn-primary btn-lg btn-block">';
    echo 'Gerenciar Categorias';
    echo '</a>';
    echo '</div>';

    echo '<div class="col-md-4">';
    echo '<a href="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/subcategory.php" class="btn btn-info btn-lg btn-block">';
    echo 'Gerenciar Subcategorias';
    echo '</a>';
    echo '</div>';

    echo '<div class="col-md-4">';
    echo '<a href="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php" class="btn btn-success btn-lg btn-block">';
    echo 'Gerenciar Serviços';
    echo '</a>';
    echo '</div>';
    echo '</div>'; // Fecha row
}

echo '</div>'; // Fecha main_form

// Finaliza a renderização da página do GLPI
Html::footer();
