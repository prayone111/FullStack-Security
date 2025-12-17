<?php
// --- 0. CONFIGURAÇÃO DE LOGIN PERSISTENTE (30 DIAS) ---
ini_set('session.gc_maxlifetime', 2592000); 
session_set_cookie_params(2592000);

session_start(); 

// --- 1. CONFIGURAÇÕES E CONEXÃO COM BANCO ---
$servidor = "....";
$usuario = "....";
$senha = "....";
$banco = "....";

// Conexão segura silenciando erros de tela
$conexao = new mysqli($servidor, $usuario, $senha, $banco);

// --- 2. LÓGICA DE LOGOUT ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// --- 3. VARIÁVEIS DO SISTEMA ---
$feedback_message = '';
$show_auth_modal = false;
$show_pix_modal = false;
$show_profile_modal = false;
$active_tab = 'register';
$produto_pix = '';

// Variáveis do Perfil do Usuário
$user_data = null;
$historico_compras = [];
$total_gasto = 0.00;
$cidade_user = "Não informado";
$estado_user = "--";

// --- 4. CARREGAR DADOS DO USUÁRIO (SE ESTIVER LOGADO) ---
if (isset($_SESSION['usuario_id']) && !$conexao->connect_error) {
    
    $id_user = $_SESSION['usuario_id'];
    $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $res_user = $stmt->get_result();
    $user_data = $res_user->fetch_assoc();
    $stmt->close();

    if ($user_data) {
        $nome_seguro = $conexao->real_escape_string($user_data['nome']);
        
        $sql_hist = "
            SELECT p.produto, p.cidade, p.estado, p.data_pedido, pg.valor 
            FROM pedidos p 
            LEFT JOIN pagamentos pg ON p.id = pg.pedido_id 
            WHERE p.responsavel = '$nome_seguro' 
            ORDER BY p.data_pedido DESC";
            
        $res_hist = $conexao->query($sql_hist);
        
        if ($res_hist) {
            while ($row = $res_hist->fetch_assoc()) {
                $historico_compras[] = $row;
                if ($row['valor']) {
                    $total_gasto += $row['valor'];
                }
                if ($cidade_user == "Não informado" && !empty($row['cidade'])) {
                    $cidade_user = $row['cidade'];
                    $estado_user = $row['estado'];
                }
            }
        }
    }
}

