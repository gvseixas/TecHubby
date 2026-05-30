from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import mysql.connector
import google.generativeai as genai
import json

app = FastAPI()

# Configura a API do Gemini com a sua chave gratuita
GOOGLE_API_KEY = "CHAVE_REMOVIDA_POR_SEGURANCA"
genai.configure(api_key=GOOGLE_API_KEY)

# Modelo para receber a requisição do Front-End (HTML/JS)
class MessageRequest(BaseModel):
    user_id: int
    message: str
    product_id: int = None # Opcional: ID do hardware que o usuário está olhando

# Função para buscar especificações do produto no MySQL
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
    
    # 1. Definindo a personalidade marcante do Tio Cláudio
    system_instruction = (
        "Você é o 'Tio Cláudio', um técnico de informática veterano com mais de 20 anos de experiência, "
        "especialista em hardware e assistente virtual do marketplace TecHubby. Você é muito prestativo, "
        "tem um tom levemente informal (como aquele seu tio gente boa que entende tudo de computadores), "
        "usa algumas gírias de tecnologia, mas é extremamente rigoroso com compatibilidade de peças. "
        "Se o cliente tentar combinar peças incompatíveis (ex: processador AMD em placa-mãe Intel, ou memória DDR4 em slot DDR5), "
        "alerte-o imediatamente com bom humor e explique o motivo técnico."
    )
    
    # 2. Injeta o contexto do produto se o usuário estiver em uma página de produto
    if payload.product_id:
        product_info = get_product_specs(payload.product_id)
        if product_info:
            specs_str = json.dumps(product_info['especificacoes_tecnicas'])
            system_instruction += (
                f"\n\nContexto Atual: O usuário está olhando o produto '{product_info['nome']}'. "
                f"As especificações técnicas dele vindas do banco de dados são: {specs_str}. "
                f"Use esses dados para responder dúvidas de compatibilidade caso o usuário pergunte algo relacionado."
            )

    try:
        # 3. Inicializa o modelo do Gemini aplicando as instruções de sistema (personalidade)
        model = genai.GenerativeModel(
            model_name="gemini-1.5-flash",
            system_instruction=system_instruction
        )
        
        # 4. Envia a mensagem do usuário e recebe a resposta da IA
        response = model.generate_content(payload.message)
        
        return {"reply": response.text}

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Erro ao processar o Gemini: {str(e)}")

if __name__ == "__main__":
    import uvicorn
    # Roda o servidor local na porta 8000
    uvicorn.run(app, host="0.0.0.0", port=8000)