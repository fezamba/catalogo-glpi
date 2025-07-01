<?php

class PluginCatalogoService {

    public static function getAllServices($status = null) { // Adicionado parâmetro $status
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

        if ($status !== null) { // Adicionado filtro por status
            $status_escaped = $DB->escape($status);
            $query .= " AND s.status_ficha = '$status_escaped'";
        }

        $query .= " ORDER BY s.titulo ASC";
        $result = $DB->query($query);
        if ($result) {
            while ($row = $DB->fetchAssoc($result)) {
                $services[] = $row;
            }
        } else {
            error_log("Erro ao buscar serviços: " . $DB->error());
        }
        return $services;
    }

    public static function getServiceById($id) {
        global $DB;
        $id = (int) $id;
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
                  WHERE s.id = '$id' AND s.is_active = 1";
        $result = $DB->query($query);
        if ($result && $DB->numrows($result) > 0) {
            return $DB->fetchAssoc($result);
        } else {
            error_log("Erro ao buscar serviço por ID: " . $DB->error());
            return false;
        }
    }

    public static function addService(
        $subcategories_id, $titulo, $descricao, $kbs, $area_especialista, $po_responsavel_users_id,
        $alcadas, $procedimento_excecao, $usuario_criador_users_id, $observacoes, $status_ficha,
        $data_revisao, $po_aprovador_users_id, $data_aprovacao,
        $justificativa_rejeicao, $codigo_ficha, $versao, $previous_version_id = NULL
    ) {
        global $DB;
        $subcategories_id = (int) $subcategories_id;
        $po_responsavel_users_id = (int) $po_responsavel_users_id;
        $usuario_criador_users_id = (int) $usuario_criador_users_id;
        $po_aprovador_users_id = (int) $po_aprovador_users_id;
        $previous_version_id = ($previous_version_id === NULL) ? "NULL" : (int) $previous_version_id;


        $titulo_escaped = $DB->escape($titulo);
        $descricao_escaped = $DB->escape($descricao);
        $kbs_escaped = $DB->escape($kbs);
        $area_especialista_escaped = $DB->escape($area_especialista);
        $alcadas_escaped = $DB->escape($alcadas);
        $procedimento_excecao_escaped = $DB->escape($procedimento_excecao);
        $observacoes_escaped = $DB->escape($observacoes);
        $status_ficha_escaped = $DB->escape($status_ficha);
        $data_revisao_escaped = $DB->escape($data_revisao);
        $data_aprovacao_escaped = $DB->escape($data_aprovacao);
        $justificativa_rejeicao_escaped = $DB->escape($justificativa_rejeicao);
        $codigo_ficha_escaped = $DB->escape($codigo_ficha);
        $versao_escaped = $DB->escape($versao);

        $query = "INSERT INTO `glpi_plugin_catalogo_servico` (
                    `subcategories_id`, `titulo`, `descricao`, `kbs`, `area_especialista`,
                    `po_responsavel_users_id`, `alcadas`, `procedimento_excecao`, `usuario_criador_users_id`,
                    `observacoes`, `status_ficha`, `data_revisao`, `po_aprovador_users_id`,
                    `data_aprovacao`, `justificativa_rejeicao`, `codigo_ficha`, `versao`, `previous_version_id`
                  ) VALUES (
                    '$subcategories_id', '$titulo_escaped', '$descricao_escaped', '$kbs_escaped',
                    '$area_especialista_escaped', " . ($po_responsavel_users_id > 0 ? "'$po_responsavel_users_id'" : "NULL") . ",
                    '$alcadas_escaped', '$procedimento_excecao_escaped', " . ($usuario_criador_users_id > 0 ? "'$usuario_criador_users_id'" : "NULL") . ",
                    '$observacoes_escaped', '$status_ficha_escaped', " . ($data_revisao_escaped ? "'$data_revisao_escaped'" : "NULL") . ",
                    " . ($po_aprovador_users_id > 0 ? "'$po_aprovador_users_id'" : "NULL") . ",
                    " . ($data_aprovacao_escaped ? "'$data_aprovacao_escaped'" : "NULL") . ",
                    '$justificativa_rejeicao_escaped', '$codigo_ficha_escaped', '$versao_escaped', " . $previous_version_id . "
                  )";
        $DB->query($query);

