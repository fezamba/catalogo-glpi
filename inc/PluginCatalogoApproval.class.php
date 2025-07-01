<?php

class PluginCatalogoApproval {

    // Status possíveis para a ficha de serviço
    const STATUS_DRAFT              = 'rascunho'; // Em Cadastro
    const STATUS_IN_REVIEW          = 'em_revisao'; // Em revisão
    const STATUS_REVIEWED           = 'revisada'; // Revisada
    const STATUS_IN_APPROVAL        = 'em_aprovacao'; // Em aprovação
    const STATUS_APPROVED           = 'aprovada'; // Aprovada
    const STATUS_PUBLISHED          = 'publicado'; // Publicado
    const STATUS_CANCELED           = 'cancelada'; // Cancelada
    const STATUS_REJECTED_REVISER   = 'reprovado_revisor'; // Reprovado pelo revisor
    const STATUS_REJECTED_PO        = 'reprovado_po'; // Reprovado pelo PO
    const STATUS_REPLACED           = 'substituida'; // Substituída
    const STATUS_DISCONTINUED       = 'descontinuada'; // Descontinuada

    /**
     * Obtém serviços que estão em um determinado status ou que precisam de revisão/aprovação por um usuário.
     *
     * @param string|null $status O status a filtrar (ex: 'em_revisao', 'em_aprovacao'). Se null, retorna todos os ativos.
     * @param int|null    $user_id O ID do usuário GLPI para filtrar serviços onde ele é revisor ou aprovador.
     * @param string|null $role O papel do usuário ('revisor' ou 'aprovador').
     * @return array Array de serviços.
     */
    public static function getServicesByStatus($status = null, $user_id = null, $role = null) {
        global $DB;
        $services = [];
        $query = "SELECT s.*,
                         sub.titulo AS subcategory_name,
                         cat.titulo AS category_name,
                         u_po.firstname AS po_responsavel_firstname, u_po.realname AS po_responsavel_realname,
                         u_creator.firstname AS usuario_criador_firstname, u_creator.realname AS usuario_criador_realname,
                         u_approver.firstname AS po_aprovador_firstname, u_approver.realname AS po_aprovador_realname
                  FROM `glpi_plugin_catalogo_servico` s
                  LEFT JOIN `glpi_plugin_catalogo_subcategoria` sub ON s.subcategories_id = sub.id
                  LEFT JOIN `glpi_plugin_catalogo_categoria` cat ON sub.categorias_id = cat.id
                  LEFT JOIN `glpi_users` u_po ON s.po_responsavel_users_id = u_po.id
                  LEFT JOIN `glpi_users` u_creator ON s.usuario_criador_users_id = u_creator.id
                  LEFT JOIN `glpi_users` u_approver ON s.po_aprovador_users_id = u_approver.id
                  WHERE s.is_active = 1";

        if ($status) {
            $status_escaped = $DB->escape($status);
            $query .= " AND s.status_ficha = '$status_escaped'";
        }

        if ($user_id && $role) {
            $user_id = (int) $user_id;
            if ($role === 'revisor') {
                $query .= " AND s.id IN (SELECT servicos_id FROM `glpi_plugin_catalogo_servico_revisores` WHERE revisores_users_id = '$user_id')";
                $query .= " AND s.status_ficha = '" . self::STATUS_IN_REVIEW . "'";
            } elseif ($role === 'aprovador') {
                $query .= " AND s.po_aprovador_users_id = '$user_id'";
                $query .= " AND s.status_ficha = '" . self::STATUS_IN_APPROVAL . "'";
            }
        }

        $query .= " ORDER BY s.titulo ASC";

        $result = $DB->query($query);
        if ($result) {
            while ($row = $DB->fetchAssoc($result)) {
                $services[] = $row;
            }
        } else {
            error_log("Erro ao buscar serviços por status: " . $DB->error());
        }
        return $services;
    }

    /**
     * Atualiza o status da ficha de um serviço.
     *
     * @param int    $service_id O ID do serviço.
     * @param string $new_status O novo status.
     * @param string $justificativa Justificativa para a mudança de status (opcional).
     * @return bool True se a atualização foi bem-sucedida, false caso contrário.
     */
    public static function updateServiceStatus($service_id, $new_status, $justificativa = '') {
        global $DB;
        $service_id = (int) $service_id;
        $new_status_escaped = $DB->escape($new_status);
        $justificativa_escaped = $DB->escape($justificativa);

        $update_fields = "`status_ficha` = '$new_status_escaped', `updated_at` = NOW()";

        if ($new_status === self::STATUS_APPROVED || $new_status === self::STATUS_PUBLISHED) {
            $update_fields .= ", `data_aprovacao` = NOW()";
            $update_fields .= ", `justificativa_rejeicao` = NULL";
        } elseif ($new_status === self::STATUS_REJECTED_REVISER || $new_status === self::STATUS_REJECTED_PO || $new_status === self::STATUS_CANCELED || $new_status === self::STATUS_REPLACED || $new_status === self::STATUS_DISCONTINUED) {
            $update_fields .= ", `justificativa_rejeicao` = '$justificativa_escaped'";
            $update_fields .= ", `data_aprovacao` = NULL";
        } elseif ($new_status === self::STATUS_IN_REVIEW || $new_status === self::STATUS_IN_APPROVAL || $new_status === self::STATUS_DRAFT || $new_status === self::STATUS_REVIEWED) {
            $update_fields .= ", `data_aprovacao` = NULL, `justificativa_rejeicao` = NULL";
        }

        $query = "UPDATE `glpi_plugin_catalogo_servico` SET $update_fields WHERE `id` = '$service_id'";
        $DB->query($query);

        if ($DB->affected_rows() > 0) {
            return true;
        } else {
            error_log("Erro ao atualizar status do serviço: " . $DB->error());
            return false;
        }
    }

    /**
     * Verifica se o usuário logado é um revisor para o serviço.
     *
     * @param int $service_id O ID do serviço.
     * @param int $user_id O ID do usuário GLPI.
     * @return bool True se o usuário é revisor do serviço, false caso contrário.
     */
    public static function isUserReviserForService($service_id, $user_id) {
        global $DB;
        $service_id = (int) $service_id;
        $user_id = (int) $user_id;

        $query = "SELECT COUNT(*) FROM `glpi_plugin_catalogo_servico_revisores`
                  WHERE `servicos_id` = '$service_id' AND `revisores_users_id` = '$user_id'";
        $result = $DB->query($query);
        return ($DB->fetchValue($result, 0, 0) > 0);
    }

    /**
     * Verifica se o usuário logado é o PO Aprovador para o serviço.
     *
     * @param int $service_id O ID do serviço.
     * @param int $user_id O ID do usuário GLPI.
     * @return bool True se o usuário é o PO Aprovador do serviço, false caso contrário.
     */
    public static function isUserPOApproverForService($service_id, $user_id) {
        global $DB;
        $service_id = (int) $service_id;
        $user_id = (int) $user_id;

        $query = "SELECT COUNT(*) FROM `glpi_plugin_catalogo_servico`
                  WHERE `id` = '$service_id' AND `po_aprovador_users_id` = '$user_id'";
        $result = $DB->query($query);
        return ($DB->fetchValue($result, 0, 0) > 0);
    }
}
