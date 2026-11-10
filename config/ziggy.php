<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Маршруты, доступные на клиенте
    |--------------------------------------------------------------------------
    |
    | Отдаём только именованные маршруты UI. API-маршруты клиенту не нужны —
    | они вызываются через axios с явными путями, и лишний список эндпоинтов
    | в HTML-пейлоаде нам не нужен.
    |
    */

    'only' => [
        'landing',
        'login',
        'login.store',
        'register',
        'register.store',
        'logout',
        'dashboard',
        'dashboard.generate',
        'studio',
        'history',
        'library',
        'projects',
        'projects.store',
        'projects.update',
        'projects.destroy',
        'usage',
        'media.show',
    ],

];
