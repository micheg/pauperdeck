<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$appDb = require __DIR__ . '/app-db.php';
$cardsDb = require __DIR__ . '/db.php';

$app->get('/api/card/{id}', function (
    Request $request,
    Response $response,
    array $args
) use ($cardsDb) {

    $id = $args['id'];

    $stmt = $cardsDb->prepare("
        SELECT *
        FROM cards
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $card = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$card) {

        $response->getBody()->write(
            json_encode([
                'error' => 'Card not found'
            ])
        );

        return $response
            ->withStatus(404)
            ->withHeader(
                'Content-Type',
                'application/json'
            );
    }

    $response->getBody()->write(
        json_encode(
            $card,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        )
    );

    return $response
        ->withHeader(
            'Content-Type',
            'application/json'
        );
});

$app->get('/api/cards', function (
    Request $request,
    Response $response
) use ($cardsDb) {

    $queryParams = $request->getQueryParams();

    //
    // Params
    //

    $query = trim($queryParams['q'] ?? '');

    $colors = trim($queryParams['colors'] ?? '');

    $type = trim($queryParams['type'] ?? '');

    $rarity = trim($queryParams['rarity'] ?? '');

    $set = trim($queryParams['set'] ?? '');

    $order = trim($queryParams['order'] ?? 'name_en');

    $limit = (int)($queryParams['limit'] ?? 50);

    $unique = trim($queryParams['unique'] ?? '');

    //
    // Safety
    //

    if ($limit < 1) {
        $limit = 1;
    }

    if ($limit > 200) {
        $limit = 200;
    }

    //
    // Allowed order
    //

    $allowedOrder = [
        'name_en',
        'released_at',
        'cmc',
        'rarity'
    ];

    if (!in_array($order, $allowedOrder)) {
        $order = 'name_en';
    }

    //
    // Base select
    //

    if ($unique === 'oracle') {

        $sql = "
            SELECT
                MIN(id) as id,
                oracle_id,
                name_en,
                name_it,
                mana_cost,
                cmc,
                colors,
                main_type,
                rarity,
                set_code,
                released_at,
                img_a
            FROM cards
        ";

    } else {

        $sql = "
            SELECT
                id,
                oracle_id,
                name_en,
                name_it,
                mana_cost,
                cmc,
                colors,
                main_type,
                rarity,
                set_code,
                released_at,
                img_a
            FROM cards
        ";
    }

    //
    // Dynamic where
    //

    $where = [];

    $params = [];

    //
    // Name search
    //

    if ($query !== '') {

        $where[] = "
            (
                name_en LIKE :query
                OR name_it LIKE :query
            )
        ";

        $params[':query'] = '%' . $query . '%';
    }

    //
    // Colors
    //

    if ($colors !== '') {

        $where[] = "colors LIKE :colors";

        $params[':colors'] = '%' . $colors . '%';
    }

    //
    // Type
    //

    if ($type !== '') {

        $where[] = "
            (
                main_type LIKE :type
                OR sub_types LIKE :type
            )
        ";

        $params[':type'] = '%' . $type . '%';
    }

    //
    // Rarity
    //

    if ($rarity !== '') {

        $where[] = "rarity = :rarity";

        $params[':rarity'] = $rarity;
    }

    //
    // Set
    //

    if ($set !== '') {

        $where[] = "set_code = :set_code";

        $params[':set_code'] = $set;
    }

    //
    // Apply where
    //

    if (!empty($where)) {

        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    //
    // Unique oracle
    //

    if ($unique === 'oracle') {

        $sql .= ' GROUP BY oracle_id ';
    }

    //
    // Order
    //

    $sql .= " ORDER BY {$order} ";

    //
    // Limit
    //

    $sql .= " LIMIT :limit ";

    //
    // Prepare
    //

    $stmt = $cardsDb->prepare($sql);

    //
    // Bind params
    //

    foreach ($params as $key => $value) {

        $stmt->bindValue(
            $key,
            $value,
            PDO::PARAM_STR
        );
    }

    $stmt->bindValue(
        ':limit',
        $limit,
        PDO::PARAM_INT
    );

    //
    // Execute
    //

    $stmt->execute();

    $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //
    // JSON response
    //

    $response->getBody()->write(
        json_encode(
            $cards,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        )
    );

    return $response
        ->withHeader(
            'Content-Type',
            'application/json'
        );
});

$app->get('/api/oracle/{oracle_id}', function (
    Request $request,
    Response $response,
    array $args
) use ($cardsDb) {

    $oracleId = $args['oracle_id'];

    //
    // Query all printings
    //

    $stmt = $cardsDb->prepare("
        SELECT
            id,
            oracle_id,

            name_en,
            name_it,

            lang,

            mana_cost,
            cmc,

            colors,

            main_type,
            sub_types,

            set_code,
            set_name,

            collector_number,

            rarity,

            frame,

            released_at,

            foil,
            nonfoil,

            img_a,
            img_b

        FROM cards

        WHERE oracle_id = :oracle_id

        ORDER BY released_at DESC
    ");

    $stmt->execute([
        ':oracle_id' => $oracleId
    ]);

    $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //
    // Not found
    //

    if (!$cards) {

        $response->getBody()->write(
            json_encode([
                'error' => 'Oracle card not found'
            ])
        );

        return $response
            ->withStatus(404)
            ->withHeader(
                'Content-Type',
                'application/json'
            );
    }

    //
    // JSON response
    //

    $response->getBody()->write(
        json_encode(
            $cards,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        )
    );

    return $response
        ->withHeader(
            'Content-Type',
            'application/json'
        );
});
