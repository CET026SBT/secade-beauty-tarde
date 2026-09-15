<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

function bot_response(string $message): string
{
    $text = mb_strtolower(trim($message), 'UTF-8');

    if ($text === '') {
        return 'Escreva a sua pergunta. Posso ajudar com servicos, precos, duracao, cidades e agendamentos.';
    }

    if (str_contains($text, 'ola') || str_contains($text, 'olá') || str_contains($text, 'bom dia') || str_contains($text, 'boa tarde')) {
        return 'Ola! Sou o assistente da Secade Beauty. Posso ajudar a escolher um servico, ver precos ou iniciar um agendamento.';
    }

    if (str_contains($text, 'agend') || str_contains($text, 'marcar') || str_contains($text, 'reserva')) {
        return 'Pode fazer o pedido na pagina Agendar. Depois a equipa confirma a viabilidade, principalmente se escolher carrinha ambulante.';
    }

    if (str_contains($text, 'carrinha') || str_contains($text, 'ambulante') || str_contains($text, 'cidade') || str_contains($text, 'desloc')) {
        $cities = db()->query('SELECT nome FROM cidade ORDER BY nome')->fetchAll(PDO::FETCH_COLUMN);
        return 'A carrinha ambulante esta preparada para as cidades registadas: ' . implode(', ', $cities) . '.';
    }

    if (str_contains($text, 'preco') || str_contains($text, 'preço') || str_contains($text, 'quanto') || str_contains($text, 'servico') || str_contains($text, 'serviço')) {
        return service_search_response($text);
    }

    $keywords = preg_split('/\s+/', preg_replace('/[^\p{L}\p{N}\s-]/u', ' ', $text)) ?: [];
    foreach ($keywords as $keyword) {
        if (mb_strlen($keyword, 'UTF-8') >= 4) {
            $response = service_search_response($keyword);
            if (!str_contains($response, 'Nao encontrei')) {
                return $response;
            }
        }
    }

    return 'Ainda nao tenho uma resposta certa para isso. Posso ajudar com servicos, precos, duracao, cidades atendidas e agendamentos.';
}

function service_search_response(string $query): string
{
    $terms = array_values(array_filter(
        preg_split('/\s+/', preg_replace('/[^\p{L}\p{N}\s-]/u', ' ', $query)) ?: [],
        static fn(string $term): bool => mb_strlen($term, 'UTF-8') >= 3
    ));

    $where = '';
    $params = [];
    if ($terms) {
        $clauses = [];
        foreach (array_slice($terms, 0, 4) as $term) {
            $clauses[] = '(s.nome LIKE ? OR s.descricao LIKE ? OR c.nome LIKE ?)';
            $like = '%' . $term . '%';
            array_push($params, $like, $like, $like);
        }
        $where = 'WHERE ' . implode(' OR ', $clauses);
    }

    $sql = "SELECT s.nome, s.descricao, s.preco_base, s.duracao_estimada_minutos, c.nome AS categoria
            FROM servico s
            JOIN categoria_profissional c ON c.id = s.categoria_id
            $where
            ORDER BY c.nome, s.nome
            LIMIT 5";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $services = $stmt->fetchAll();

    if (!$services) {
        return 'Nao encontrei esse servico no catalogo. Pode abrir a pagina Servicos para ver todas as opcoes disponiveis.';
    }

    $lines = ['Encontrei estes servicos:'];
    foreach ($services as $service) {
        $lines[] = '- ' . $service['nome'] . ' (' . $service['categoria'] . '): ' . money($service['preco_base']) . ', cerca de ' . (int) $service['duracao_estimada_minutos'] . ' min.';
    }
    $lines[] = 'Para reservar, use a pagina Agendar.';

    return implode("\n", $lines);
}

try {
    $payload = json_decode((string) file_get_contents('php://input'), true);
    $message = is_array($payload) ? (string) ($payload['message'] ?? '') : '';

    echo json_encode(['reply' => bot_response($message)], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['reply' => 'O assistente nao conseguiu responder agora. Tente novamente dentro de momentos.']);
}
