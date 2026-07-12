<?php
require_once('vendor/autoload.php');
require_once('config.php');
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    // Récupération des champs du formulaire
    $prenom     = trim($input['prenom'] ?? '');
    $nom        = trim($input['nom'] ?? '');
    $numero     = trim($input['numero'] ?? '');
    $voie       = trim($input['voie'] ?? '');
    $complement = trim($input['complement'] ?? '');
    $codepostal = trim($input['codepostal'] ?? '');
    $ville      = trim($input['ville'] ?? '');
    $email      = trim($input['email'] ?? '');
    $dedicace   = trim($input['dedicace'] ?? '');

    // Validation minimale
    if (!$prenom || !$nom || !$numero || !$voie || !$codepostal || !$ville || !$email) {
        http_response_code(400);
        echo json_encode(['error' => 'Champs obligatoires manquants']);
        exit();
    }

    // 1) Enregistrement immédiat de la commande sur le serveur
    $commande = [
        'date'        => date('Y-m-d H:i:s'),
        'prenom'      => $prenom,
        'nom'         => $nom,
        'adresse'     => "$numero $voie",
        'complement'  => $complement,
        'code_postal' => $codepostal,
        'ville'       => $ville,
        'email'       => $email,
        'dedicace'    => $dedicace,
        'statut'      => 'en_attente_paiement',
    ];
    file_put_contents('commandes.log', json_encode($commande, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

    // 2) Création de la session Stripe, avec email pré-rempli et dédicace en métadonnée
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'customer_email' => $email,
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => 'Livre PAPIDO - Le Tour de France à vélo',
                ],
                'unit_amount' => 2300,
            ],
            'quantity' => 1,
        ], [
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => 'Frais de port',
                ],
                'unit_amount' => 310,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'https://laventurepapido.fr/merci.html',
        'cancel_url'  => 'https://laventurepapido.fr/commande.html',
        'billing_address_collection' => 'required',
        'shipping_address_collection' => [
            'allowed_countries' => ['FR']
        ],
        'customer_creation' => 'always',
        'locale' => 'fr',
        'metadata' => [
            'prenom'     => $prenom,
            'nom'        => $nom,
            'dedicace'   => $dedicace,
        ],
    ]);

    echo json_encode(['url' => $session->url]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

