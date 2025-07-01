<?php

include ('../../../inc/includes.php');
require_once GLPI_ROOT . '/inc/plugin.class.php';
require_once GLPI_ROOT . '/plugins/catalogo/inc/PluginCatalogoCategory.class.php';
require_once GLPI_ROOT . '/plugins/catalogo/inc/PluginCatalogoSubcategory.class.php';
require_once GLPI_ROOT . '/plugins/catalogo/inc/PluginCatalogoService.class.php';
require_once GLPI_ROOT . '/plugins/catalogo/inc/PluginCatalogoApproval.class.php';
require_once GLPI_ROOT . '/inc/user.class.php';

// Verifica permissões (ajuste conforme necessário)
if (!Session::haveRight('plugin_catalogo_config', READ)) {
    Html::displayNotFoundError();
}

// Obtém todos os usuários GLPI para os dropdowns
$glpi_users = PluginCatalogoService::getGLPIUsersForDropdown();

// Processa ações do formulário
if (isset($_POST['submit'])) {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;

    switch ($action) {
        case 'add':
        case 'edit':
            $subcategories_id = $_POST['subcategories_id'] ?? 0;
            $titulo = $_POST['titulo'] ?? '';
            $descricao = $_POST['descricao'] ?? '';
            $kbs = $_POST['kbs'] ?? '';
            $area_especialista = $_POST['area_especialista'] ?? '';
            $po_responsavel_users_id = $_POST['po_responsavel_users_id'] ?? 0;
            $alcadas = $_POST['alcadas'] ?? '';
            $procedimento_excecao = $_POST['procedimento_excecao'] ?? '';
            $usuario_criador_users_id = $_POST['usuario_criador_users_id'] ?? 0;
            $observacoes = $_POST['observacoes'] ?? '';
            $status_ficha = $_POST['status_ficha'] ?? '';
            $data_revisao = $_POST['data_revisao'] ?? '';
            $po_aprovador_users_id = $_POST['po_aprovador_users_id'] ?? 0;
            $data_aprovacao = $_POST['data_aprovacao'] ?? '';
            $justificativa_rejeicao = $_POST['justificativa_rejeicao'] ?? '';
            $codigo_ficha = $_POST['codigo_ficha'] ?? '';
            $versao = $_POST['versao'] ?? '';
            $previous_version_id = $_POST['previous_version_id'] ?? NULL;


            if ($action === 'add') {
                $status_ficha = PluginCatalogoApproval::STATUS_DRAFT; // Define o status inicial como Rascunho
                if (PluginCatalogoService::addService(
                    $subcategories_id, $titulo, $descricao, $kbs, $area_especialista, $po_responsavel_users_id,
                    $alcadas, $procedimento_excecao, $usuario_criador_users_id, $observacoes, $status_ficha,
                    $data_revisao, $po_aprovador_users_id, $data_aprovacao,
                    $justificativa_rejeicao, $codigo_ficha, $versao, $previous_version_id
                )) {
                    Session::addMessageAfterRedirect('Serviço adicionado com sucesso!', true, INFO);
                } else {
                    Session::addMessageAfterRedirect('Erro ao adicionar serviço.', true, ERROR);
                }
            } else { // edit
                // Ao editar, o status não é alterado pelo formulário principal, mas sim pelas ações de fluxo
                if (PluginCatalogoService::updateService(
                    $id, $subcategories_id, $titulo, $descricao, $kbs, $area_especialista, $po_responsavel_users_id,
                    $alcadas, $procedimento_excecao, $usuario_criador_users_id, $observacoes, $status_ficha, // Mantém o status existente
                    $data_revisao, $po_aprovador_users_id, $data_aprovacao,
                    $justificativa_rejeicao, $codigo_ficha, $versao, $previous_version_id
                )) {
                    Session::addMessageAfterRedirect('Serviço atualizado com sucesso!', true, INFO);
                } else {
                    Session::addMessageAfterRedirect('Erro ao atualizar serviço.', true, ERROR);
                }
            }
            Html::redirect($CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php');
            break;

        case 'delete':
            if (PluginCatalogoService::deleteService($id)) {
                Session::addMessageAfterRedirect('Serviço excluído com sucesso!', true, INFO);
            } else {
                Session::addMessageAfterRedirect('Erro ao excluir serviço.', true, ERROR);
            }
            Html::redirect($CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php');
            break;

        case 'assign_reviser':
            $service_id = $_POST['service_id'] ?? 0;
            $reviser_user_id = $_POST['reviser_user_id'] ?? 0;
            if ($service_id > 0 && $reviser_user_id > 0) {
                if (PluginCatalogoService::assignReviserToService($service_id, $reviser_user_id)) {
                    Session::addMessageAfterRedirect('Revisor associado com sucesso!', true, INFO);
                } else {
                    Session::addMessageAfterRedirect('Erro ao associar revisor.', true, ERROR);
                }
            } else {
                Session::addMessageAfterRedirect('Dados inválidos para associar revisor.', true, ERROR);
            }
            Html::redirect($CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php?action=edit&id=' . $service_id);
            break;

        case 'unassign_reviser':
            $service_id = $_POST['service_id'] ?? 0;
            $reviser_user_id = $_POST['reviser_user_id'] ?? 0;
            if ($service_id > 0 && $reviser_user_id > 0) {
                if (PluginCatalogoService::unassignReviserFromService($service_id, $reviser_user_id)) {
                    Session::addMessageAfterRedirect('Revisor desassociado com sucesso!', true, INFO);
                } else {
                    Session::addMessageAfterRedirect('Erro ao desassociar revisor.', true, ERROR);
                }
            } else {
                Session::addMessageAfterRedirect('Dados inválidos para desassociar revisor.', true, ERROR);
            }
            Html::redirect($CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php?action=edit&id=' . $service_id);
            break;

        case 'send_for_review':
            $service_id = $_POST['service_id'] ?? 0;
            if ($service_id > 0) {
                if (PluginCatalogoApproval::updateServiceStatus($service_id, PluginCatalogoApproval::STATUS_IN_REVIEW)) {
                    Session::addMessageAfterRedirect('Serviço enviado para revisão com sucesso!', true, INFO);
                } else {
                    Session::addMessageAfterRedirect('Erro ao enviar serviço para revisão.', true, ERROR);
                }
            } else {
                Session::addMessageAfterRedirect('Serviço inválido para enviar para revisão.', true, ERROR);
            }
            Html::redirect($CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php?action=edit&id=' . $service_id);
            break;

        case 'send_for_approval':
            $service_id = $_POST['service_id'] ?? 0;
            if ($service_id > 0) {
                if (PluginCatalogoApproval::updateServiceStatus($service_id, PluginCatalogoApproval::STATUS_IN_APPROVAL)) {
                    Session::addMessageAfterRedirect('Serviço enviado para aprovação com sucesso!', true, INFO);
                } else {
                    Session::addMessageAfterRedirect('Erro ao enviar serviço para aprovação.', true, ERROR);
                }
            } else {
                Session::addMessageAfterRedirect('Serviço inválido para enviar para aprovação.', true, ERROR);
            }
            Html::redirect($CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php?action=edit&id=' . $service_id);
            break;

        case 'publish_service':
            $service_id = $_POST['service_id'] ?? 0;
            $previous_version_id_on_publish = $_POST['previous_version_id_on_publish'] ?? 0; // ID da versão anterior

            if ($service_id > 0) {
                if (PluginCatalogoApproval::updateServiceStatus($service_id, PluginCatalogoApproval::STATUS_PUBLISHED)) {
                    // Se a publicação for bem-sucedida e houver uma versão anterior, marcá-la como substituída
                    if ($previous_version_id_on_publish > 0) {
                        PluginCatalogoApproval::updateServiceStatus($previous_version_id_on_publish, PluginCatalogoApproval::STATUS_REPLACED, "Substituído pela versão ID: $service_id");
                        Session::addMessageAfterRedirect('Serviço publicado e versão anterior marcada como substituída com sucesso!', true, INFO);
                    } else {
                        Session::addMessageAfterRedirect('Serviço publicado com sucesso!', true, INFO);
                    }
                } else {
                    Session::addMessageAfterRedirect('Erro ao publicar serviço.', true, ERROR);
                }
            } else {
                Session::addMessageAfterRedirect('Serviço inválido para publicação.', true, ERROR);
            }
            Html::redirect($CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php?action=edit&id=' . $service_id);
            break;

        case 'cancel_service':
            $service_id = $_POST['service_id'] ?? 0;
            $justificativa = $_POST['justificativa'] ?? '';
            if ($service_id > 0) {
                if (PluginCatalogoApproval::updateServiceStatus($service_id, PluginCatalogoApproval::STATUS_CANCELED, $justificativa)) {
                    Session::addMessageAfterRedirect('Serviço cancelado com sucesso!', true, INFO);
                } else {
                    Session::addMessageAfterRedirect('Erro ao cancelar serviço.', true, ERROR);
                }
            } else {
                Session::addMessageAfterRedirect('Serviço inválido para cancelamento.', true, ERROR);
            }
            Html::redirect($CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php?action=edit&id=' . $service_id);
            break;

        case 'replace_service':
            $service_id = $_POST['service_id'] ?? 0;
            $justificativa = $_POST['justificativa'] ?? '';
            if ($service_id > 0) {
                // A ação de substituir agora é feita pela criação de uma nova versão e posterior publicação
                // Este botão agora apenas muda o status para 'substituida' se não for parte de um fluxo de nova versão
                // ou se for uma substituição manual.
                if (PluginCatalogoApproval::updateServiceStatus($service_id, PluginCatalogoApproval::STATUS_REPLACED, $justificativa)) {
                    Session::addMessageAfterRedirect('Serviço marcado como substituído com sucesso!', true, INFO);
                } else {
                    Session::addMessageAfterRedirect('Erro ao marcar serviço como substituído.', true, ERROR);
                }
            } else {
                Session::addMessageAfterRedirect('Serviço inválido para substituição.', true, ERROR);
            }
            Html::redirect($CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php?action=edit&id=' . $service_id);
            break;

        case 'discontinue_service':
            $service_id = $_POST['service_id'] ?? 0;
            $justificativa = $_POST['justificativa'] ?? '';
            if ($service_id > 0) {
                if (PluginCatalogoApproval::updateServiceStatus($service_id, PluginCatalogoApproval::STATUS_DISCONTINUED, $justificativa)) {
                    Session::addMessageAfterRedirect('Serviço descontinuado com sucesso!', true, INFO);
                } else {
                    Session::addMessageAfterRedirect('Erro ao descontinuar serviço.', true, ERROR);
                }
            } else {
                Session::addMessageAfterRedirect('Serviço inválido para descontinuação.', true, ERROR);
            }
            Html::redirect($CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php?action=edit&id=' . $service_id);
            break;

        case 'create_new_version':
            $original_service_id = $_POST['original_service_id'] ?? 0;
            if ($original_service_id > 0) {
                $new_service_id = PluginCatalogoService::createNewVersion($original_service_id);
                if ($new_service_id) {
                    Session::addMessageAfterRedirect('Nova versão do serviço criada com sucesso!', true, INFO);
                    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php?action=edit&id=' . $new_service_id);
                } else {
                    Session::addMessageAfterRedirect('Erro ao criar nova versão do serviço.', true, ERROR);
                }
            } else {
                Session::addMessageAfterRedirect('Serviço original inválido para criar nova versão.', true, ERROR);
            }
            break;
    }
}

// Inicia a renderização da página do GLPI
Html::header('Gerenciar Serviços', $_SERVER['PHP_SELF'], 'plugins', 'catalogo');

// Verifica se é para exibir o formulário de adição/edição
$is_edit = false;
$service_to_edit = [];
$current_revisers = [];
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $is_edit = true;
    $service_to_edit = PluginCatalogoService::getServiceById($_GET['id']);
    if (!$service_to_edit) {
        Session::addMessageAfterRedirect('Serviço não encontrado.', true, ERROR);
        Html::redirect($CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php');
    }
    $current_revisers = PluginCatalogoService::getServiceRevisers($service_to_edit['id']);
}

// Obtém todas as categorias e subcategorias para os dropdowns
$categories = PluginCatalogoCategory::getAllCategories();
$subcategories = PluginCatalogoSubcategory::getAllSubcategories();

echo '<div class="main_form">';

// Formulário de Adição/Edição de Serviço
echo '<form method="post" action="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php">';
echo '<input type="hidden" name="action" value="' . ($is_edit ? 'edit' : 'add') . '">';
if ($is_edit) {
    echo '<input type="hidden" name="id" value="' . $service_to_edit['id'] . '">';
    echo '<input type="hidden" name="previous_version_id" value="' . ($service_to_edit['previous_version_id'] ?? '') . '">';
}

echo '<div class="field">';
echo '<label for="subcategories_id">Subcategoria:</label>';
echo '<select name="subcategories_id" id="subcategories_id" class="form-control" required>';
echo '<option value="">-- Selecione --</option>';
foreach ($subcategories as $subcategory) {
    $selected = ($is_edit && $service_to_edit['subcategories_id'] == $subcategory['id']) ? 'selected' : '';
    echo '<option value="' . $subcategory['id'] . '" ' . $selected . '>' . Html::cleanOutputText($subcategory['category_name'] . ' > ' . $subcategory['titulo']) . '</option>';
}
echo '</select>';
echo '</div>';

echo '<div class="field">';
echo '<label for="titulo">Título do Serviço:</label>';
echo '<input type="text" name="titulo" id="titulo" class="form-control" required value="' . ($is_edit ? Html::cleanInputText($service_to_edit['titulo']) : '') . '">';
echo '</div>';

echo '<div class="field">';
echo '<label for="descricao">Descrição:</label>';
echo '<textarea name="descricao" id="descricao" class="form-control">' . ($is_edit ? Html::cleanInputText($service_to_edit['descricao']) : '') . '</textarea>';
echo '</div>';

echo '<div class="field">';
echo '<label for="kbs">KBs Relacionadas:</label>';
echo '<textarea name="kbs" id="kbs" class="form-control">' . ($is_edit ? Html::cleanInputText($service_to_edit['kbs']) : '') . '</textarea>';
echo '</div>';

echo '<div class="field">';
echo '<label for="area_especialista">Área Especialista:</label>';
echo '<input type="text" name="area_especialista" id="area_especialista" class="form-control" value="' . ($is_edit ? Html::cleanInputText($service_to_edit['area_especialista']) : '') . '">';
echo '</div>';

echo '<div class="field">';
echo '<label for="po_responsavel_users_id">PO Responsável:</label>';
echo '<select name="po_responsavel_users_id" id="po_responsavel_users_id" class="form-control">';
echo '<option value="0">-- Selecione --</option>';
foreach ($glpi_users as $user) {
    $selected = ($is_edit && $service_to_edit['po_responsavel_users_id'] == $user['id']) ? 'selected' : '';
    echo '<option value="' . $user['id'] . '" ' . $selected . '>' . Html::cleanOutputText($user['name']) . '</option>';
}
echo '</select>';
echo '</div>';

echo '<div class="field">';
echo '<label for="alcadas">Alçadas:</label>';
echo '<textarea name="alcadas" id="alcadas" class="form-control">' . ($is_edit ? Html::cleanInputText($service_to_edit['alcadas']) : '') . '</textarea>';
echo '</div>';

echo '<div class="field">';
echo '<label for="procedimento_excecao">Procedimento de Exceção:</label>';
echo '<textarea name="procedimento_excecao" id="procedimento_excecao" class="form-control">' . ($is_edit ? Html::cleanInputText($service_to_edit['procedimento_excecao']) : '') . '</textarea>';
echo '</div>';

echo '<div class="field">';
echo '<label for="usuario_criador_users_id">Usuário Criador:</label>';
echo '<select name="usuario_criador_users_id" id="usuario_criador_users_id" class="form-control">';
echo '<option value="0">-- Selecione --</option>';
foreach ($glpi_users as $user) {
    $selected = ($is_edit && $service_to_edit['usuario_criador_users_id'] == $user['id']) ? 'selected' : '';
    echo '<option value="' . $user['id'] . '" ' . $selected . '>' . Html::cleanOutputText($user['name']) . '</option>';
}
echo '</select>';
echo '</div>';

echo '<div class="field">';
echo '<label for="observacoes">Observações:</label>';
echo '<textarea name="observacoes" id="observacoes" class="form-control">' . ($is_edit ? Html::cleanInputText($service_to_edit['observacoes']) : '') . '</textarea>';
echo '</div>';

echo '<div class="field">';
echo '<label for="status_ficha">Status da Ficha:</label>';
echo '<input type="text" name="status_ficha" id="status_ficha" class="form-control" value="' . ($is_edit ? Html::cleanInputText($service_to_edit['status_ficha']) : PluginCatalogoApproval::STATUS_DRAFT) . '" readonly>';
echo '</div>';

echo '<div class="field">';
echo '<label for="data_revisao">Data da Revisão:</label>';
echo '<input type="date" name="data_revisao" id="data_revisao" class="form-control" value="' . ($is_edit && $service_to_edit['data_revisao'] ? date('Y-m-d', strtotime($service_to_edit['data_revisao'])) : '') . '">';
echo '</div>';

echo '<div class="field">';
echo '<label for="po_aprovador_users_id">PO Aprovador:</label>';
echo '<select name="po_aprovador_users_id" id="po_aprovador_users_id" class="form-control">';
echo '<option value="0">-- Selecione --</option>';
foreach ($glpi_users as $user) {
    $selected = ($is_edit && $service_to_edit['po_aprovador_users_id'] == $user['id']) ? 'selected' : '';
    echo '<option value="' . $user['id'] . '" ' . $selected . '>' . Html::cleanOutputText($user['name']) . '</option>';
}
echo '</select>';
echo '</div>';

echo '<div class="field">';
echo '<label for="data_aprovacao">Data da Aprovação:</label>';
echo '<input type="date" name="data_aprovacao" id="data_aprovacao" class="form-control" value="' . ($is_edit && $service_to_edit['data_aprovacao'] ? date('Y-m-d', strtotime($service_to_edit['data_aprovacao'])) : '') . '">';
echo '</div>';

echo '<div class="field">';
echo '<label for="justificativa_rejeicao">Justificativa de Rejeição:</label>';
echo '<textarea name="justificativa_rejeicao" id="justificativa_rejeicao" class="form-control">' . ($is_edit ? Html::cleanInputText($service_to_edit['justificativa_rejeicao']) : '') . '</textarea>';
echo '</div>';

echo '<div class="field">';
echo '<label for="codigo_ficha">Código da Ficha:</label>';
echo '<input type="text" name="codigo_ficha" id="codigo_ficha" class="form-control" value="' . ($is_edit ? Html::cleanInputText($service_to_edit['codigo_ficha']) : '') . '">';
echo '</div>';

echo '<div class="field">';
echo '<label for="versao">Versão:</label>';
echo '<input type="text" name="versao" id="versao" class="form-control" value="' . ($is_edit ? Html::cleanInputText($service_to_edit['versao']) : '') . '">';
echo '</div>';

echo '<div class="form-actions">';
echo '<input type="submit" name="submit" value="' . ($is_edit ? 'Atualizar Serviço' : 'Adicionar Serviço') . '" class="submit">';
echo '</div>';

Html::closeForm();
echo '</div>';

// Se estiver editando um serviço, adicione a seção de gerenciamento de revisores e ações de fluxo
if ($is_edit) {
    echo '<hr>';
    echo '<h3 class="section_header">Gerenciar Revisores do Serviço</h3>';
    echo '<div class="main_form">';

    // Formulário para adicionar revisor
    echo '<form method="post" action="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php">';
    echo '<input type="hidden" name="action" value="assign_reviser">';
    echo '<input type="hidden" name="service_id" value="' . $service_to_edit['id'] . '">';
    echo '<div class="field">';
    echo '<label for="reviser_user_id">Adicionar Revisor:</label>';
    echo '<select name="reviser_user_id" id="reviser_user_id" class="form-control" required>';
    echo '<option value="">-- Selecione um usuário GLPI --</option>';
    foreach ($glpi_users as $user) {
        $is_already_reviser = false;
        foreach ($current_revisers as $reviser) {
            if ($reviser['id'] == $user['id']) {
                $is_already_reviser = true;
                break;
            }
        }
        if (!$is_already_reviser) {
            echo '<option value="' . $user['id'] . '">' . Html::cleanOutputText($user['name']) . '</option>';
        }
    }
    echo '</select>';
    echo '</div>';
    echo '<div class="form-actions">';
    echo '<input type="submit" name="submit" value="Adicionar Revisor" class="submit">';
    echo '</div>';
    Html::closeForm();

    echo '<hr>';

    // Lista de revisores atuais
    echo '<h4>Revisores Atuais:</h4>';
    if (count($current_revisers) > 0) {
        echo '<table class="tab_cadre_fixe">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Nome</th>';
        echo '<th>Ações</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ($current_revisers as $reviser) {
            echo '<tr>';
            echo '<td>' . Html::cleanOutputText($reviser['firstname'] . ' ' . $reviser['realname']) . '</td>';
            echo '<td>';
            echo '<form method="post" action="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php" style="display:inline;">';
            echo '<input type="hidden" name="action" value="unassign_reviser">';
            echo '<input type="hidden" name="service_id" value="' . $service_to_edit['id'] . '">';
            echo '<input type="hidden" name="reviser_user_id" value="' . $reviser['id'] . '">';
            echo '<input type="submit" name="submit" value="Remover" class="btn btn-danger btn-sm" onclick="return confirm(\'Tem certeza que deseja remover este revisor?\');">';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>Nenhum revisor associado a este serviço.</p>';
    }
    echo '</div>'; // Fecha main_form de revisores

    echo '<hr>';
    echo '<h3 class="section_header">Ações de Fluxo</h3>';
    echo '<div class="main_form">';
    echo '<form method="post" action="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php">';
    echo '<input type="hidden" name="service_id" value="' . $service_to_edit['id'] . '">';
    echo '<input type="hidden" name="previous_version_id_on_publish" value="' . ($service_to_edit['previous_version_id'] ?? '') . '">';


    // Botões de ação baseados no status atual
    switch ($service_to_edit['status_ficha']) {
        case PluginCatalogoApproval::STATUS_DRAFT:
        case PluginCatalogoApproval::STATUS_REJECTED_REVISER:
        case PluginCatalogoApproval::STATUS_REJECTED_PO:
            echo '<input type="hidden" name="action" value="send_for_review">';
            echo '<input type="submit" name="submit" value="Enviar para Revisão" class="btn btn-primary">';
            break;
        case PluginCatalogoApproval::STATUS_REVIEWED:
            echo '<input type="hidden" name="action" value="send_for_approval">';
            echo '<input type="submit" name="submit" value="Enviar para Aprovação" class="btn btn-info">';
            break;
        case PluginCatalogoApproval::STATUS_APPROVED:
            echo '<input type="hidden" name="action" value="publish_service">';
            echo '<input type="submit" name="submit" value="Publicar Serviço" class="btn btn-success">';
            echo '<br><br>';
            echo '<form method="post" action="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php" style="display:inline-block;">';
            echo '<input type="hidden" name="action" value="create_new_version">';
            echo '<input type="hidden" name="original_service_id" value="' . $service_to_edit['id'] . '">';
            echo '<input type="submit" name="submit" value="Criar Nova Versão" class="btn btn-warning">';
            echo '</form>';
            break;
        case PluginCatalogoApproval::STATUS_PUBLISHED:
            echo '<p>Este serviço está publicado.</p>';
            echo '<form method="post" action="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php" style="display:inline-block;">';
            echo '<input type="hidden" name="action" value="create_new_version">';
            echo '<input type="hidden" name="original_service_id" value="' . $service_to_edit['id'] . '">';
            echo '<input type="submit" name="submit" value="Criar Nova Versão" class="btn btn-warning">';
            echo '</form>';
            break;
        case PluginCatalogoApproval::STATUS_IN_REVIEW:
            echo '<p>Este serviço está atualmente em revisão.</p>';
            break;
        case PluginCatalogoApproval::STATUS_IN_APPROVAL:
            echo '<p>Este serviço está atualmente em aprovação.</p>';
            break;
        case PluginCatalogoApproval::STATUS_CANCELED:
            echo '<p>Este serviço foi cancelado.</p>';
            break;
        case PluginCatalogoApproval::STATUS_REPLACED:
            echo '<p>Este serviço foi substituído.</p>';
            break;
        case PluginCatalogoApproval::STATUS_DISCONTINUED:
            echo '<p>Este serviço foi descontinuado.</p>';
            break;
    }

    // Ações de cancelamento/substituição/descontinuação (sempre visíveis para serviços editáveis)
    if ($service_to_edit['status_ficha'] !== PluginCatalogoApproval::STATUS_CANCELED &&
        $service_to_edit['status_ficha'] !== PluginCatalogoApproval::STATUS_REPLACED &&
        $service_to_edit['status_ficha'] !== PluginCatalogoApproval::STATUS_DISCONTINUED) {
        echo '<br><br>';
        echo '<form method="post" action="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php" style="display:inline-block;">';
        echo '<input type="hidden" name="action" value="cancel_service">';
        echo '<input type="hidden" name="service_id" value="' . $service_to_edit['id'] . '">';
        echo '<input type="text" name="justificativa" placeholder="Justificativa para cancelamento" class="form-control" required>';
        echo '<input type="submit" name="submit" value="Cancelar Serviço" class="btn btn-danger">';
        echo '</form>';
    }

    echo '</div>'; // Fecha main_form de ações de fluxo
}


echo '<hr>';

// Listagem de Serviços
$services = PluginCatalogoService::getAllServices();

echo '<div class="main_form">';
echo '<div class="table-responsive">';
echo '<table class="tab_cadre_fixe">';
echo '<thead>';
echo '<tr>';
echo '<th>ID</th>';
echo '<th>Subcategoria</th>';
echo '<th>Título</th>';
echo '<th>Versão</th>';
echo '<th>PO Responsável</th>';
echo '<th>Usuário Criador</th>';
echo '<th>Status da Ficha</th>';
echo '<th>Ações</th>';
echo '</tr>';
</thead>';
echo '<tbody>';

if (count($services) > 0) {
    foreach ($services as $service) {
        echo '<tr>';
        echo '<td>' . $service['id'] . '</td>';
        echo '<td>' . Html::cleanOutputText($service['category_name'] . ' > ' . $service['subcategory_name']) . '</td>';
        echo '<td>' . Html::cleanOutputText($service['titulo']) . '</td>';
        echo '<td>' . Html::cleanOutputText($service['versao']) . '</td>'; // Exibe a versão
        echo '<td>' . Html::cleanOutputText($service['po_responsavel_firstname'] . ' ' . $service['po_responsavel_realname']) . '</td>';
        echo '<td>' . Html::cleanOutputText($service['usuario_criador_firstname'] . ' ' . $service['usuario_criador_realname']) . '</td>';
        echo '<td>' . Html::cleanOutputText($service['status_ficha']) . '</td>';
        echo '<td>';
        echo '<a href="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php?action=edit&id=' . $service['id'] . '" class="btn btn-primary btn-sm">Editar</a> ';
        echo '<a href="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/service.php?action=delete&id=' . $service['id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Tem certeza que deseja excluir este serviço?\');">Excluir</a>';
        echo '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="8">Nenhum serviço encontrado.</td></tr>';
}

echo '</tbody>';
echo '</table>';
echo '</div>';
echo '</div>';

// Finaliza a renderização da página do GLPI
Html::footer();
