<?php
require_once('vendor/autoload.php');

\Stripe\Stripe::setApiKey($_SERVER['STRIPE_SECRET_KEY']);   // ← On lit la clé ici

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $adresse_complete = $input['numero'] . ' ' . $input['voie'];
    if (!empty($input['complement'])) $adresse_complete .= ' - ' . $input['complement'];

    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'customer_email' => $input['email'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => 'Livre PAPIDO - Le Tour de France à vélo',
                ],
                'unit_amount' => 2300,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'https://tonsite.com/merci.html',
        'cancel_url'  => 'https://tonsite.com/commande.html',
        'billing_address_collection' => 'required',
    ]);

    echo json_encode(['url' => $session->url]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
