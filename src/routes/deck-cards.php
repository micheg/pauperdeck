<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$appDb = require __DIR__ . '/../app-db.php';

$cardsDb = require __DIR__ . '/../db.php';



/*
|--------------------------------------------------------------------------
| GET DECK CARDS
|--------------------------------------------------------------------------
*/

$app->get('/api/decks/{id}/cards', function (
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
    // Enrich with card data
    //

    $result = [];

    foreach ($deckCards as $deckCard) {

        $stmtCard = $cardsDb->prepare("
            SELECT

                id,

                oracle_id,

                name_en,
                name_it,

                mana_cost,

                cmc,

                colors,

                main_type,
                sub_types,

                rarity,

                set_code,

                img_a,
                img_b

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

        $card['quantity'] =
            (int)$deckCard['quantity'];

        $card['is_sideboard'] =
            (int)$deckCard['is_sideboard'];

        $result[] = $card;
    }

    //
    // Response
    //

    return jsonResponse(
        $response,
        $result
    );
});



/*
|--------------------------------------------------------------------------
| ADD CARD TO DECK
|--------------------------------------------------------------------------
*/

$app->post('/api/decks/{id}/cards', function (
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

    $data = $request->getParsedBody();

    $cardId = safeTrim(
        $data['card_id'] ?? ''
    );

    $quantity = (int)(
        $data['quantity'] ?? 1
    );

    $isSideboard = (int)(
        $data['is_sideboard'] ?? 0
    );

    //
    // Validation
    //

    if (!$cardId) {

        return jsonResponse(
            $response,
            [
                'error' => 'Missing card_id'
            ],
            400
        );
    }

    if ($quantity < 1) {
        $quantity = 1;
    }

    //
    // Verify deck ownership
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
    // Verify card exists
    //

    $stmt = $cardsDb->prepare("
        SELECT id
        FROM cards
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $cardId
    ]);

    $cardExists = $stmt->fetch(
        PDO::FETCH_ASSOC
    );

    if (!$cardExists) {

        return jsonResponse(
            $response,
            [
                'error' => 'Card not found'
            ],
            404
        );
    }

    //
    // Existing row?
    //

    $stmt = $appDb->prepare("
        SELECT *
        FROM deck_cards
        WHERE
            deck_id = :deck_id
            AND card_id = :card_id
            AND is_sideboard = :is_sideboard
        LIMIT 1
    ");

    $stmt->execute([
        ':deck_id' => $deckId,
        ':card_id' => $cardId,
        ':is_sideboard' => $isSideboard
    ]);

    $existing = $stmt->fetch(
        PDO::FETCH_ASSOC
    );

    //
    // Update quantity
    //

    if ($existing) {

        $newQuantity =
            (int)$existing['quantity']
            + $quantity;

        $stmt = $appDb->prepare("
            UPDATE deck_cards
            SET quantity = :quantity
            WHERE id = :id
        ");

        $stmt->execute([
            ':quantity' => $newQuantity,
            ':id' => $existing['id']
        ]);

    } else {

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
            ':card_id' => $cardId,
            ':quantity' => $quantity,
            ':is_sideboard' => $isSideboard
        ]);
    }

    //
    // Response
    //

    return jsonResponse(
        $response,
        [
            'success' => true
        ]
    );
});



/*
|--------------------------------------------------------------------------
| UPDATE CARD QUANTITY
|--------------------------------------------------------------------------
*/

$app->put('/api/decks/{id}/cards/{cardId}', function (
    Request $request,
    Response $response,
    array $args
) use ($appDb) {

    //
    // Auth
    //

    $auth = requireAuth($response);

    if ($auth) {
        return $auth;
    }

    $deckId = (int)$args['id'];

    $cardId = safeTrim(
        $args['cardId']
    );

    $data = $request->getParsedBody();
/*
    $quantity = (int)(
        $data['quantity'] ?? 1
    );
*/
    $quantity = (int)(
        $data['quantity'] ?? 1
    );

    $isSideboard = (int)(
        $data['is_sideboard'] ?? 0
    );
    //
    // Delete if <= 0
    //

    if ($quantity <= 0) {

        $stmt = $appDb->prepare("
        DELETE FROM deck_cards
        WHERE
            deck_id = :deck_id
            AND card_id = :card_id
            AND is_sideboard = :is_sideboard
        ");

        $stmt->execute([
            ':deck_id' => $deckId,
            ':card_id' => $cardId
        ]);

        return jsonResponse(
            $response,
            [
                'success' => true,
                'deleted' => true
            ]
        );
    }

    //
    // Update
    //

    $stmt = $appDb->prepare("
      UPDATE deck_cards

      SET quantity = :quantity

      WHERE

          deck_id = :deck_id

          AND card_id = :card_id

          AND is_sideboard = :is_sideboard
    ");

    $stmt->execute([
        ':quantity' => $quantity,
        ':deck_id' => $deckId,
        ':card_id' => $cardId,
        ':is_sideboard' => $isSideboard
    ]);

    //
    // Response
    //

    return jsonResponse(
        $response,
        [
            'success' => true
        ]
    );
});



/*
|--------------------------------------------------------------------------
| REMOVE CARD
|--------------------------------------------------------------------------
*/

$app->delete('/api/decks/{id}/cards/{cardId}', function (
    Request $request,
    Response $response,
    array $args
) use ($appDb) {

    //
    // Auth
    //

    $auth = requireAuth($response);

    if ($auth) {
        return $auth;
    }

    $deckId = (int)$args['id'];

    $cardId = safeTrim(
        $args['cardId']
    );

    $isSideboard = (int)(
        $request->getQueryParams()['is_sideboard'] ?? 0
    );

    //
    // Delete
    //

    $stmt = $appDb->prepare("
        DELETE FROM deck_cards

        WHERE

            deck_id = :deck_id

            AND card_id = :card_id
            AND is_sideboard = :is_sideboard
    ");

    $stmt->execute([
        ':deck_id' => $deckId,
        ':card_id' => $cardId
    ]);

    //
    // Response
    //

    return jsonResponse(
        $response,
        [
            'success' => true
        ]
    );
});
