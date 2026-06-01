-- Criação do banco de dados se não existir
CREATE DATABASE IF NOT EXISTS techubby_db
DEFAULT CHARACTER SET utf8mb4
DEFAULT COLLATE utf8mb4_unicode_ci;

USE techubby_db;

-- 1. TABELA DE USUÁRIOS
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    senha_hash VARCHAR(255) NULL, -- Nulo se o login for exclusivamente via Google
    google_id VARCHAR(255) NULL,   -- ID único retornado pelo Google Auth
    telefone VARCHAR(20) NULL,
    tipo_usuario ENUM('cliente', 'vendedor', 'admin') DEFAULT 'cliente',
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_usuarios PRIMARY KEY (id_usuario),
    CONSTRAINT uk_usuarios_email UNIQUE (email),
    CONSTRAINT uk_usuarios_google UNIQUE (google_id)
) ENGINE=InnoDB;

-- 2. TABELA DE LOJAS (Para o modelo Marketplace)
CREATE TABLE lojas (
    id_loja INT AUTO_INCREMENT,
    id_usuario INT NOT NULL, -- Dono da loja
    nome_fantasia VARCHAR(100) NOT NULL,
    razao_social VARCHAR(150) NOT NULL,
    cnpj VARCHAR(14) NOT NULL,
    inscricao_estadual VARCHAR(20) NULL,
    logotipo VARCHAR(255) NULL,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_lojas PRIMARY KEY (id_loja),
    CONSTRAINT uk_lojas_cnpj UNIQUE (cnpj),
    CONSTRAINT fk_lojas_usuarios FOREIGN KEY (id_usuario) 
        REFERENCES usuarios(id_usuario) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 3. TABELA DE PRODUTOS (Focada em Hardware)
CREATE TABLE produtos (
    id_produto INT AUTO_INCREMENT,
    id_loja INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(50) NOT NULL,
    descricao TEXT NULL,
    especificacoes_tecnicas JSON NULL, -- Guardará soquetes, frequências, etc. O Python/Tio Claudio vai ler isso aqui!
    preco DECIMAL(10, 2) NOT NULL,
    estoque_atual INT NOT NULL DEFAULT 0,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_produtos PRIMARY KEY (id_produto),
    CONSTRAINT fk_produtos_lojas FOREIGN KEY (id_loja) 
        REFERENCES lojas(id_loja) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 4. TABELA DE PEDIDOS
CREATE TABLE pedidos (
    id_pedido INT AUTO_INCREMENT,
    id_usuario INT NOT NULL, -- Comprador
    valor_produtos DECIMAL(10, 2) NOT NULL,
    valor_frete DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    valor_total DECIMAL(10, 2) NOT NULL,
    status_pedido ENUM('pendente', 'pago', 'enviado', 'entregue', 'cancelado') DEFAULT 'pendente',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_pedidos PRIMARY KEY (id_pedido),
    CONSTRAINT fk_pedidos_usuarios FOREIGN KEY (id_usuario) 
        REFERENCES usuarios(id_usuario) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 5. TABELA INTERMEDIÁRIA: ITENS DO PEDIDO
CREATE TABLE itens_pedido (
    id_item INT AUTO_INCREMENT,
    id_pedido INT NOT NULL,
    id_produto INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10, 2) NOT NULL, -- Histórico do preço na hora da compra
    CONSTRAINT pk_itens_pedido PRIMARY KEY (id_item),
    CONSTRAINT fk_itens_pedidos FOREIGN KEY (id_pedido) 
        REFERENCES pedidos(id_pedido) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_itens_produtos FOREIGN KEY (id_produto) 
        REFERENCES produtos(id_produto) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 6. TABELA DE PAGAMENTOS (Integração Mercado Pago / PicPay)
CREATE TABLE pagamentos (
    id_pagamento INT AUTO_INCREMENT,
    id_pedido INT NOT NULL,
    id_transacao_gateway VARCHAR(100) NOT NULL, -- ID que vem da API do Mercado Pago/PicPay
    metodo_pagamento ENUM('pix', 'cartao_credito', 'boleto') NOT NULL,
    status_pagamento ENUM('pendente', 'aprovado', 'recusado', 'estornado') DEFAULT 'pendente',
    data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_pagamentos PRIMARY KEY (id_pagamento),
    CONSTRAINT uk_pagamentos_transacao UNIQUE (id_transacao_gateway),
    CONSTRAINT fk_pagamentos_pedidos FOREIGN KEY (id_pedido) 
        REFERENCES pedidos(id_pedido) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 7. TABELA DE ENVIOS (Integração Correios)
CREATE TABLE envios (
    id_envio INT AUTO_INCREMENT,
    id_pedido INT NOT NULL,
    codigo_rastreio VARCHAR(50) NULL, -- Código gerado pelos Correios
    servico_envio ENUM('PAC', 'SEDEX') NOT NULL,
    valor_frete DECIMAL(10, 2) NOT NULL,
    logradouro VARCHAR(150) NOT NULL,
    numero VARCHAR(20) NOT NULL,
    complemento VARCHAR(100) NULL,
    bairro VARCHAR(50) NOT NULL,
    cidade VARCHAR(50) NOT NULL,
    estado CHAR(2) NOT NULL,
    cep VARCHAR(8) NOT NULL,
    data_postagem DATETIME NULL,
    CONSTRAINT pk_envios PRIMARY KEY (id_envio),
    CONSTRAINT uk_envios_rastreio UNIQUE (codigo_rastreio),
    CONSTRAINT fk_envios_pedidos FOREIGN KEY (id_pedido) 
        REFERENCES pedidos(id_pedido) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 8. TABELA DE NOTAS FISCAIS (Integração API Fiscal)
CREATE TABLE notas_fiscais (
    id_nf INT AUTO_INCREMENT,
    id_pedido INT NOT NULL,
    numero_nf VARCHAR(20) NOT NULL,
    serie_nf VARCHAR(10) NOT NULL,
    chave_acesso VARCHAR(44) NOT NULL, -- Chave padrão de 44 dígitos da NF-e
    xml_nota TEXT NOT NULL,             -- Conteúdo XML da nota retornado pela API
    data_emissao DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_notas_fiscais PRIMARY KEY (id_nf),
    CONSTRAINT uk_nf_chave UNIQUE (chave_acesso),
    CONSTRAINT fk_nf_pedidos FOREIGN KEY (id_pedido) 
        REFERENCES pedidos(id_pedido) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Criando índices estratégicos para melhorar a velocidade de busca (Performance)
CREATE INDEX idx_produtos_nome ON produtos(nome);
CREATE INDEX idx_produtos_preco ON produtos(preco);
CREATE INDEX idx_pedidos_status ON pedidos(status_pedido);