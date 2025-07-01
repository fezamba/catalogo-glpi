<?php

class PluginCatalogoSubcategory {

    public static function getAllSubcategories() {
        global $DB;
        $subcategories = [];
        $query = "SELECT s.*, c.titulo AS category_name
                  FROM `glpi_plugin_catalogo_subcategoria` s
                  LEFT JOIN `glpi_plugin_catalogo_categoria` c ON s.categorias_id = c.id
                  WHERE s.is_active = 1
                  ORDER BY s.titulo ASC";
        $result = $DB->query($query);
        if ($result) {
            while ($row = $DB->fetchAssoc($result)) {
                $subcategories[] = $row;
            }
        } else {
            error_log("Erro ao buscar subcategorias: " . $DB->error());
        }
        return $subcategories;
    }

    public static function getSubcategoryById($id) {
        global $DB;
        $id = (int) $id;
        $query = "SELECT s.*, c.titulo AS category_name
                  FROM `glpi_plugin_catalogo_subcategoria` s
                  LEFT JOIN `glpi_plugin_catalogo_categoria` c ON s.categorias_id = c.id
                  WHERE s.id = '$id' AND s.is_active = 1";
        $result = $DB->query($query);
        if ($result && $DB->numrows($result) > 0) {
            return $DB->fetchAssoc($result);
        } else {
            error_log("Erro ao buscar subcategoria por ID: " . $DB->error());
            return false;
        }
    }

    public static function addSubcategory($categorias_id, $titulo, $descricao) {
        global $DB;
        $categorias_id = (int) $categorias_id;
        $titulo_escaped = $DB->escape($titulo);
        $descricao_escaped = $DB->escape($descricao);

        $query = "INSERT INTO `glpi_plugin_catalogo_subcategoria` (`categorias_id`, `titulo`, `descricao`)
                  VALUES ('$categorias_id', '$titulo_escaped', '$descricao_escaped')";
        $DB->query($query);

        if ($DB->affected_rows() > 0) {
            return $DB->insert_id();
        } else {
            error_log("Erro ao adicionar subcategoria: " . $DB->error());
            return false;
        }
    }

    public static function updateSubcategory($id, $categorias_id, $titulo, $descricao) {
        global $DB;
        $id = (int) $id;
        $categorias_id = (int) $categorias_id;
        $titulo_escaped = $DB->escape($titulo);
        $descricao_escaped = $DB->escape($descricao);

        $query = "UPDATE `glpi_plugin_catalogo_subcategoria`
                  SET `categorias_id` = '$categorias_id', `titulo` = '$titulo_escaped', `descricao` = '$descricao_escaped', `updated_at` = NOW()
                  WHERE `id` = '$id'";
        $DB->query($query);

        if ($DB->affected_rows() > 0) {
            return true;
        } else {
            error_log("Erro ao atualizar subcategoria: " . $DB->error());
            return false;
        }
    }

    public static function deleteSubcategory($id) {
        global $DB;
        $id = (int) $id;
        $query = "UPDATE `glpi_plugin_catalogo_subcategoria` SET `is_active` = 0, `updated_at` = NOW() WHERE `id` = '$id'";
        $DB->query($query);

        if ($DB->affected_rows() > 0) {
            return true;
        } else {
            error_log("Erro ao excluir subcategoria: " . $DB->error());
            return false;
        }
    }
}
