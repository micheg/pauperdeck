<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$appDb = require __DIR__ . '/../app-db.php';

$cardsDb = require __DIR__ . '/../db.php';



/*
|--------------------------------------------------------------------------
| GET USER DECKS
|--------------------------------------------------------------------------
*/

$app->get('/api/decks', function (
    Request $request,
    Response $response
) use ($appDb) {

    $auth = requireAuth($response);

    if ($auth) {
        return $auth;
    }

    $stmt = $appDb->prepare("
        SELECT

            id,

            name,

            description,

            format_name,

            is_public,

            created_at,

            updated_at

        FROM decks

        WHERE user_id = :user_id

        ORDER BY updated_at DESC
    ");

    $stmt->execute([
        ':user_id' => currentUserId()
    ]);

    $decks = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

    return jsonResponse(
        $response,
        $decks
    );
});



/*
|--------------------------------------------------------------------------
| GET SINGLE DECK
|--------------------------------------------------------------------------
*/

$app->get('/api/decks/{id}', function (
    Request $request,
    Response $response,
    array $args
) use ($appDb) {

    $auth = requireAuth($response);

    if ($auth) {
        return $auth;
    }

    $deckId = (int)$args['id'];

    $stmt = $appDb->prepare("
        SELECT *

        FROM decks

        WHERE
            id = :id
            AND user_id = :user_id

        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $deckId,
        ':user_id' => currentUserId()
    ]);

    $deck = $stmt->fetch(
        PDO::FETCH_ASSOC
    );

    if (!$deck) {

        return jsonResponse(
            $response,
            [
                'error' => 'Deck not found'
            ],
            404
        );
    }

    return jsonResponse(
        $response,
        $deck
    );
});



/*
|--------------------------------------------------------------------------
| CREATE DECK
|--------------------------------------------------------------------------
*/

