<?php
require_once('vendor/autoload.php');
require_once('config.php');
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$endpoint_secret = 'whsec_8EAkijxEdu2i2xqrE96YlsDpZVcoitPy'; // à récupérer dans le dashboard Stripe → Webhooks

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

    $email = $session->customer_details->email;
    $adresseFacturation = $session->customer_details->address;
    $adresseLivraison = $session->shipping_details->address ?? null;

    // Enregistrer en base de données, envoyer un email récap, etc.
}

http_response_code(200);
?>
