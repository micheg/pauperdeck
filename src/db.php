<?php

$config = require __DIR__ . '/config.php';

$cardsDb = new PDO(
    'sqlite:' . $config['cards_db']
);

$cardsDb->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

return $cardsDb;
