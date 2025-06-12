import pandas as pd
import mysql.connector
from datetime import datetime

excel_path = 'c:/Users/fpetit/Desktop/workspace/PHP-CATALOGO/bd_script (refatorar)/script_pandas/catalogo.xlsx'
abas = [
    'Credenciais de Acesso',
    'Segurança',
    'Infraestrutura',
    'Microsoft 365',
    'Sistemas e Portais',
    'Softwares Homologados'
]
descricao_template = "Subcategoria vinculada à área de {categoria}. Esta entrada foi criada automaticamente."

conn = mysql.connector.connect(
    host='127.0.0.1',
    user='root',
    password='sefazfer123@',
    database='catalogo-teste'
)
cursor = conn.cursor()
agora = datetime.now()

for aba in abas:
    df = pd.read_excel(excel_path, sheet_name=aba)
    df.columns = df.columns.str.strip() # Remove espaços extras nos nomes das colunas

    categoria = aba.split(" (")[0]  # Limpa o "(Feito)" se houver
    coluna_subcat = next((col for col in df.columns if 'sub' in col.lower()), None)
    if not coluna_subcat:
        print(f"  [ERRO] Colunas da aba '{aba}':", df.columns.tolist())
        print(f"[ERRO] Coluna de subcategoria não encontrada na aba '{aba}'")
        continue

    subcategorias = df[coluna_subcat].dropna().unique()

    cursor.execute("SELECT ID FROM categoria WHERE Titulo = %s", (categoria,))
    resultado = cursor.fetchone()
    if not resultado:
        print(f"[ERRO] Categoria '{categoria}' não encontrada.")
        continue

    id_categoria = resultado[0]
    print(f"[INFO] Lendo aba: {aba}")

    for sub in subcategorias:
        descricao = descricao_template.format(categoria=categoria)
        cursor.execute("""
            INSERT INTO subcategoria (Titulo, Descricao, ID_Categoria, UltimaAtualizacao)
            VALUES (%s, %s, %s, %s)
        """, (sub, descricao, id_categoria, agora))
        print(f"  ↳ Sub: {sub}")

conn.commit()
cursor.close()
conn.close()
print("Subcategorias importadas com sucesso.")