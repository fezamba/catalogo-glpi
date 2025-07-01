<?php

include ('../../../inc/includes.php');
require_once GLPI_ROOT . '/inc/plugin.class.php';
require_once GLPI_ROOT . '/plugins/catalogo/inc/PluginCatalogoService.class.php';
require_once GLPI_ROOT . '/plugins/catalogo/inc/PluginCatalogoApproval.class.php';

// Verifica se o usuário está logado
if (!Session::getLoginUserID()) {
    Html::displayNotFoundError(); // Ou redirecionar para a página de login
}

$current_user_id = Session::getLoginUserID();

// Processa ações de revisão/aprovação
if (isset($_POST['submit_review_action'])) {
    $service_id = $_POST['service_id'] ?? 0;
    $action = $_POST['review_action'] ?? ''; // 'review_complete', 'approve', 'reject_reviser', 'reject_po'
    $justificativa = $_POST['justificativa'] ?? '';

    $service = PluginCatalogoService::getServiceById($service_id);
    if (!$service) {
        Session::addMessageAfterRedirect('Serviço não encontrado.', true, ERROR);
        Html::redirect($CFG_GLPI['root_doc'] . '/plugins/catalogo/front/manage_review.php');
    }

    $success = false;
    $message = '';

    switch ($action) {
        case 'review_complete':
            if (PluginCatalogoApproval::isUserReviserForService($service_id, $current_user_id) && $service['status_ficha'] === PluginCatalogoApproval::STATUS_IN_REVIEW) {
                $success = PluginCatalogoApproval::updateServiceStatus($service_id, PluginCatalogoApproval::STATUS_REVIEWED);
                $message = $success ? 'Revisão concluída com sucesso!' : 'Erro ao concluir revisão.';
            } else {
                $message = 'Você não tem permissão para realizar esta ação de revisão ou o status do serviço não permite.';
            }
            break;

        case 'approve':
            if (PluginCatalogoApproval::isUserPOApproverForService($service_id, $current_user_id) && $service['status_ficha'] === PluginCatalogoApproval::STATUS_IN_APPROVAL) {
                $success = PluginCatalogoApproval::updateServiceStatus($service_id, PluginCatalogoApproval::STATUS_APPROVED);
                $message = $success ? 'Serviço aprovado com sucesso!' : 'Erro ao aprovar serviço.';
            } else {
                $message = 'Você não tem permissão para aprovar este serviço ou o status do serviço não permite.';
            }
            break;

        case 'reject_reviser':
            if (PluginCatalogoApproval::isUserReviserForService($service_id, $current_user_id) && $service['status_ficha'] === PluginCatalogoApproval::STATUS_IN_REVIEW) {
                $success = PluginCatalogoApproval::updateServiceStatus($service_id, PluginCatalogoApproval::STATUS_REJECTED_REVISER, $justificativa);
                $message = $success ? 'Revisão rejeitada com sucesso!' : 'Erro ao rejeitar revisão.';
            } else {
                $message = 'Você não tem permissão para rejeitar esta revisão ou o status do serviço não permite.';
            }
            break;

        case 'reject_po':
            if (PluginCatalogoApproval::isUserPOApproverForService($service_id, $current_user_id) && $service['status_ficha'] === PluginCatalogoApproval::STATUS_IN_APPROVAL) {
                $success = PluginCatalogoApproval::updateServiceStatus($service_id, PluginCatalogoApproval::STATUS_REJECTED_PO, $justificativa);
                $message = $success ? 'Aprovação rejeitada com sucesso!' : 'Erro ao rejeitar aprovação.';
            } else {
                $message = 'Você não tem permissão para rejeitar esta aprovação ou o status do serviço não permite.';
            }
            break;
    }

    if ($success) {
        Session::addMessageAfterRedirect($message, true, INFO);
    } else {
        Session::addMessageAfterRedirect($message, true, ERROR);
    }
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/catalogo/front/manage_review.php');
}


// Inicia a renderização da página do GLPI
Html::header('Gerenciar Revisões e Aprovações', $_SERVER['PHP_SELF'], 'plugins', 'catalogo');

