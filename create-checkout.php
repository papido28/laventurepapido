<?php
require_once('vendor/autoload.php');
require_once('config.php');

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

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
        'success_url' => 'https://laventurepapido.fr/merci.html',
        'cancel_url'  => 'https://laventurepapido.fr/commande.html',
        
        // ←←← TU METS ÇA ICI
        'billing_address_collection' => 'required',           // Adresse de facturation
        'shipping_address_collection' => [                    // Adresse de livraison
            'allowed_countries' => ['FR']
        ],
        // ←←← FIN

        'customer_creation' => 'always',
        'locale' => 'fr'
    ]);

    echo json_encode(['url' => $session->url]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
