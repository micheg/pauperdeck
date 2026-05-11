<?php

use Psr\Http\Message\ResponseInterface as Response;

/*
|--------------------------------------------------------------------------
| JSON RESPONSE
|--------------------------------------------------------------------------
*/

function jsonResponse(
    Response $response,
    mixed $data,
    int $status = 200
): Response {

    $response->getBody()->write(
        json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        )
    );

    return $response
    ->withStatus( $status )
    ->withHeader(
        'Content-Type',
        'application/json'
    );
}

/*
|--------------------------------------------------------------------------
| AUTH REQUIRED
|--------------------------------------------------------------------------
*/

function requireAuth( Response $response ): ?Response {

    if ( !isset( $_SESSION[ 'user_id' ] ) ) {

        return jsonResponse(
            $response,
            [
                'error' => 'Unauthorized'
            ],
            401
        );
    }

    return null;
}

/*
|--------------------------------------------------------------------------
| CURRENT USER ID
|--------------------------------------------------------------------------
*/

function currentUserId(): ?int {

    return $_SESSION[ 'user_id' ] ?? null;
}

/*
|--------------------------------------------------------------------------
| VALIDATE EMAIL
|--------------------------------------------------------------------------
*/

function isValidEmail( string $email ): bool {

    return filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    ) !== false;
}

/*
|--------------------------------------------------------------------------
| TRIM STRING
|--------------------------------------------------------------------------
*/

function safeTrim(
    mixed $value
): string {

    return trim(
        ( string ) $value
    );
}

function normalizeCardName(
    string $name
): string {

    //
    // Lowercase
    //

    $name = mb_strtolower( $name );

    //
    // Remove accents
    //

    $name = iconv(
        'UTF-8',
        'ASCII//TRANSLIT',
        $name
    );

    //
    // Cleanup
    //

    $name = preg_replace(
        '/[^a-z0-9 ]/',
        '',
        $name
    );

    //
    // Normalize spaces
    //

    $name = preg_replace(
        '/\s+/',
        ' ',
        $name
    );

    return trim( $name );
}