        if ($DB->affected_rows() > 0) {
            return $DB->insert_id();
        } else {
            error_log("Erro ao adicionar serviço: " . $DB->error());
            return false;
        }
    }

    public static function updateService(
        $id, $subcategories_id, $titulo, $descricao, $kbs, $area_especialista, $po_responsavel_users_id,
        $alcadas, $procedimento_excecao, $usuario_criador_users_id, $observacoes, $status_ficha,
        $data_revisao, $po_aprovador_users_id, $data_aprovacao,
        $justificativa_rejeicao, $codigo_ficha, $versao, $previous_version_id = NULL
    ) {
        global $DB;
        $id = (int) $id;
        $subcategories_id = (int) $subcategories_id;
        $po_responsavel_users_id = (int) $po_responsavel_users_id;
        $usuario_criador_users_id = (int) $usuario_criador_users_id;
        $po_aprovador_users_id = (int) $po_aprovador_users_id;
        $previous_version_id_sql = ($previous_version_id === NULL) ? "NULL" : (int) $previous_version_id;


        $titulo_escaped = $DB->escape($titulo);
        $descricao_escaped = $DB->escape($descricao);
        $kbs_escaped = $DB->escape($kbs);
        $area_especialista_escaped = $DB->escape($area_especialista);
        $alcadas_escaped = $DB->escape($alcadas);
        $procedimento_excecao_escaped = $DB->escape($procedimento_excecao);
        $observacoes_escaped = $DB->escape($observacoes);
        $status_ficha_escaped = $DB->escape($status_ficha);
        $data_revisao_escaped = $DB->escape($data_revisao);
        $data_aprovacao_escaped = $DB->escape($data_aprovacao);
        $justificativa_rejeicao_escaped = $DB->escape($justificativa_rejeicao);
        $codigo_ficha_escaped = $DB->escape($codigo_ficha);
        $versao_escaped = $DB->escape($versao);

        $query = "UPDATE `glpi_plugin_catalogo_servico` SET
                    `subcategories_id` = '$subcategories_id',
                    `titulo` = '$titulo_escaped',
                    `descricao` = '$descricao_escaped',
                    `kbs` = '$kbs_escaped',
                    `area_especialista` = '$area_especialista_escaped',
                    `po_responsavel_users_id` = " . ($po_responsavel_users_id > 0 ? "'$po_responsavel_users_id'" : "NULL") . ",
                    `alcadas` = '$alcadas_escaped',
                    `procedimento_excecao` = '$procedimento_excecao_escaped',
                    `usuario_criador_users_id` = " . ($usuario_criador_users_id > 0 ? "'$usuario_criador_users_id'" : "NULL") . ",
                    `observacoes` = '$observacoes_escaped',
                    `status_ficha` = '$status_ficha_escaped',
                    `data_revisao` = " . ($data_revisao_escaped ? "'$data_revisao_escaped'" : "NULL") . ",
                    `po_aprovador_users_id` = " . ($po_aprovador_users_id > 0 ? "'$po_aprovador_users_id'" : "NULL") . ",
                    `data_aprovacao` = " . ($data_aprovacao_escaped ? "'$data_aprovacao_escaped'" : "NULL") . ",
                    `justificativa_rejeicao` = '$justificativa_rejeicao_escaped',
                    `codigo_ficha` = '$codigo_ficha_escaped',
                    `versao` = '$versao_escaped',
                    `previous_version_id` = " . $previous_version_id_sql . ",
                    `updated_at` = NOW()
                  WHERE `id` = '$id'";
        $DB->query($query);

        if ($DB->affected_rows() > 0) {
            return true;
        } else {
            error_log("Erro ao atualizar serviço: " . $DB->error());
            return false;
        }
    }

    public static function deleteService($id) {
        global $DB;
        $id = (int) $id;
        $query = "UPDATE `glpi_plugin_catalogo_servico` SET `is_active` = 0, `updated_at` = NOW() WHERE `id` = '$id'";
        $DB->query($query);

        if ($DB->affected_rows() > 0) {
            return true;
        } else {
            error_log("Erro ao excluir serviço: " . $DB->error());
            return false;
        }
    }

    public static function getServiceRevisers($service_id) {
        global $DB;
        $service_id = (int) $service_id;
        $revisers = [];
        $query = "SELECT u.id, u.firstname, u.realname, u.name AS username
                  FROM `glpi_plugin_catalogo_servico_revisores` sr
                  JOIN `glpi_users` u ON sr.revisores_users_id = u.id
                  WHERE sr.servicos_id = '$service_id'";
        $result = $DB->query($query);
        if ($result) {
            while ($row = $DB->fetchAssoc($result)) {
                $revisers[] = $row;
            }
        }
        return $revisers;
    }

    public static function assignReviserToService($service_id, $user_id) {
        global $DB;
        $service_id = (int) $service_id;
        $user_id = (int) $user_id;

        $check_query = "SELECT COUNT(*) FROM `glpi_plugin_catalogo_servico_revisores`
                        WHERE `servicos_id` = '$service_id' AND `revisores_users_id` = '$user_id'";
        $check_result = $DB->query($check_query);
        if ($DB->fetchValue($check_result, 0, 0) > 0) {
            return true;
        }

        $query = "INSERT INTO `glpi_plugin_catalogo_servico_revisores` (`servicos_id`, `revisores_users_id`)
                  VALUES ('$service_id', '$user_id')";
        $DB->query($query);

        if ($DB->affected_rows() > 0) {
            return true;
        } else {
            error_log("Erro ao associar revisor ao serviço: " . $DB->error());
            return false;
        }
    }

    public static function unassignReviserFromService($service_id, $user_id) {
        global $DB;
        $service_id = (int) $service_id;
        $user_id = (int) $user_id;

        $query = "DELETE FROM `glpi_plugin_catalogo_servico_revisores`
                  WHERE `servicos_id` = '$service_id' AND `revisores_users_id` = '$user_id'";
        $DB->query($query);

        if ($DB->affected_rows() > 0) {
            return true;
        } else {
            error_log("Erro ao desassociar revisor do serviço: " . $DB->error());
            return false;
        }
    }

    public static function getGLPIUsersForDropdown() {
        global $DB;
        $users = [];
        $query = "SELECT `id`, `firstname`, `realname`, `name` FROM `glpi_users`
                  WHERE `is_active` = 1 AND `is_deleted` = 0
                  ORDER BY `firstname`, `realname` ASC";
        $result = $DB->query($query);
        if ($result) {
            while ($row = $DB->fetchAssoc($result)) {
                $full_name = trim($row['firstname'] . ' ' . $row['realname']);
                if (empty($full_name)) {
                    $full_name = $row['name'];
                }
                $users[] = ['id' => $row['id'], 'name' => $full_name];
            }
        }
        return $users;
    }

    /**
     * Cria uma nova versão de um serviço existente.
     * Copia todos os dados do serviço original para um novo registro.
     *
     * @param int $original_service_id O ID do serviço original.
     * @return int|false O ID do novo serviço (nova versão) ou false em caso de erro.
     */
    public static function createNewVersion($original_service_id) {
        global $DB;

        $original_service = self::getServiceById($original_service_id);
        if (!$original_service) {
            error_log("Serviço original não encontrado para criar nova versão: " . $original_service_id);
            return false;
        }

        // Incrementa a versão
        $new_version_number = (floatval($original_service['versao']) + 0.1);
        // Formata para ter sempre uma casa decimal, se necessário
        $new_version_string = number_format($new_version_number, 1, '.', '');


        // Prepara os dados para a nova versão
        $new_service_data = [
            'subcategories_id'          => $original_service['subcategories_id'],
            'titulo'                    => $original_service['titulo'],
            'descricao'                 => $original_service['descricao'],
            'kbs'                       => $original_service['kbs'],
            'area_especialista'         => $original_service['area_especialista'],
            'po_responsavel_users_id'   => $original_service['po_responsavel_users_id'],
            'alcadas'                   => $original_service['alcadas'],
            'procedimento_excecao'      => $original_service['procedimento_excecao'],
            'usuario_criador_users_id'  => Session::getLoginUserID(), // O usuário logado é o criador da nova versão
            'observacoes'               => $original_service['observacoes'],
            'status_ficha'              => PluginCatalogoApproval::STATUS_DRAFT, // Nova versão começa como rascunho
            'data_revisao'              => NULL,
            'po_aprovador_users_id'     => $original_service['po_aprovador_users_id'],
            'data_aprovacao'            => NULL,
            'justificativa_rejeicao'    => NULL,
            'codigo_ficha'              => $original_service['codigo_ficha'],
            'versao'                    => $new_version_string,
            'previous_version_id'       => $original_service_id, // Link para a versão anterior
            'is_active'                 => 1 // Nova versão ativa por padrão
        ];

        // Adiciona o novo serviço
        $new_service_id = self::addService(
            $new_service_data['subcategories_id'],
            $new_service_data['titulo'],
            $new_service_data['descricao'],
            $new_service_data['kbs'],
            $new_service_data['area_especialista'],
            $new_service_data['po_responsavel_users_id'],
            $new_service_data['alcadas'],
            $new_service_data['procedimento_excecao'],
            $new_service_data['usuario_criador_users_id'],
            $new_service_data['observacoes'],
            $new_service_data['status_ficha'],
            $new_service_data['data_revisao'],
            $new_service_data['po_aprovador_users_id'],
            $new_service_data['data_aprovacao'],
            $new_service_data['justificativa_rejeicao'],
            $new_service_data['codigo_ficha'],
            $new_service_data['versao'],
            $new_service_data['previous_version_id']
        );

        if ($new_service_id) {
            // Copiar revisores da versão anterior para a nova versão
            $original_revisers = self::getServiceRevisers($original_service_id);
            foreach ($original_revisers as $reviser) {
                self::assignReviserToService($new_service_id, $reviser['id']);
            }
            return $new_service_id;
        }
        return false;
    }
}