// --- 5. PROCESSAMENTO DE MENSAGENS DE URL (Feedback) ---
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'cadastro_sucesso') {
        $feedback_message = '<div class="feedback-message success">Cadastro realizado! Faça login.</div>';
        $show_auth_modal = true;
        $active_tab = 'login';
    } elseif ($_GET['status'] == 'cadastro_erro') {
        $error_detail = isset($_GET['error_detail']) ? htmlspecialchars($_GET['error_detail']) : 'Erro.';
        $feedback_message = '<div class="feedback-message error">' . $error_detail . '</div>';
        $show_auth_modal = true;
    } elseif ($_GET['status'] == 'login_sucesso') {
        echo "<script>window.addEventListener('load', function() { showToast('Bem-vindo, " . htmlspecialchars($_SESSION['usuario_nome']) . "!'); });</script>";
    } elseif ($_GET['status'] == 'login_erro') {
        $error_detail = isset($_GET['error_detail']) ? htmlspecialchars($_GET['error_detail']) : 'Erro.';
        $feedback_message = '<div class="feedback-message error">' . $error_detail . '</div>';
        $show_auth_modal = true;
        $active_tab = 'login';
    } elseif ($_GET['status'] == 'pagamento_pix') {
        $show_pix_modal = true;
        $produto_pix = isset($_GET['prod']) ? htmlspecialchars($_GET['prod']) : 'Software';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FullStack Security - Perfil & Ofertas</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- ESTILOS GERAIS --- */
        :root {
            --primary-color: #3b82f6; --primary-hover: #2563eb; --bg-dark: #111827;
            --bg-card: #1f2937; --text-light: #f3f4f6; --text-gray: #9ca3af;
            --accent-color: #8b5cf6; --discount-color: #10b981; --old-price-color: #ef4444;
            --pix-color: #32bcad;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-dark); color: var(--text-light); min-height: 100vh; display: flex; flex-direction: column; }

        /* Navbar & Hero */
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem 5%; background: rgba(17, 24, 39, 0.8); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255,255,255,0.05); position: sticky; top: 0; z-index: 100; }
        .logo { font-size: 1.5rem; font-weight: 700; background: linear-gradient(to right, #3b82f6, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .nav-actions { display: flex; gap: 20px; align-items: center; }
        .cart-icon, .profile-icon { font-size: 1.4rem; cursor: pointer; transition: color 0.3s; color: var(--text-light); position: relative; }
        .cart-icon:hover, .profile-icon:hover { color: var(--primary-color); }
        .cart-count { position: absolute; top: -8px; right: -8px; background: var(--primary-color); color: white; font-size: 0.7rem; width: 18px; height: 18px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-weight: bold; opacity: 0; }
        
        .hero { text-align: center; padding: 80px 20px 40px; background: radial-gradient(circle at center, rgba(59, 130, 246, 0.15) 0%, rgba(17, 24, 39, 0) 70%); }
        .hero h1 { font-size: 3rem; font-weight: 700; margin-bottom: 15px; line-height: 1.2; }
        .hero p { font-size: 1.1rem; color: var(--text-gray); max-width: 600px; margin: 0 auto 40px; }
        
        /* Search & Grid */
        .search-container { max-width: 500px; margin: 0 auto 60px; position: relative; }
        .search-input { width: 100%; padding: 15px 20px 15px 50px; border-radius: 30px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: #fff; font-size: 1rem; outline: none; }
        .search-icon { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: var(--text-gray); }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; flex: 1; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin-bottom: 80px; }
        .product-card { background: var(--bg-card); border-radius: 16px; padding: 25px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s; position: relative; overflow: hidden; display: flex; flex-direction: column; }
        .product-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.4); border-color: var(--primary-color); }
        .discount-badge-card { position: absolute; top: 15px; right: 15px; background: #ef4444; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; }
        .icon-box { width: 50px; height: 50px; background: rgba(59, 130, 246, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--primary-color); font-size: 1.5rem; margin-bottom: 20px; }
        .product-card h3 { font-size: 1.25rem; margin-bottom: 10px; }
        .product-card .desc { color: var(--text-gray); font-size: 0.9rem; line-height: 1.5; margin-bottom: 20px; flex-grow: 1; }
        .price-row { display: flex; justify-content: space-between; align-items: flex-end; margin-top: auto; gap: 10px; }
        .old-price { font-size: 0.85rem; text-decoration: line-through; color: var(--old-price-color); }
        .new-price { font-size: 1.3rem; font-weight: 700; color: var(--discount-color); white-space: nowrap; }
        .buy-btn, .submit-btn { background: var(--primary-color); color: #fff; border: none; padding: 10px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.2s; }
        .buy-btn:hover, .submit-btn:hover { background: var(--primary-hover); }
        .add-cart-btn { background: transparent; border: 1px solid var(--primary-color); color: var(--primary-color); padding: 10px; border-radius: 8px; cursor: pointer; }

        /* Footer & Modals */
        .footer { background: #0f1115; border-top: 1px solid rgba(255,255,255,0.05); padding: 40px 5%; text-align: center; margin-top: auto; }
        .social-links { margin-top: 15px; display: flex; justify-content: center; gap: 20px; font-size: 1.5rem; }
        .social-links a { color: var(--text-gray); }
        
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 1000; justify-content: center; align-items: center; }
        .modal-overlay.visible { display: flex; animation: fadeIn 0.3s; }
        .auth-container { background: #fff; width: 100%; max-width: 450px; padding: 40px; border-radius: 20px; position: relative; color: #333; max-height: 90vh; overflow-y: auto; }
        .close-modal { position: absolute; top: 20px; right: 20px; font-size: 1.5rem; cursor: pointer; color: #999; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 0.85rem; font-weight: 600; color: #555; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; }
        
        /* --- ESTILOS PERFIL --- */
        .profile-header { text-align: center; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .profile-avatar { width: 80px; height: 80px; background: #dcfce7; color: #16a34a; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 15px; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .profile-name { font-size: 1.4rem; font-weight: 700; color: #1f2937; }
        .profile-location { color: #666; font-size: 0.9rem; margin-top: 5px; }
        .profile-stats { display: flex; gap: 15px; margin-bottom: 25px; }
        .stat-box { flex: 1; background: #f9fafb; padding: 15px; border-radius: 10px; text-align: center; border: 1px solid #eee; }
        .stat-value { font-size: 1.2rem; font-weight: 700; color: var(--primary-color); display: block; }
        .stat-label { font-size: 0.8rem; color: #666; }
        .purchase-list { list-style: none; margin-top: 10px; max-height: 200px; overflow-y: auto; }
        .purchase-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: 0.9rem; }
        .purchase-date { font-size: 0.75rem; color: #999; }
        .logout-btn { background: #ef4444; width: 100%; margin-top: 15px; padding: 12px; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        
        /* Ícone de verificado */
        .verified-user { color: #10b981 !important; text-shadow: 0 0 10px rgba(16, 185, 129, 0.4); }

        /* Estilo para janelas de confirmação/sucesso (Sobreposto ao modal) */
        .small-modal-wrapper {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 1200; display: none;
            justify-content: center; align-items: center;
        }
        .small-modal {
            background: #fff; padding: 25px; border-radius: 12px; width: 90%; max-width: 320px;
            text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.2); animation: fadeIn 0.2s;
        }
        .small-modal h3 { color: #1f2937; margin-bottom: 15px; font-size: 1.2rem; }
        .small-modal-actions { display: flex; gap: 10px; justify-content: center; margin-top: 20px; }
        .btn-yes { background: #ef4444; color: white; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer; }
        .btn-no { background: #e5e7eb; color: #333; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer; }
        
        /* Sucesso */
        .success-modal-content { text-align: center; }
        .success-icon { font-size: 4rem; color: #10b981; margin-bottom: 15px; display: block; }
        .success-title { font-size: 1.5rem; font-weight: 800; color: #10b981; margin-bottom: 10px; text-transform: uppercase; }
        .success-text { color: #666; line-height: 1.6; }

        /* Tabs de Login/Cadastro */
        .toggle-buttons { display: flex; margin-bottom: 30px; border-bottom: 2px solid #eee; }
        .toggle-btn { flex: 1; padding: 10px; border: none; background: none; font-size: 1rem; font-weight: 600; color: #999; cursor: pointer; position: relative; }
        .toggle-btn.active { color: var(--primary-color); }
        .toggle-btn.active::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 100%; height: 2px; background: var(--primary-color); }
        .auth-form { display: none; animation: fadeIn 0.3s; }
        .auth-form.active { display: block; }

        /* Tost */
        .toast { visibility: hidden; min-width: 300px; background-color: #333; color: #fff; text-align: center; border-radius: 8px; padding: 16px; position: fixed; z-index: 1300; left: 50%; bottom: 30px; transform: translateX(-50%); font-size: 16px; display: flex; gap: 10px; justify-content: center;}
        .toast.show { visibility: visible; animation: fadein 0.5s, fadeout 0.5s 2.5s; }
        @keyframes fadein { from {bottom: 0; opacity: 0;} to {bottom: 30px; opacity: 1;} }
        @keyframes fadeout { from {bottom: 30px; opacity: 1;} to {bottom: 0; opacity: 0;} }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo"><i class="fas fa-shield-halved"></i> FullStack Security</div>
        <div class="nav-actions">
            <div style="position:relative; display:inline-block;">
                <i class="fas fa-shopping-cart cart-icon" onclick="openCartModal()" title="Ver Carrinho"></i>
                <span id="cartCount" class="cart-count">0</span>
            </div>
            
            <!-- LÓGICA DO ÍCONE DE PERFIL -->
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <!-- Usuário Logado: Ícone de Verificado -->
                <i class="fas fa-user-check profile-icon verified-user" onclick="openProfileModal()" title="Meu Perfil"></i>
            <?php else: ?>
                <!-- Usuário Anônimo: Ícone Padrão -->
                <i class="fas fa-user-circle profile-icon" onclick="openAuthModal()" title="Fazer Login"></i>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Área Principal -->
    <div class="container">
        <section class="hero">
            <h1>Tecnologia que protege.<br>Software que impulsiona.</h1>
            <p>Acesse as melhores ferramentas de gestão, segurança e análise de dados em um só lugar. <br><strong>Aproveite 10% OFF em todos os produtos!</strong></p>
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" id="searchInput" placeholder="O que você procura? (Ex: Linux, Firewall...)">
            </div>
        </section>

        <!-- Grid de Produtos -->
        <section class="product-grid" id="productGrid">
            <!-- Produto 1 -->
            <div class="product-card" data-name="sistema de gestão erp">
                <span class="discount-badge-card">10% OFF</span>
                <div class="icon-box"><i class="fas fa-chart-line"></i></div>
                <h3>Sistema de Gestão (ERP)</h3>
                <p class="desc">Controle total do seu negócio: financeiro, estoque e vendas em uma dashboard intuitiva.</p>
                <div class="price-row"><div class="price-wrapper"><span class="old-price">De R$ 499,99</span><span class="new-price">R$ 449,99<span>/mês</span></span></div>
                <button class="buy-btn" onclick="openCheckout('Sistema de Gestão (ERP)')">Comprar</button><button class="add-cart-btn" onclick="addToCart('Sistema de Gestão (ERP)', 'R$ 449,99')"><i class="fas fa-cart-plus"></i></button></div>
            </div>
            <!-- Produto 2 -->
            <div class="product-card" data-name="antivirus pro segurança">
                <span class="discount-badge-card">10% OFF</span>
                <div class="icon-box"><i class="fas fa-shield-virus"></i></div>
                <h3>Anti-Vírus Pro</h3>
                <p class="desc">Proteção blindada contra malwares e ransomwares com IA avançada de detecção.</p>
                <div class="price-row"><div class="price-wrapper"><span class="old-price">De R$ 129,90</span><span class="new-price">R$ 116,90<span>/ano</span></span></div>
                <button class="buy-btn" onclick="openCheckout('Anti-Vírus Pro')">Comprar</button><button class="add-cart-btn" onclick="addToCart('Anti-Vírus Pro', 'R$ 116,90')"><i class="fas fa-cart-plus"></i></button></div>
            </div>
            <!-- Produto 3 -->
            <div class="product-card" data-name="linux security os sistema operacional">
                <span class="discount-badge-card">10% OFF</span>
                <div class="icon-box"><i class="fab fa-linux"></i></div>
                <h3>Linux Security OS</h3>
                <p class="desc">Distro Linux hardened focada em privacidade, zero telemetria e performance extrema.</p>
                <div class="price-row"><div class="price-wrapper"><span class="old-price">De R$ 249,00</span><span class="new-price">R$ 224,10</span></div>
                <button class="buy-btn" onclick="openCheckout('Linux Security OS')">Comprar</button><button class="add-cart-btn" onclick="addToCart('Linux Security OS', 'R$ 224,10')"><i class="fas fa-cart-plus"></i></button></div>
            </div>
            <!-- Produto 4 -->
            <div class="product-card" data-name="data analytics dados">
                <span class="discount-badge-card">10% OFF</span>
                <div class="icon-box"><i class="fas fa-database"></i></div>
                <h3>Data Analytics Suite</h3>
                <p class="desc">Transforme Big Data em lucro. Ferramentas preditivas para análise de mercado.</p>
                <div class="price-row"><div class="price-wrapper"><span class="old-price">De R$ 799,00</span><span class="new-price">R$ 719,10<span>/mês</span></span></div>
                <button class="buy-btn" onclick="openCheckout('Data Analytics Suite')">Comprar</button><button class="add-cart-btn" onclick="addToCart('Data Analytics Suite', 'R$ 719,10')"><i class="fas fa-cart-plus"></i></button></div>
            </div>
            <!-- Produto 5 -->
            <div class="product-card" data-name="firewall de host computador">
                <span class="discount-badge-card">10% OFF</span>
                <div class="icon-box"><i class="fas fa-laptop-code"></i></div>
                <h3>Firewall de Host</h3>
                <p class="desc">Um software que protege um único computador ou servidor, monitorando e controlando o tráfego de rede que entra e sai, lá ele.</p>
                <div class="price-row"><div class="price-wrapper"><span class="old-price">De R$ 100,00</span><span class="new-price">R$ 90,00<span>/PC</span></span></div>
                <button class="buy-btn" onclick="openCheckout('Firewall de Host')">Comprar</button><button class="add-cart-btn" onclick="addToCart('Firewall de Host', 'R$ 90,00')"><i class="fas fa-cart-plus"></i></button></div>
            </div>
            <!-- Produto 6 -->
            <div class="product-card" data-name="firewall de rede network">
                <span class="discount-badge-card">10% OFF</span>
                <div class="icon-box"><i class="fas fa-network-wired"></i></div>
                <h3>Firewall de Rede</h3>
                <p class="desc">Um dispositivo de segurança que funciona como uma barreira entre uma rede interna e redes externas, como a internet, monitorando e controlando o tráfego de entrada e saída(lá ele) com base em regras de segurança.</p>
                <div class="price-row"><div class="price-wrapper"><span class="old-price">De R$ 1.500,00</span><span class="new-price">R$ 1.350,00</span></div>
                <button class="buy-btn" onclick="openCheckout('Firewall de Rede')">Comprar</button><button class="add-cart-btn" onclick="addToCart('Firewall de Rede', 'R$ 1.350,00')"><i class="fas fa-cart-plus"></i></button></div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="logo" style="font-size: 1.2rem; margin-bottom: 10px;">FullStack Security</div>
        <p>&copy; 2025 FullStack Security. Todos os direitos reservados.</p>
        <div class="social-links">
            <a href="https://www.instagram.com/prayone1?igsh=NThwbXl3aXl2Z2t5" target="_blank"><i class="fab fa-instagram"></i></a>
            <a href="#" onclick="showToast('Linkedin em construção!', 'info')"><i class="fab fa-linkedin"></i></a>
            <a href="https://github.com/prayone111" target="_blank"><i class="fab fa-github"></i></a>
        </div>
    </footer>

    <!-- Notificação Toast -->
    <div id="toast" class="toast">Item adicionado ao carrinho!</div>

    <!-- Modais de Confirmação -->
    <div class="small-modal-wrapper" id="cancelConfirmModal">
        <div class="small-modal">
            <h3>Cancelar o pagamento?</h3>
            <div class="small-modal-actions">
                <button class="btn-yes" onclick="confirmCancel()">Sim</button>
                <button class="btn-no" onclick="denyCancel()">Não</button>
            </div>
        </div>
    </div>

    <!-- Modal Sucesso -->
    <div class="small-modal-wrapper" id="successRealModal">
        <div class="small-modal" style="max-width: 400px; padding: 40px;">
            <div class="success-modal-content">
                <i class="fas fa-check-circle success-icon"></i>
                <h2 class="success-title">COMPRA BEM SUCEDIDA!</h2>
                <p class="success-text">Obrigado por comprar na Fullstack Security, aqui seu negócio fica sempre mais moderno (>.-).</p>
                <button class="submit-btn" style="margin-top:20px;" onclick="closeAllSuccess()">Fechar</button>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL DE PERFIL DO USUÁRIO                 -->
    <!-- ========================================== -->
    <div class="modal-overlay" id="profileModal">
        <div class="auth-container">
            <span class="close-modal" onclick="closeProfileModal()">&times;</span>
            
            <?php if ($user_data): ?>
                <div class="profile-header">
                    <div class="profile-avatar"><i class="fas fa-check"></i></div>
                    <h2 class="profile-name"><?php echo htmlspecialchars($user_data['nome']); ?></h2>
                    <p class="profile-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($cidade_user) . ' - ' . htmlspecialchars($estado_user); ?></p>
                    <p style="color:#666; font-size:0.9rem; margin-top:5px;"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user_data['telefone']); ?></p>
                </div>

                <div class="profile-stats">
                    <div class="stat-box">
                        <span class="stat-value">R$ <?php echo number_format($total_gasto, 2, ',', '.'); ?></span>
                        <span class="stat-label">Total Investido</span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-value"><?php echo count($historico_compras); ?></span>
                        <span class="stat-label">Softwares</span>
                    </div>
                </div>

                <h3 style="font-size:1rem; color:#333; margin-bottom:10px;">Meus Softwares:</h3>
                <?php if (count($historico_compras) > 0): ?>
                    <ul class="purchase-list">
                        <?php foreach($historico_compras as $compra): ?>
                            <li class="purchase-item">
                                <span><?php echo htmlspecialchars($compra['produto']); ?></span>
                                <span class="purchase-date"><i class="fas fa-check-circle" style="color:var(--discount-color)"></i> Ativo</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="text-align:center; color:#999; font-size:0.9rem;">Nenhuma compra realizada ainda.</p>
                <?php endif; ?>

                <form action="index.php" method="GET">
                    <button type="submit" name="logout" value="true" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Sair da Conta</button>
                </form>

            <?php else: ?>
                <p>Erro ao carregar perfil. Faça login novamente.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modais de Checkout e PIX -->
    <div class="modal-overlay" id="checkoutModal">
        <div class="auth-container">
            <span class="close-modal" onclick="closeCheckout()">&times;</span>
            <div class="checkout-header"><h2>Finalizar Compra</h2><span class="product-highlight" id="checkoutProductName">Software Selecionado</span></div>
            <div class="checkout-text">Obrigado por escolher o melhor site de vendas de software. Aqui, sua empresa estará sempre acompanhando o avanço da tecnologia.</div>
            <form action="processa_compra.php" method="POST">
                <input type="hidden" id="hiddenProductInput" name="produto_escolhido">
                <div class="form-group"><label>Nome da Empresa:</label><input type="text" name="empresa" required placeholder="Ex: Tech Solutions Ltda"></div>
                <div class="row-dual">
                    <div class="form-group"><label>Nome do Responsável:</label><input type="text" name="nome" required placeholder="Ex: prayone" value="<?php echo isset($user_data['nome']) ? htmlspecialchars($user_data['nome']) : ''; ?>"></div>
                    <div class="form-group"><label>Telefone:</label><input type="text" name="telefone" required placeholder="(xx) xxxxx-xxxx" value="<?php echo isset($user_data['telefone']) ? htmlspecialchars($user_data['telefone']) : ''; ?>"></div>
                </div>
                <div class="row-dual">
                    <div class="form-group"><label>Estado:</label><input type="text" name="estado" required placeholder="Ex: RR"></div>
                    <div class="form-group"><label>Cidade:</label><input type="text" name="cidade" required placeholder="Ex: Boa Vista"></div>
                </div>
                <button type="submit" class="submit-btn">Ir para Pagamento</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="pixModal">
        <div class="auth-container">
            <span class="close-modal" onclick="closePixModal()">&times;</span>
            <div class="pix-header">
                <div class="pix-logo"><i class="fas fa-qrcode"></i></div>
                <h2 style="color:#1f2937;">Pagamento via PIX</h2>
                <p style="color:#666; font-size:0.9rem;">Pedido realizado! Escaneie o QR Code abaixo para pagar.</p>
                <p style="color:var(--primary-color); font-weight:600; margin-top:5px; font-size: 1.5rem;">R$ 0,01</p>
            </div>
            <div class="qr-code-container"><img id="pixQrImage" src="" alt="Gerando PIX..." style="display:none;"><p id="pixLoading" style="color:#999;">Carregando QR Code...</p></div>
            <p style="text-align:center; font-size:0.9rem; margin-bottom:5px;">Pix Copia e Cola:</p>
            <textarea id="pixPayloadRaw" style="position:absolute; left:-9999px;"></textarea>
            <div class="pix-key-box" id="pixKeyDisplay" style="font-size: 0.8rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">(95) 99152-1095</div>
            <button class="copy-btn" onclick="copyPixPayload()"><i class="far fa-copy"></i> Copiar Código PIX</button>
            <button class="submit-btn" id="btnVerifyPayment" style="background:#10b981; margin-top:20px;" onclick="verifyPayment()">Já paguei</button>
        </div>
    </div>

    <div class="modal-overlay" id="cartModal">
        <div class="auth-container">
            <span class="close-modal" onclick="closeCartModal()">&times;</span>
            <h2 class="cart-modal-title">Carrinho de Compras</h2>
            <p class="cart-quote">"Tudo no seu tempo, mas não deixe o tempo ter tudo."</p>
            <ul class="cart-list" id="cartListContainer"></ul>
            <button class="submit-btn" onclick="showToast('Finalizando compra do carrinho...'); closeCartModal();">Finalizar Compra Total</button>
            <button class="submit-btn empty-cart-btn" onclick="clearCart()">Esvaziar Carrinho</button>
        </div>
    </div>

    <!-- Auth Modal -->
    <div class="modal-overlay" id="authModal">
        <div class="auth-container">
            <span class="close-modal" onclick="closeAuthModal()">&times;</span>
            <div class="toggle-buttons">
                <button class="toggle-btn <?php echo ($active_tab == 'register') ? 'active' : ''; ?>" id="btnRegister" onclick="switchTab('register')">Criar Conta</button>
                <button class="toggle-btn <?php echo ($active_tab == 'login') ? 'active' : ''; ?>" id="btnLogin" onclick="switchTab('login')">Fazer Login</button>
            </div>
            <?php echo $feedback_message; ?>
            <div id="registerForm" class="auth-form <?php echo ($active_tab == 'register') ? 'active' : ''; ?>">
                <form action="processa_cadastro.php" method="POST">
                    <div class="form-group"><label>Nome Completo</label><input type="text" name="nome" required placeholder="Ex: sunraku"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" required placeholder="seu@email.com"></div>
                    <div class="form-group"><label>Senha</label><input type="password" name="senha" required placeholder="******"></div>
                    <div class="form-group"><label>Telefone (Opcional)</label><input type="text" name="telefone" placeholder="(xx) xxxxx-xxxx"></div>
                    <button type="submit" class="submit-btn">Cadastrar Agora</button>
                </form>
            </div>
            <div id="loginForm" class="auth-form <?php echo ($active_tab == 'login') ? 'active' : ''; ?>">
                <form action="processa_login.php" method="POST">
                    <div class="form-group"><label>Email</label><input type="email" name="email_login" required placeholder="seu@email.com"></div>
                    <div class="form-group"><label>Senha</label><input type="password" name="senha_login" required placeholder="******"></div>
                    <button type="submit" class="submit-btn">Entrar</button>
                    <p style="text-align:center; margin-top:15px; font-size:0.85rem; color:#666;"><a href="#" style="color:var(--primary-color); text-decoration:none;">Esqueceu a senha?</a></p>
                </form>
            </div>
        </div>
    </div>

    <script>
        /* --- DADOS DO USUÁRIO NO FRONTEND --- */
        const isUserLoggedIn = <?php echo isset($_SESSION['usuario_id']) ? 'true' : 'false'; ?>;
        const currentUserId = "<?php echo isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : ''; ?>";

        /* --- Lógica Estática PIX & Verificação Real --- */
        const staticPixPayload = "....";
        let pixStartTime = 0;

        function generatePixCode() {
            document.getElementById('pixPayloadRaw').value = staticPixPayload;
            document.getElementById('pixKeyDisplay').innerText = "...."; 
            const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(staticPixPayload)}`;
            const img = document.getElementById('pixQrImage');
            img.src = qrUrl; img.onload = function() { img.style.display = 'block'; document.getElementById('pixLoading').style.display = 'none'; };
            pixStartTime = Date.now();
            resetPixButton();
        }

        function resetPixButton() {
            const btn = document.getElementById('btnVerifyPayment');
            btn.innerText = "Já paguei";
            btn.disabled = false;
            btn.style.backgroundColor = "#10b981";
        }

        function verifyPayment() {
            const btn = document.getElementById('btnVerifyPayment');
            btn.innerText = "Verificando...";
            btn.disabled = true;
            btn.style.backgroundColor = "#9ca3af";
            setTimeout(() => {
                const timeElapsed = Date.now() - pixStartTime;
                if (timeElapsed < 10000) {
                    document.getElementById('cancelConfirmModal').style.display = 'flex';
                } else {
                    showSuccessReal();
                }
            }, 2000);
        }

        function confirmCancel() {
            document.getElementById('cancelConfirmModal').style.display = 'none';
            closePixModal();
            showToast("Pagamento cancelado T-T.", "error");
        }

        function denyCancel() {
            document.getElementById('cancelConfirmModal').style.display = 'none';
            resetPixButton();
            const btn = document.getElementById('btnVerifyPayment');
            btn.innerText = "Aguardando pagamento...";
        }

        function showSuccessReal() {
            closePixModal();
            document.getElementById('successRealModal').style.display = 'flex';
        }

        function closeAllSuccess() {
            document.getElementById('successRealModal').style.display = 'none';
        }

        function copyPixPayload() {
            const copyText = document.getElementById("pixPayloadRaw"); copyText.select(); copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value).then(() => { showToast("Código PIX Copia e Cola copiado!", 'success'); });
        }

        /* --- Carrinho Inteligente --- */
        let cartItems = [];

        function saveCartToStorage() {
            if(isUserLoggedIn && currentUserId) {
                localStorage.setItem('cart_' + currentUserId, JSON.stringify(cartItems));
            }
        }

        function loadCartFromStorage() {
            if(isUserLoggedIn && currentUserId) {
                const saved = localStorage.getItem('cart_' + currentUserId);
                if(saved) {
                    cartItems = JSON.parse(saved);
                    updateCartCount();
                }
            }
        }

        function addToCart(productName, price) {
            if (!isUserLoggedIn) {
                showToast("Ops! Você se esqueceu de se cadastrar/login.", "error");
                openAuthModal();
                return;
            }
            cartItems.push({ name: productName, price: price });
            updateCartCount();
            saveCartToStorage();
            showToast("Não deixe o tempo parar você ;).", 'cart-add');
        }

        function updateCartCount() { const el = document.getElementById('cartCount'); el.innerText = cartItems.length; el.style.opacity = cartItems.length > 0 ? '1' : '0'; }
        
        function openCartModal() { 
            if (!isUserLoggedIn) {
                showToast("Ops! Você se esqueceu de se cadastrar/login.", "error");
                return;
            }
            if (cartItems.length === 0) showToast("Ela também enganou você? Selecione sua compra!", 'error'); 
            else { renderCartItems(); document.getElementById('cartModal').classList.add('visible'); } 
        }
         
        function closeCartModal() { document.getElementById('cartModal').classList.remove('visible'); }
        function renderCartItems() { const c = document.getElementById('cartListContainer'); c.innerHTML = ''; cartItems.forEach(i => { c.innerHTML += `<li class="cart-item"><div><h4>${i.name}</h4><p>Promocional</p></div><b>${i.price}</b></li>`; }); }
        
        function clearCart() { 
            cartItems = []; updateCartCount(); saveCartToStorage(); closeCartModal(); showToast("Carrinho esvaziado."); 
        }

        /* --- Modais --- */
        const authModal = document.getElementById('authModal');
        const checkoutModal = document.getElementById('checkoutModal');
        const cartModal = document.getElementById('cartModal');
        const pixModal = document.getElementById('pixModal');
        const profileModal = document.getElementById('profileModal');
        
        function openAuthModal() { authModal.classList.add('visible'); }
        function closeAuthModal() { authModal.classList.remove('visible'); }
        
        function openCheckout(productName) { 
            if (!isUserLoggedIn) {
                showToast("Opa, meu consagrado! Faça primeiro seu cadastro/login para fazer sua compra.", "error");
                openAuthModal();
                return;
            }
            document.getElementById('checkoutProductName').innerText = productName; 
            document.getElementById('hiddenProductInput').value = productName; 
            checkoutModal.classList.add('visible'); 
        }
         
        function closeCheckout() { checkoutModal.classList.remove('visible'); }
        function closePixModal() { pixModal.classList.remove('visible'); }
        function openProfileModal() { profileModal.classList.add('visible'); }
        function closeProfileModal() { profileModal.classList.remove('visible'); }

        function switchTab(tab) {
            document.getElementById('registerForm').classList.remove('active'); document.getElementById('loginForm').classList.remove('active');
            document.getElementById('btnRegister').classList.remove('active'); document.getElementById('btnLogin').classList.remove('active');
            if(tab === 'register') { document.getElementById('registerForm').classList.add('active'); document.getElementById('btnRegister').classList.add('active'); } 
            else { document.getElementById('loginForm').classList.add('active'); document.getElementById('btnLogin').classList.add('active'); }
        }

        /* --- Search & Toast --- */
        document.getElementById('searchInput').addEventListener('keyup', (e) => {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('.product-card').forEach(card => { card.style.display = card.getAttribute('data-name').includes(term) ? 'flex' : 'none'; });
        });

        function showToast(message, type = 'success') {
            const toast = document.getElementById("toast"); toast.innerText = message;
            toast.style.backgroundColor = type === 'info' ? "#333" : type === 'error' ? "#dc2626" : type === 'cart-add' ? "#8b5cf6" : "#16a34a";
            toast.className = "toast show"; setTimeout(() => { toast.className = toast.className.replace("show", ""); }, 3000);
        }

        window.onclick = function(e) {
            if (e.target == authModal) closeAuthModal();
            if (e.target == checkoutModal) closeCheckout();
            if (e.target == cartModal) closeCartModal();
            if (e.target == pixModal) closePixModal();
            if (e.target == profileModal) closeProfileModal();
        }

        window.addEventListener('load', loadCartFromStorage);

        <?php if ($show_auth_modal): ?> openAuthModal(); <?php endif; ?>
        <?php if ($show_pix_modal): ?> pixModal.classList.add('visible'); generatePixCode(); <?php endif; ?>
    </script>
</body>
</html>