echo '<div class="main_form">';
echo '<h2 class="section_header">Minhas Revisões e Aprovações</h2>';

// Serviços para Revisão (para o usuário logado como revisor)
$services_for_review = PluginCatalogoApproval::getServicesByStatus(PluginCatalogoApproval::STATUS_IN_REVIEW, $current_user_id, 'revisor');

echo '<h3>Serviços Aguardando Minha Revisão:</h3>';
if (count($services_for_review) > 0) {
    echo '<div class="table-responsive">';
    echo '<table class="tab_cadre_fixe">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Título do Serviço</th>';
    echo '<th>Status</th>';
    echo '<th>Ações</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    foreach ($services_for_review as $service) {
        echo '<tr>';
        echo '<td>' . $service['id'] . '</td>';
        echo '<td><a href="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/view_service.php?id=' . $service['id'] . '">' . Html::cleanOutputText($service['titulo']) . '</a></td>';
        echo '<td>' . Html::cleanOutputText($service['status_ficha']) . '</td>';
        echo '<td>';
        echo '<form method="post" action="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/manage_review.php" style="display:inline-block;">';
        echo '<input type="hidden" name="service_id" value="' . $service['id'] . '">';
        echo '<input type="hidden" name="review_action" value="review_complete">';
        echo '<input type="submit" name="submit_review_action" value="Marcar como Revisado" class="btn btn-success btn-sm">';
        echo '</form> ';
        echo '<form method="post" action="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/manage_review.php" style="display:inline-block;">';
        echo '<input type="hidden" name="service_id" value="' . $service['id'] . '">';
        echo '<input type="hidden" name="review_action" value="reject_reviser">';
        echo '<input type="text" name="justificativa" placeholder="Justificativa (obrigatório para rejeitar)" class="form-control" required>';
        echo '<input type="submit" name="submit_review_action" value="Rejeitar Revisão" class="btn btn-danger btn-sm" onclick="return confirm(\'Tem certeza que deseja rejeitar esta revisão?\');">';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
} else {
    echo '<p>Nenhum serviço aguardando sua revisão.</p>';
}

echo '<hr>';

// Serviços para Aprovação (para o usuário logado como aprovador)
$services_for_approval = PluginCatalogoApproval::getServicesByStatus(PluginCatalogoApproval::STATUS_IN_APPROVAL, $current_user_id, 'aprovador');

echo '<h3>Serviços Aguardando Minha Aprovação:</h3>';
if (count($services_for_approval) > 0) {
    echo '<div class="table-responsive">';
    echo '<table class="tab_cadre_fixe">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Título do Serviço</th>';
    echo '<th>Status</th>';
    echo '<th>Ações</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    foreach ($services_for_approval as $service) {
        echo '<tr>';
        echo '<td>' . $service['id'] . '</td>';
        echo '<td><a href="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/view_service.php?id=' . $service['id'] . '">' . Html::cleanOutputText($service['titulo']) . '</a></td>';
        echo '<td>' . Html::cleanOutputText($service['status_ficha']) . '</td>';
        echo '<td>';
        echo '<form method="post" action="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/manage_review.php" style="display:inline-block;">';
        echo '<input type="hidden" name="service_id" value="' . $service['id'] . '">';
        echo '<input type="hidden" name="review_action" value="approve">';
        echo '<input type="submit" name="submit_review_action" value="Aprovar" class="btn btn-success btn-sm">';
        echo '</form> ';
        echo '<form method="post" action="' . $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/manage_review.php" style="display:inline-block;">';
        echo '<input type="hidden" name="service_id" value="' . $service['id'] . '">';
        echo '<input type="hidden" name="review_action" value="reject_po">';
        echo '<input type="text" name="justificativa" placeholder="Justificativa (obrigatório para rejeitar)" class="form-control" required>';
        echo '<input type="submit" name="submit_review_action" value="Rejeitar Aprovação" class="btn btn-danger btn-sm" onclick="return confirm(\'Tem certeza que deseja rejeitar esta aprovação?\');">';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
} else {
    echo '<p>Nenhum serviço aguardando sua aprovação.</p>';
}

echo '</div>'; // Fecha main_form

// Finaliza a renderização da página do GLPI
Html::footer();