$app->post('/api/decks', function (
    Request $request,
    Response $response
) use ($appDb) {

    $auth = requireAuth($response);

    if ($auth) {
        return $auth;
    }

    $data = $request->getParsedBody();

    $name = safeTrim(
        $data['name'] ?? ''
    );

    $description = safeTrim(
        $data['description'] ?? ''
    );

    $formatName = safeTrim(
        $data['format_name'] ?? 'pauper'
    );

    $isPublic = (int)(
        $data['is_public'] ?? 0
    );

    if (!$name) {

        return jsonResponse(
            $response,
            [
                'error' => 'Missing deck name'
            ],
            400
        );
    }

    $stmt = $appDb->prepare("
        INSERT INTO decks (

            user_id,

            name,

            description,

            format_name,

            is_public

        )
        VALUES (

            :user_id,

            :name,

            :description,

            :format_name,

            :is_public
        )
    ");

    $stmt->execute([
        ':user_id' => currentUserId(),
        ':name' => $name,
        ':description' => $description,
        ':format_name' => $formatName,
        ':is_public' => $isPublic
    ]);

    return jsonResponse(
        $response,
        [
            'success' => true,
            'deck_id' => (int)$appDb->lastInsertId()
        ]
    );
});



/*
|--------------------------------------------------------------------------
| UPDATE DECK
|--------------------------------------------------------------------------
*/

$app->put('/api/decks/{id}', function (
    Request $request,
    Response $response,
    array $args
) use ($appDb) {

    $auth = requireAuth($response);

    if ($auth) {
        return $auth;
    }

    $deckId = (int)$args['id'];

    $data = $request->getParsedBody();

    $name = safeTrim(
        $data['name'] ?? ''
    );

    $description = safeTrim(
        $data['description'] ?? ''
    );

    $formatName = safeTrim(
        $data['format_name'] ?? 'pauper'
    );

    $isPublic = (int)(
        $data['is_public'] ?? 0
    );

    if (!$name) {

        return jsonResponse(
            $response,
            [
                'error' => 'Missing deck name'
            ],
            400
        );
    }

    $stmt = $appDb->prepare("
        UPDATE decks

        SET

            name = :name,

            description = :description,

            format_name = :format_name,

            is_public = :is_public

        WHERE

            id = :id

            AND user_id = :user_id
    ");

    $stmt->execute([
        ':name' => $name,
        ':description' => $description,
        ':format_name' => $formatName,
        ':is_public' => $isPublic,
        ':id' => $deckId,
        ':user_id' => currentUserId()
    ]);

    return jsonResponse(
        $response,
        [
            'success' => true
        ]
    );
});



/*
|--------------------------------------------------------------------------
| DELETE DECK
|--------------------------------------------------------------------------
*/

$app->delete('/api/decks/{id}', function (
    Request $request,
    Response $response,
    array $args
) use ($appDb) {

    $auth = requireAuth($response);

    if ($auth) {
        return $auth;
    }

    $deckId = (int)$args['id'];

    $stmt = $appDb->prepare("
        DELETE FROM decks

        WHERE

            id = :id

            AND user_id = :user_id
    ");

    $stmt->execute([
        ':id' => $deckId,
        ':user_id' => currentUserId()
    ]);

    return jsonResponse(
        $response,
        [
            'success' => true
        ]
    );
});



/*
|--------------------------------------------------------------------------
| IMPORT DECK LIST
|--------------------------------------------------------------------------
*/

$app->post('/api/decks/{id}/import', function (
    Request $request,
    Response $response,
    array $args
) use ($appDb, $cardsDb) {

    $auth = requireAuth($response);

    if ($auth) {
        return $auth;
    }

    $deckId = (int)$args['id'];

    //
    // Verify ownership
    //

    $stmt = $appDb->prepare("
        SELECT id

        FROM decks

        WHERE
            id = :id
            AND user_id = :user_id

        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $deckId,
        ':user_id' => currentUserId()
    ]);

    $deck = $stmt->fetch(
        PDO::FETCH_ASSOC
    );

    if (!$deck) {

        return jsonResponse(
            $response,
            [
                'error' => 'Deck not found'
            ],
            404
        );
    }

    //
    // Input
    //

    $data = $request->getParsedBody();

    $text = trim(
        $data['text'] ?? ''
    );

    if (!$text) {

        return jsonResponse(
            $response,
            [
                'error' => 'Missing deck text'
            ],
            400
        );
    }

    //
    // Clear deck
    //

    $stmt = $appDb->prepare("
        DELETE FROM deck_cards
        WHERE deck_id = :deck_id
    ");

    $stmt->execute([
        ':deck_id' => $deckId
    ]);

    //
    // Parse
    //

    $lines = preg_split(
        '/\r\n|\r|\n/',
        $text
    );

    $isSideboard = 0;

    $inserted = [];

    $errors = [];

    foreach ($lines as $line) {

        $line = trim($line);

        //
        // Skip empty
        //

        if (!$line) {
            continue;
        }

        //
        // Sideboard switch
        //

        if (
            strtolower($line)
            === 'sideboard'
        ) {

            $isSideboard = 1;

            continue;
        }

        //
        // Parse:
        // 4 Brainstorm
        //

        if (
            !preg_match(
                '/^(\d+)\s+(.+)$/',
                $line,
                $matches
            )
        ) {

            $errors[] = [
                'line' => $line,
                'error' => 'Invalid format'
            ];

            continue;
        }

        $quantity = (int)$matches[1];

        $cardName = trim($matches[2]);

        //
        // Normalize
        //

        $normalizedCardName =
            normalizeCardName($cardName);

        //
        // Search card
        //

        $stmt = $cardsDb->prepare("
            SELECT

                id,

                name_en,

                name_it

            FROM cards

            WHERE

                name_en_normalized = :name

                OR name_it_normalized = :name

            ORDER BY released_at DESC

            LIMIT 1
        ");

        $stmt->execute([
            ':name' => $normalizedCardName
        ]);

        $card = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        //
        // Not found
        //

        if (!$card) {

            $errors[] = [
                'line' => $line,
                'error' => 'Card not found'
            ];

            continue;
        }

        //
        // Insert
        //

        $stmt = $appDb->prepare("
            INSERT INTO deck_cards (

                deck_id,

                card_id,

                quantity,

                is_sideboard

            )
            VALUES (

                :deck_id,

                :card_id,

                :quantity,

                :is_sideboard
            )
        ");

        $stmt->execute([
            ':deck_id' => $deckId,
            ':card_id' => $card['id'],
            ':quantity' => $quantity,
            ':is_sideboard' => $isSideboard
        ]);

        $inserted[] = [
            'quantity' => $quantity,
            'card' => $card['name_en'],
            'sideboard' => $isSideboard
        ];
    }

    return jsonResponse(
        $response,
        [
            'success' => true,
            'inserted_count' => count($inserted),
            'errors_count' => count($errors),
            'inserted' => $inserted,
            'errors' => $errors
        ]
    );
});
/*
|--------------------------------------------------------------------------
| EXPORT DECK
|--------------------------------------------------------------------------
*/

$app->get('/api/decks/{id}/export', function (
    Request $request,
    Response $response,
    array $args
) use ($appDb, $cardsDb) {

    //
    // Auth
    //

    $auth = requireAuth($response);

    if ($auth) {
        return $auth;
    }

    $deckId = (int)$args['id'];

    //
    // Verify ownership
    //

    $stmt = $appDb->prepare("
        SELECT *

        FROM decks

        WHERE
            id = :id
            AND user_id = :user_id

        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $deckId,
        ':user_id' => currentUserId()
    ]);

    $deck = $stmt->fetch(
        PDO::FETCH_ASSOC
    );

    //
    // Not found
    //

    if (!$deck) {

        return jsonResponse(
            $response,
            [
                'error' => 'Deck not found'
            ],
            404
        );
    }

    //
    // Get deck cards
    //

    $stmt = $appDb->prepare("
        SELECT

            card_id,

            quantity,

            is_sideboard

        FROM deck_cards

        WHERE deck_id = :deck_id

        ORDER BY is_sideboard ASC
    ");

    $stmt->execute([
        ':deck_id' => $deckId
    ]);

    $deckCards = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

    //
    // Load cards from SQLite
    //

    $cards = [];

    foreach ($deckCards as $deckCard) {

        $stmtCard = $cardsDb->prepare("
            SELECT

                name_en

            FROM cards

            WHERE id = :id

            LIMIT 1
        ");

        $stmtCard->execute([
            ':id' => $deckCard['card_id']
        ]);

        $card = $stmtCard->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$card) {
            continue;
        }

        $cards[] = [

            'quantity' =>
                (int)$deckCard['quantity'],

            'is_sideboard' =>
                (int)$deckCard['is_sideboard'],

            'name_en' =>
                $card['name_en']
        ];
    }

    //
    // Build export text
    //

    $main = [];

    $side = [];

    foreach ($cards as $card) {

        $line =
            $card['quantity']
            . ' '
            . $card['name_en'];

        if (
            $card['is_sideboard'] === 1
        ) {

            $side[] = $line;

        } else {

            $main[] = $line;
        }
    }

    //
    // Final output
    //

    $output = implode(
        "\n",
        $main
    );

    if (count($side) > 0) {

        $output .= "\n\nSideboard\n";

        $output .= implode(
            "\n",
            $side
        );
    }

    //
    // Response
    //

    return jsonResponse(
        $response,
        [
            'success' => true,
            'deck_id' => $deckId,
            'name' => $deck['name'],
            'export' => $output
        ]
    );
});
