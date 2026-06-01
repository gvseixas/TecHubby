<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Produto — TecHubby</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/EstiloGeral.css">
    <link rel="stylesheet" href="../css/EstiloConta.css">
    <link rel="shortcut icon" href="../imagens/favicon.svg" type="image/x-icon">
</head>
<body>
    <header class="nav_bar">
        <nav class="logo">
            <a href="../index.html">
                <img src="../imagens/logo.svg" alt="Logo do TecHub">
            </a>
        </nav>

        <nav class="barra_pesquisa">
            <i class='bx bx-search'></i>
            <input type="text" placeholder="Buscar produtos, componentes...">
        </nav>

        <nav>
            <ul class="menu_lateral">
                <li class="menu_item">
                    <a href="" class="nav_icon" aria-label="Carrinho">
                        <i class='bx bx-cart'></i>
                        <span class="cart_badge">0</span>
                    </a>
                </li>
                <li class="menu_item">
                    <a href="conta.html" class="btn">
                        <i class='bx bxs-user'></i>
                        <span>Conta</span>
                    </a>
                </li>
            </ul>
        </nav>
    </header>

    <main class="container_geral">
        <form class="container_body" action="../php/produtos_cadastrar.php" method="post">
            <div class="container">
                <h1>Cadastre seu Produto</h1>

                <div class="input_box">
                    <input type="text" name="nome" placeholder="Nome do produto" required>
                    <i class="bx bxs-user"></i>
                </div>

                <div class="input_box">
                    <input type="text" name="marca" placeholder="Marca" required>
                    <i class="bx bxs-user"></i>
                </div>

                <div class="input_box">
                    <input type="text" name="modelo" placeholder="Modelo" required>
                    <i class="bx bxs-user"></i>
                </div>

                <div class="input_box">
                    <input type="text" name="preco" placeholder="Preço" required>
                    <i class="bx bxs-user"></i>
                </div>

                <div class="input_box">
                    <input type="number" name="estoque_atual" placeholder="Estoque atual" value="0" min="0">
                    <i class="bx bxs-user"></i>
                </div>

                <div class="input_box" style="height:auto; margin: 24px 0 18px;">
                    <textarea name="descricao" placeholder="Descrição" rows="3" style="width:100%; height:100%; background-color: transparent; border: 2px solid rgba(255,255,255, .2); outline-color: rgb(0, 140, 255); border-radius: 10px; color:#fff; padding: 14px 18px; resize: vertical;"></textarea>
                </div>

                <div class="input_box" style="height:auto; margin: 14px 0 18px;">
                    <textarea name="especificacoes_tecnicas" placeholder="Especificações técnicas (JSON). Ex: {\"soquete\":\"AM4\",\"memoria\":\"DDR4\"}" rows="4" style="width:100%; height:100%; background-color: transparent; border: 2px solid rgba(255,255,255, .2); outline-color: rgb(0, 140, 255); border-radius: 10px; color:#fff; padding: 14px 18px; resize: vertical;"></textarea>
                </div>

                <button type="submit" class="btn botao_login">Salvar Produto</button>

                <div class="link_registrar">
                    <p><a href="../index.html">Voltar para o início</a></p>
                </div>
            </div>
        </form>
    </main>
</body>
</html>

