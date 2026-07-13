<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$fichier = 'avis.json';

function chargerAvis($fichier) {
    if (!file_exists($fichier)) {
        return [];
    }
    $contenu = file_get_contents($fichier);
    $avis = json_decode($contenu, true);
    return is_array($avis) ? $avis : [];
}

$methode = $_SERVER['REQUEST_METHOD'];

if ($methode === 'GET') {
    $avis = chargerAvis($fichier);
    echo json_encode($avis, JSON_UNESCAPED_UNICODE);
    exit();
}

if ($methode === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $nom = trim($input['name'] ?? '');
    $stars = intval($input['stars'] ?? 0);
    $text = trim($input['text'] ?? '');
    $location = trim($input['location'] ?? '');

    if (!$nom || !$text || $stars < 1 || $stars > 5) {
        http_response_code(400);
        echo json_encode(['error' => 'Données invalides']);
        exit();
    }

    $avis = chargerAvis($fichier);

    $nouvelAvis = [
        'stars' => $stars,
        'text' => $text,
        'name' => $nom,
        'location' => $location ?: 'Lecteur',
        'date' => date('F Y'),
    ];

    array_unshift($avis, $nouvelAvis);

    file_put_contents($fichier, json_encode($avis, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    echo json_encode(['success' => true, 'avis' => $avis]);
    exit();
}

http_response_code(405);
echo json_encode(['error' => 'Méthode non autorisée']);
?>
