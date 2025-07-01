<?php

function plugin_init_catalogo() {
    global $PLUGIN_HOOKS;

    $plugin = new Plugin();
    $plugin->init(
        'catalogo',
        true,
        true
    );

    $PLUGIN_HOOKS['menu_entry']['catalogo'] = 'plugin_catalogo_add_menu';

    $PLUGIN_HOOKS['add_hook_files']['catalogo'] = 'hook.php';

    return true;
}

function plugin_catalogo_add_menu() {
    global $CFG_GLPI;

    Menu::addEntry(
        'catalogo',
        $CFG_GLPI['root_doc'] . '/plugins/catalogo/front/main.php',
        'Catálogo de Serviços',
        'ti',
        'plugins',
        'CatalogoPlugin'
    );
}

function plugin_catalogo_install() {
    global $DB;

    // Tabela: categoria
    $query_categoria = "CREATE TABLE IF NOT EXISTS `glpi_plugin_catalogo_categoria` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `titulo` VARCHAR(255) NOT NULL,
        `descricao` TEXT DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
    $DB->query($query_categoria);

    // Tabela: subcategoria
    $query_subcategoria = "CREATE TABLE IF NOT EXISTS `glpi_plugin_catalogo_subcategoria` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `categorias_id` INT(11) NOT NULL,
        `titulo` VARCHAR(255) NOT NULL,
        `descricao` TEXT DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`),
        INDEX (`categorias_id`),
        FOREIGN KEY (`categorias_id`) REFERENCES `glpi_plugin_catalogo_categoria`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
    $DB->query($query_subcategoria);

    // Tabela: servico (com previous_version_id)
    $query_servico = "CREATE TABLE IF NOT EXISTS `glpi_plugin_catalogo_servico` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `subcategories_id` INT(11) NOT NULL,
        `titulo` VARCHAR(255) NOT NULL,
        `descricao` TEXT DEFAULT NULL,
        `kbs` TEXT DEFAULT NULL,
        `area_especialista` VARCHAR(255) DEFAULT NULL,
        `po_responsavel_users_id` INT(11) DEFAULT NULL,
        `alcadas` TEXT DEFAULT NULL,
        `procedimento_excecao` TEXT DEFAULT NULL,
        `usuario_criador_users_id` INT(11) DEFAULT NULL,
        `observacoes` TEXT DEFAULT NULL,
        `status_ficha` VARCHAR(100) DEFAULT NULL,
        `data_revisao` DATETIME DEFAULT NULL,
        `po_aprovador_users_id` INT(11) DEFAULT NULL,
        `data_aprovacao` DATETIME DEFAULT NULL,
        `justificativa_rejeicao` TEXT DEFAULT NULL,
        `codigo_ficha` VARCHAR(100) DEFAULT NULL,
        `versao` VARCHAR(50) DEFAULT NULL,
        `previous_version_id` INT(11) DEFAULT NULL, -- Nova coluna
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`),
        INDEX (`subcategories_id`),
        INDEX (`po_responsavel_users_id`),
        INDEX (`usuario_criador_users_id`),
        INDEX (`po_aprovador_users_id`),
        INDEX (`previous_version_id`), -- Novo índice
        FOREIGN KEY (`subcategories_id`) REFERENCES `glpi_plugin_catalogo_subcategoria`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        FOREIGN KEY (`po_responsavel_users_id`) REFERENCES `glpi_users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
        FOREIGN KEY (`usuario_criador_users_id`) REFERENCES `glpi_users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
        FOREIGN KEY (`po_aprovador_users_id`) REFERENCES `glpi_users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
        FOREIGN KEY (`previous_version_id`) REFERENCES `glpi_plugin_catalogo_servico`(`id`) ON DELETE SET NULL ON UPDATE CASCADE -- Nova FK
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
    $DB->query($query_servico);

    // Tabela: servico_revisores (tabela de junção, agora referenciando glpi_users.id)
    $query_servico_revisores = "CREATE TABLE IF NOT EXISTS `glpi_plugin_catalogo_servico_revisores` (
        `servicos_id` INT(11) NOT NULL,
        `revisores_users_id` INT(11) NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`servicos_id`, `revisores_users_id`),
        INDEX (`revisores_users_id`),
        FOREIGN KEY (`servicos_id`) REFERENCES `glpi_plugin_catalogo_servico`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        FOREIGN KEY (`revisores_users_id`) REFERENCES `glpi_users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
    $DB->query($query_servico_revisores);

    // Tabela: diretriz
    $query_diretriz = "CREATE TABLE IF NOT EXISTS `glpi_plugin_catalogo_diretriz` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `servicos_id` INT(11) NOT NULL,
        `titulo` VARCHAR(255) NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`),
        INDEX (`servicos_id`),
        FOREIGN KEY (`servicos_id`) REFERENCES `glpi_plugin_catalogo_servico`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
    $DB->query($query_diretriz);

    // Tabela: itemdiretriz
    $query_itemdiretriz = "CREATE TABLE IF NOT EXISTS `glpi_plugin_catalogo_itemdiretriz` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `diretrizes_id` INT(11) NOT NULL,
        `conteudo` TEXT DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`),
        INDEX (`diretrizes_id`),
        FOREIGN KEY (`diretrizes_id`) REFERENCES `glpi_plugin_catalogo_diretriz`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
    $DB->query($query_itemdiretriz);

    // Tabela: padrao
    $query_padrao = "CREATE TABLE IF NOT EXISTS `glpi_plugin_catalogo_padrao` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `servicos_id` INT(11) NOT NULL,
        `titulo` VARCHAR(255) NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`),
        INDEX (`servicos_id`),
        FOREIGN KEY (`servicos_id`) REFERENCES `glpi_plugin_catalogo_servico`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
    $DB->query($query_padrao);

    // Tabela: itempadrao
    $query_itempadrao = "CREATE TABLE IF NOT EXISTS `glpi_plugin_catalogo_itempadrao` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `padroes_id` INT(11) NOT NULL,
        `conteudo` TEXT DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`),
        INDEX (`padroes_id`),
        FOREIGN KEY (`padroes_id`) REFERENCES `glpi_plugin_catalogo_padrao`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
    $DB->query($query_itempadrao);

    // Tabela: checklist
    $query_checklist = "CREATE TABLE IF NOT EXISTS `glpi_plugin_catalogo_checklist` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `servicos_id` INT(11) NOT NULL,
        `nome_item` VARCHAR(255) NOT NULL,
        `observacao` TEXT DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`),
        INDEX (`servicos_id`),
        FOREIGN KEY (`servicos_id`) REFERENCES `glpi_plugin_catalogo_servico`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
    $DB->query($query_checklist);

    return true;
}

function plugin_catalogo_uninstall() {
    global $DB;

    // Ordem inversa de exclusão para evitar problemas de chaves estrangeiras
    $DB->query("DROP TABLE IF EXISTS `glpi_plugin_catalogo_itemdiretriz`;");
    $DB->query("DROP TABLE IF EXISTS `glpi_plugin_catalogo_itempadrao`;");
    $DB->query("DROP TABLE IF EXISTS `glpi_plugin_catalogo_servico_revisores`;");
    $DB->query("DROP TABLE IF EXISTS `glpi_plugin_catalogo_checklist`;");
    $DB->query("DROP TABLE IF EXISTS `glpi_plugin_catalogo_diretriz`;");
    $DB->query("DROP TABLE IF EXISTS `glpi_plugin_catalogo_padrao`;");
    $DB->query("DROP TABLE IF EXISTS `glpi_plugin_catalogo_servico`;");
    $DB->query("DROP TABLE IF EXISTS `glpi_plugin_catalogo_subcategoria`;");
    $DB->query("DROP TABLE IF EXISTS `glpi_plugin_catalogo_categoria`;");

    return true;
}
