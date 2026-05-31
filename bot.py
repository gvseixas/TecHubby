import os
from dotenv import load_dotenv
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import mysql.connector
from google import genai
import json

# Carrega as variáveis do arquivo .env
load_dotenv()

# Puxa a chave de forma segura
GOOGLE_API_KEY = os.getenv("GEMINI_API_KEY")

if not GOOGLE_API_KEY:
    raise ValueError("Chave da API não encontrada! Verifique seu arquivo .env")

# Configura o NOVO cliente da API do Gemini
client = genai.Client(api_key=GOOGLE_API_KEY)

app = FastAPI()

class MessageRequest(BaseModel):
    user_id: int
    message: str
    product_id: int = None

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

@app.post("/api/tio-claudio/chat")
async def chat_tio_claudio(payload: MessageRequest):
    
    system_instruction = (
        "Você é o 'Tio Cláudio', um técnico de informática veterano com mais de 20 anos de experiência, "
        "especialista em hardware e assistente virtual do marketplace TecHubby. Você é muito prestativo, "
        "tem um tom levemente informal (como aquele seu tio gente boa que entende tudo de computadores), "
        "usa algumas gírias de tecnologia, mas é extremamente rigoroso com compatibilidade de peças. "
        "Se o cliente tentar combinar peças incompatíveis (ex: processador AMD em placa-mãe Intel), "
        "alerte-o imediatamente com bom humor e explique o motivo técnico."
    )
    
    if payload.product_id:
        product_info = get_product_specs(payload.product_id)
        if product_info:
            specs_str = json.dumps(product_info['especificacoes_tecnicas'])
            system_instruction += (
                f"\n\nContexto Atual: O usuário está olhando o produto '{product_info['nome']}'. "
                f"As especificações técnicas dele vindas do banco de dados são: {specs_str}. "
            )

    try:
        # Novo formato de envio do Google GenAI
        response = client.models.generate_content(
            model='gemini-2.5-flash',
            contents=payload.message,
            config=genai.types.GenerateContentConfig(
                system_instruction=system_instruction,
            )
        )
        
        return {"reply": response.text}

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Erro ao processar o Gemini: {str(e)}")

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8080)