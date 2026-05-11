<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$appDb = require __DIR__ . '/../app-db.php';



/*
|--------------------------------------------------------------------------
| REGISTER
|--------------------------------------------------------------------------
*/

$app->post('/api/register', function (
    Request $request,
    Response $response
) use ($appDb) {

    $data = $request->getParsedBody();

    $username = safeTrim(
        $data['username'] ?? ''
    );

    $email = safeTrim(
        $data['email'] ?? ''
    );

    $password = safeTrim(
        $data['password'] ?? ''
    );

    //
    // Validation
    //

    if (
        !$username ||
        !$email ||
        !$password
    ) {

        return jsonResponse(
            $response,
            [
                'error' => 'Missing fields'
            ],
            400
        );
    }

    if (!isValidEmail($email)) {

        return jsonResponse(
            $response,
            [
                'error' => 'Invalid email'
            ],
            400
        );
    }

    if (strlen($password) < 6) {

        return jsonResponse(
            $response,
            [
                'error' => 'Password too short'
            ],
            400
        );
    }

    //
    // Existing user
    //

    $stmt = $appDb->prepare("
        SELECT id
        FROM users
        WHERE
            username = :username
            OR email = :email
        LIMIT 1
    ");

    $stmt->execute([
        ':username' => $username,
        ':email' => $email
    ]);

    $existingUser = $stmt->fetch(
        PDO::FETCH_ASSOC
    );

    if ($existingUser) {

        return jsonResponse(
            $response,
            [
                'error' => 'User already exists'
            ],
            400
        );
    }

    //
    // Insert
    //

    $stmt = $appDb->prepare("
        INSERT INTO users (
            username,
            email,
            password_hash
        )
        VALUES (
            :username,
            :email,
            :password_hash
        )
    ");

    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':password_hash' => password_hash(
            $password,
            PASSWORD_DEFAULT
        )
    ]);

    //
    // Auto login
    //

    $userId = (int)$appDb->lastInsertId();

    $_SESSION['user_id'] = $userId;

    //
    // Response
    //

    return jsonResponse(
        $response,
        [
            'success' => true,
            'user' => [
                'id' => $userId,
                'username' => $username,
                'email' => $email
            ]
        ]
    );
});



/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

$app->post('/api/login', function (
    Request $request,
    Response $response
) use ($appDb) {

    $data = $request->getParsedBody();

    $username = safeTrim(
        $data['username'] ?? ''
    );

    $password = safeTrim(
        $data['password'] ?? ''
    );

    //
    // Validation
    //

    if (
        !$username ||
        !$password
    ) {

        return jsonResponse(
            $response,
            [
                'error' => 'Missing credentials'
            ],
            400
        );
    }

    //
    // Find user
    //

    $stmt = $appDb->prepare("
        SELECT *
        FROM users
        WHERE username = :username
        LIMIT 1
    ");

    $stmt->execute([
        ':username' => $username
    ]);

    $user = $stmt->fetch(
        PDO::FETCH_ASSOC
    );

    //
    // Invalid credentials
    //

    if (
        !$user ||
        !password_verify(
            $password,
            $user['password_hash']
        )
    ) {

        return jsonResponse(
            $response,
            [
                'error' => 'Invalid credentials'
            ],
            401
        );
    }

    //
    // Session
    //

    $_SESSION['user_id'] = $user['id'];

    //
    // Response
    //

    return jsonResponse(
        $response,
        [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email']
            ]
        ]
    );
});



/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

$app->post('/api/logout', function (
    Request $request,
    Response $response
) {

    //
    // Destroy session
    //

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

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
| CURRENT USER
|--------------------------------------------------------------------------
*/

$app->get('/api/me', function (
    Request $request,
    Response $response
) use ($appDb) {

    //
    // Auth
    //

    $auth = requireAuth($response);

    if ($auth) {
        return $auth;
    }

    //
    // Query
    //

    $stmt = $appDb->prepare("
        SELECT
            id,
            username,
            email,
            created_at
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => currentUserId()
    ]);

    $user = $stmt->fetch(
        PDO::FETCH_ASSOC
    );

    //
    // Not found
    //

    if (!$user) {

        return jsonResponse(
            $response,
            [
                'error' => 'User not found'
            ],
            404
        );
    }

    //
    // Response
    //

    return jsonResponse(
        $response,
        $user
    );
});

