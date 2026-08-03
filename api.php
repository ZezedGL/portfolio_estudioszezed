<?php
// Configurações para permitir que o frontend converse com este arquivo e leia JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type");

// 1. Configuração do Banco de Dados (Ajuste conforme seu servidor local)
$host = 'localhost';
$dbname = 'sistema_eventos';
$user = 'root'; // Padrão XAMPP
$pass = '';     // Padrão XAMPP

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["status" => "erro", "message" => "Erro no Banco de Dados: " . $e->getMessage()]);
    exit;
}

// 2. Lidar com requisição GET (Apenas para listar os eventos)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'listarEventos') {
    $stmt = $pdo->query("SELECT * FROM eventos ORDER BY data_evento ASC");
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $eventosFormatados = [];
    foreach($eventos as $evt) {
        $eventosFormatados[] = [
            "id" => $evt['id'],
            "nome" => $evt['nome'],
            // Formata a data para o padrão brasileiro
            "data" => date('d/m/Y', strtotime($evt['data_evento'])), 
            // Formata o valor para exibir corretamente
            "valor" => number_format($evt['valor'], 2, ',', '.') 
        ];
    }
    echo json_encode(["status" => "sucesso", "eventos" => $eventosFormatados]);
    exit;
}

// 3. Lidar com requisições POST (Cadastro, Login, Criar Evento, Pagamento)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura o JSON enviado pelo JavaScript
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $data['action'] ?? '';

    if ($action === 'cadastro') {
        $stmt = $pdo->prepare("INSERT INTO usuarios (email, senha, tipo) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$data['email'], $data['senha'], $data['tipo']]);
            echo json_encode(["status" => "sucesso", "message" => "Usuário cadastrado com sucesso!"]);
        } catch(Exception $e) {
            echo json_encode(["status" => "erro", "message" => "Erro: Este e-mail já pode estar cadastrado."]);
        }
    }
    
    elseif ($action === 'login') {
        $stmt = $pdo->prepare("SELECT tipo FROM usuarios WHERE email = ? AND senha = ?");
        $stmt->execute([$data['email'], $data['senha']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo json_encode(["status" => "sucesso", "tipo" => $user['tipo']]);
        } else {
            echo json_encode(["status" => "erro", "message" => "E-mail ou senha inválidos."]);
        }
    }
    
    elseif ($action === 'criarEvento') {
        $stmt = $pdo->prepare("INSERT INTO eventos (nome, data_evento, valor) VALUES (?, ?, ?)");
        $stmt->execute([$data['nome'], $data['data'], $data['valor']]);
        echo json_encode(["status" => "sucesso", "message" => "Evento publicado com sucesso!"]);
    }
    
    elseif ($action === 'confirmarInscricao') {
        // Gera um código único estilo ticket
        $codigo = 'TICKET-' . strtoupper(substr(md5(uniqid()), 0, 8));
        
        $stmt = $pdo->prepare("INSERT INTO inscricoes (usuario_email, evento_id, codigo_ingresso) VALUES (?, ?, ?)");
        $stmt->execute([$data['email'], $data['eventoId'], $codigo]);
        
        echo json_encode(["status" => "sucesso", "codigo" => $codigo]);
    }
}
?>