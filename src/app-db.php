<?php

$appDb = new PDO(
    'mysql:host=127.0.0.1;dbname=pauperdeck;charset=utf8mb4',
    'pauperdeck',
    '646313'
);

$appDb->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

return $appDb;
