import mysql.connector

# Conectar ao banco
conn = mysql.connector.connect(
    host='127.0.0.1',
    user='root',
    password='sefazfer123@',
    database='catalogo-teste'
)
cursor = conn.cursor()

# Desativa restrições de chave estrangeira
cursor.execute("SET FOREIGN_KEY_CHECKS = 0;")

# Ordem: filhos → pais
tabelas = [
    "itemdiretriz",
    "diretriz",
    "itempadrao",
    "padrao",
    "checklist",
    "servico_atendimento",
    "servico_sistema",
    "servico_equipe_externa",
    "servico_software",
    "servico"
]

for tabela in tabelas:
    print(f"Truncando {tabela}...")
    cursor.execute(f"TRUNCATE TABLE {tabela};")

# Ativa novamente as restrições de chave estrangeira
cursor.execute("SET FOREIGN_KEY_CHECKS = 1;")

conn.commit()
cursor.close()
conn.close()
print("Todas as tabelas foram truncadas com sucesso.")