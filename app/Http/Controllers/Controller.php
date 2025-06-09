<?php

declare(strict_types=1);

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="OLX Price Tracker API",
 *     version="1.0.0",
 *     description="API for tracking price changes on OLX advertisements",
 *
 *     @OA\Contact(
 *         email="admin@example.com",
 *         name="API Support"
 *     ),
 *
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="/api",
 *     description="API Server"
 * )
 *
 * @OA\Tag(
 *     name="Subscriptions",
 *     description="API Endpoints for managing subscriptions"
 * )
 */
abstract class Controller
{
    //
}
