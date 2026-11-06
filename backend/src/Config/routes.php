<?php

declare(strict_types=1);

namespace App\Config;

use App\Controllers\HealthController;
use App\Controllers\ImportController;
use App\Controllers\ProductController;
use App\Middlewares\ErrorHandlerMiddleware;
use Slim\App;

return static function (App $app): void {
    $app->add(ErrorHandlerMiddleware::class);

    $app->get('/api/health', HealthController::class);

    $app->post('/api/imports', [ImportController::class, 'create']);
    $app->get('/api/imports/{job_id}', [ImportController::class, 'status']);

    $app->get('/api/products', [ProductController::class, 'list']);
    $app->get('/api/products/{id}', [ProductController::class, 'detail']);
};
