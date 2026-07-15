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
    // Quantité commandée (entre 1 et 10)
    $quantite   = isset($input['quantite']) ? intval($input['quantite']) : 1;
    if ($quantite < 1) { $quantite = 1; }
    if ($quantite > 10) { $quantite = 10; }
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
        'quantite'    => $quantite,
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
                'unit_amount' => 1990,
            ],
            'quantity' => $quantite,
            'adjustable_quantity' => [
                'enabled' => true,
                'minimum' => 1,
                'maximum' => 10,
            ],
        ]],
        'shipping_options' => [[
            'shipping_rate_data' => [
                'type' => 'fixed_amount',
                'fixed_amount' => [
                    'amount' => 415,
                    'currency' => 'eur',
                ],
                'display_name' => 'Frais de port',
            ],
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
            'quantite'   => $quantite,
        ],
    ]);
    echo json_encode(['url' => $session->url]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

