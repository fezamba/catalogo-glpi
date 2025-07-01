<?php

class PluginCatalogoCategory {

    public static function getAllCategories() {
        global $DB;
        $categories = [];
        $query = "SELECT * FROM `glpi_plugin_catalogo_categoria` WHERE `is_active` = 1 ORDER BY `titulo` ASC";
        $result = $DB->query($query);
        if ($result) {
            while ($row = $DB->fetchAssoc($result)) {
                $categories[] = $row;
            }
        } else {
            error_log("Erro ao buscar categorias: " . $DB->error());
        }
        return $categories;
    }

    public static function getCategoryById($id) {
        global $DB;
        $id = (int) $id;
        $query = "SELECT * FROM `glpi_plugin_catalogo_categoria` WHERE `id` = '$id' AND `is_active` = 1";
        $result = $DB->query($query);
        if ($result && $DB->numrows($result) > 0) {
            return $DB->fetchAssoc($result);
        } else {
            error_log("Erro ao buscar categoria por ID: " . $DB->error());
            return false;
        }
    }

    public static function addCategory($titulo, $descricao) {
        global $DB;
        $titulo_escaped = $DB->escape($titulo);
        $descricao_escaped = $DB->escape($descricao);

        $query = "INSERT INTO `glpi_plugin_catalogo_categoria` (`titulo`, `descricao`)
                  VALUES ('$titulo_escaped', '$descricao_escaped')";
        $DB->query($query);

        if ($DB->affected_rows() > 0) {
            return $DB->insert_id();
        } else {
            error_log("Erro ao adicionar categoria: " . $DB->error());
            return false;
        }
    }

    public static function updateCategory($id, $titulo, $descricao) {
        global $DB;
        $id = (int) $id;
        $titulo_escaped = $DB->escape($titulo);
        $descricao_escaped = $DB->escape($descricao);

        $query = "UPDATE `glpi_plugin_catalogo_categoria`
                  SET `titulo` = '$titulo_escaped', `descricao` = '$descricao_escaped', `updated_at` = NOW()
                  WHERE `id` = '$id'";
        $DB->query($query);

        if ($DB->affected_rows() > 0) {
            return true;
        } else {
            error_log("Erro ao atualizar categoria: " . $DB->error());
            return false;
        }
    }

    public static function deleteCategory($id) {
        global $DB;
        $id = (int) $id;
        // Marca como inativo em vez de excluir para manter a integridade referencial
        $query = "UPDATE `glpi_plugin_catalogo_categoria` SET `is_active` = 0, `updated_at` = NOW() WHERE `id` = '$id'";
        $DB->query($query);

        if ($DB->affected_rows() > 0) {
            return true;
        } else {
            error_log("Erro ao excluir categoria: " . $DB->error());
            return false;
        }
    }
}
