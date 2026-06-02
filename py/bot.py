import os
import json
from dotenv import load_dotenv
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import mysql.connector
from google import genai

# 1. Carrega as variáveis do arquivo .env
load_dotenv()

# 2. Puxa a chave de forma segura
GOOGLE_API_KEY = os.getenv("GEMINI_API_KEY")

if not GOOGLE_API_KEY:
    raise ValueError("Chave da API não encontrada! Verifique seu arquivo .env")

# 3. Configura o NOVO cliente da API do Gemini
client = genai.Client(api_key=GOOGLE_API_KEY)

app = FastAPI()

# 4. Configuração do CORS (Impede que o navegador bloqueie o chat)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"], 
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# 5. Modelo de dados para receber a mensagem do Front-End
class MessageRequest(BaseModel):
    message: str
    user_id: int = 1  # Valor padrão para evitar erro se o front não enviar o ID
    product_id: int = None

# Função para conectar no banco de dados
def get_product_specs(product_id: int):
    try:
        conn = mysql.connector.connect(
            host="localhost",
            user="root",
            password="",
            database="techubby_db"
        )
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT nome, especificacoes_tecnicas FROM produtos WHERE id_produto = %s", (product_id,))
        product = cursor.fetchone()
        cursor.close()
        conn.close()
        return product
    except Exception as e:
        print(f"Erro ao conectar no banco: {e}")
        return None

# Rota principal que o JavaScript vai chamar
@app.post("/api/tio-claudio/chat")
async def chat_tio_claudio(payload: MessageRequest):
    
    # A Personalidade do Bot
    system_instruction = (
        "Você é o 'Tio Cláudio', um técnico de informática veterano com mais de 20 anos de experiência, "
        "especialista em hardware e assistente virtual do marketplace TecHubby. Você é muito prestativo, "
        "tem um tom levemente informal (como aquele seu tio gente boa que entende tudo de computadores), "
        "usa algumas gírias de tecnologia, mas é extremamente rigoroso com compatibilidade de peças. "
        "Se o cliente tentar combinar peças incompatíveis (ex: processador AMD em placa-mãe Intel), "
        "alerte-o imediatamente com bom humor e explique o motivo técnico."
    )
    
    # Injeta dados do produto se houver
    if payload.product_id:
        product_info = get_product_specs(payload.product_id)
        if product_info:
            specs_str = json.dumps(product_info['especificacoes_tecnicas'])
            system_instruction += (
                f"\n\nContexto Atual: O usuário está olhando o produto '{product_info['nome']}'. "
                f"As especificações técnicas dele vindas do banco de dados são: {specs_str}. "
            )

    try:
        # Gera a resposta no modelo novo
        response = client.models.generate_content(
            model='gemini-2.5-flash',
            contents=payload.message,
            config=genai.types.GenerateContentConfig(
                system_instruction=system_instruction,
            )
        )
        
        return {"reply": response.text}

    except Exception as e:
        print(f"Erro processando o Gemini: {e}") # Mostra no terminal caso dê erro
        raise HTTPException(status_code=500, detail=f"Erro ao processar o Gemini: {str(e)}")

if __name__ == "__main__":
    import uvicorn
    # Servidor rodando na porta 8080
    uvicorn.run(app, host="0.0.0.0", port=8080)