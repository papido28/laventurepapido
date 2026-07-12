<?php
require_once('vendor/autoload.php');
require_once('config.php');
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
$endpoint_secret = 'whsec_8EAkijxEdu2i2xqrE96YlsDpZVcoitPy';

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch (\Exception $e) {
    http_response_code(400);
    exit();
}

if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;

    $email = $session->customer_details->email ?? '';
    $nom = $session->customer_details->name ?? '';
    $adresseFacturation = $session->customer_details->address ?? null;
    $adresseLivraison = $session->shipping_details->address ?? $adresseFacturation;

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

    // Enregistrement dans un fichier log (une ligne JSON par commande)
    file_put_contents('commandes.log', json_encode($ligne, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
}

http_response_code(200);
echo json_encode(['received' => true]);
?>
