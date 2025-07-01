<?php

include ('../../../inc/includes.php');
require_once GLPI_ROOT . '/inc/plugin.class.php';
require_once GLPI_ROOT . '/plugins/catalogo/inc/PluginCatalogoCategory.class.php';

// Verifica permissões (ajuste conforme necessário)
if (!Session::haveRight('plugin_catalogo_config', READ)) { // Use uma permissão adequada para o seu plugin
    Html::displayNotFoundError();
}

// Processa ações do formulário (adicionar/editar/excluir)
if (isset($_POST['submit'])) {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;
    $titulo = $_POST['titulo'] ?? '';
    $descricao = $_POST['descricao'] ?? '';

    switch ($action) {
        case 'add':
            if (PluginCatalogoCategory::addCategory($titulo, $descricao)) {
                Session::addMessageAfterRedirect('Categoria adicionada com sucesso!', true, INFO);
            } else {
                Session::addMessageAfterRedirect('Erro ao adicionar categoria.', true, ERROR);
            }
            Html::redirect($CFG_GLPI['root_doc'] . '/plugins/catalogo/front/category.php');
            break;

        case 'edit':
            if (PluginCatalogoCategory::updateCategory($id, $titulo, $descricao)) {
                Session::addMessageAfterRedirect('Categoria atualizada com sucesso!', true, INFO);
            } else {
                Session::addMessageAfterRedirect('Erro ao atualizar categoria.', true, ERROR);
            }
            Html::redirect($CFG_GLPI['root_doc'] . '/plugins/catalogo/front/category.php');
            break;

        case 'delete':
            if (PluginCatalogoCategory::deleteCategory($id)) {
                Session::addMessageAfterRedirect('Categoria excluída com sucesso!', true, INFO);
            } else {
                Session::addMessageAfterRedirect('Erro ao excluir categoria.', true, ERROR);
            }
            Html::redirect($CFG_GLPI['root_doc'] . '/plugins/catalogo/front/category.php');
            break;
    }
}

// Inicia a renderização da página do GLPI
Html::header('Gerenciar Categorias', $_SERVER['PHP_SELF'], 'plugins', 'catalogo');

// Verifica se é para exibir o formulário de adição/edição
$is_edit = false;
$category_to_edit = [];
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $is_edit = true;
    $category_to_edit = PluginCatalogoCategory::getCategoryById($_GET['id']);
    if (!$category_to_edit) {
        Session::addMessageAfterRedirect('Categoria não encontrada.', true, ERROR);
        Html::redirect($CFG_GLPI['root_doc'] . '/plugins/catalogo/front/category.php');
    }
}

echo '<div class="main_form">';

// Formulário de Adição/Edição de Categoria
echo '<form method="post" action="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/category.php">';
echo '<input type="hidden" name="action" value="' . ($is_edit ? 'edit' : 'add') . '">';
if ($is_edit) {
    echo '<input type="hidden" name="id" value="' . $category_to_edit['id'] . '">';
}

echo '<div class="field">';
echo '<label for="titulo">Título da Categoria:</label>';
echo '<input type="text" name="titulo" id="titulo" class="form-control" required value="' . ($is_edit ? Html::cleanInputText($category_to_edit['titulo']) : '') . '">';
echo '</div>';

echo '<div class="field">';
echo '<label for="descricao">Descrição da Categoria:</label>';
echo '<textarea name="descricao" id="descricao" class="form-control">' . ($is_edit ? Html::cleanInputText($category_to_edit['descricao']) : '') . '</textarea>';
echo '</div>';

echo '<div class="form-actions">';
echo '<input type="submit" name="submit" value="' . ($is_edit ? 'Atualizar' : 'Adicionar') . '" class="submit">';
echo '</div>';

Html::closeForm();
echo '</div>';

echo '<hr>';

// Listagem de Categorias
$categories = PluginCatalogoCategory::getAllCategories();

echo '<div class="main_form">';
echo '<div class="table-responsive">';
echo '<table class="tab_cadre_fixe">';
echo '<thead>';
echo '<tr>';
echo '<th>ID</th>';
echo '<th>Título</th>';
echo '<th>Descrição</th>';
echo '<th>Ações</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

if (count($categories) > 0) {
    foreach ($categories as $category) {
        echo '<tr>';
        echo '<td>' . $category['id'] . '</td>';
        echo '<td>' . Html::cleanOutputText($category['titulo']) . '</td>';
        echo '<td>' . Html::cleanOutputText($category['descricao']) . '</td>';
        echo '<td>';
        echo '<a href="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/category.php?action=edit&id=' . $category['id'] . '" class="btn btn-primary btn-sm">Editar</a> ';
        echo '<a href="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/category.php?action=delete&id=' . $category['id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Tem certeza que deseja excluir esta categoria?\');">Excluir</a>';
        echo '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="4">Nenhuma categoria encontrada.</td></tr>';
}

echo '</tbody>';
echo '</table>';
echo '</div>';
echo '</div>';

// Finaliza a renderização da página do GLPI
Html::footer();
