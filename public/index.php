<?php

session_start();

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();

$app->addBodyParsingMiddleware();

//
// Helpers
//

require __DIR__ . '/../src/helpers.php';

//
// API routes
//

require __DIR__ . '/../src/routes/cards.php';

require __DIR__ . '/../src/routes/auth.php';

require __DIR__ . '/../src/routes/decks.php';

require __DIR__ . '/../src/routes/deck-cards.php';



/*
|--------------------------------------------------------------------------
| HOME
|--------------------------------------------------------------------------
*/

$app->get('/', function (
    $request,
    $response
) {

    return $response
        ->withHeader(
            'Location',
            '/login'
        )
        ->withStatus(302);
});



/*
|--------------------------------------------------------------------------
| LOGIN PAGE
|--------------------------------------------------------------------------
*/

$app->get('/login', function (
    $request,
    $response
) {

    ob_start();

    require __DIR__
        . '/views/login.php';


    $html = ob_get_clean();

    $response->getBody()
        ->write($html);

    return $response;
});



/*
|--------------------------------------------------------------------------
| REGISTER PAGE
|--------------------------------------------------------------------------
*/

$app->get('/register', function (
    $request,
    $response
) {

    ob_start();

    require __DIR__
        . '/views/register.php';

    $html = ob_get_clean();

    $response->getBody()
        ->write($html);

    return $response;
});



/*
|--------------------------------------------------------------------------
| DASHBOARD PAGE
|--------------------------------------------------------------------------
*/

$app->get('/dashboard', function (
    $request,
    $response
) {

    ob_start();

    require __DIR__
        . '/views/dashboard.php';

    $html = ob_get_clean();

    $response->getBody()
        ->write($html);

    return $response;
});

/*
|--------------------------------------------------------------------------
| deck PAGE
|--------------------------------------------------------------------------
*/

$app->get('/deck/{id}', function (
    $request,
    $response,
    $args
) {

    $deckId =
        (int)$args['id'];

    ob_start();

    require __DIR__
        . '/views/deck.php';

    $html = ob_get_clean();

    $response->getBody()
        ->write($html);

    return $response;
});

$app->run();
