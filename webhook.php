<?php
require_once('vendor/autoload.php');
require_once('config.php');

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$endpoint_secret = 'whsec_8EAkijxEdu2i2xqrE96YlsDpZVcoitPy';

// Récupération sécurisée du payload et de la signature
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (empty($payload) || empty($sig_header)) {
    http_response_code(400);
    echo "Missing payload or signature";
    exit();
}

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch (\Exception $e) {
    http_response_code(400);
    error_log("Webhook signature verification failed: " . $e->getMessage());
    exit();
}

// Traitement de l'événement
if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;

    $email = $session->customer_details->email ?? '';
    $nom = $session->customer_details->name ?? '';
    
    $adresseLivraison = $session->shipping_details->address ?? 
                       ($session->customer_details->address ?? null);

    $ligne = [
        'date' => date('Y-m-d H:i:s'),
        'email' => $email,
        'nom' => $nom,
        'adresse' => [
            'ligne1' => $adresseLivraison->line1 ?? '',
            'ligne2' => $adresseLivraison->line2 ?? '',
            'code_postal' => $adresseLivraison->postal_code ?? '',
            'ville' => $adresseLivraison->city ?? '',
            'pays' => $adresseLivraison->country ?? '',
        ],
    ];

    // Enregistrement dans le log
    file_put_contents('commandes.log', json_encode($ligne, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
}

http_response_code(200);
echo json_encode(['received' => true]);
?>
