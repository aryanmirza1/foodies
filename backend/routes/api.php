<?php

use App\Http\Controllers\Api\PublicCatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function () {
    Route::get('/settings', [PublicCatalogController::class, 'settings']);
    Route::get('/home', [PublicCatalogController::class, 'home']);
    Route::get('/banners', [PublicCatalogController::class, 'banners']);
    Route::get('/categories', [PublicCatalogController::class, 'categories']);
    Route::get('/menu-items', [PublicCatalogController::class, 'menuItems']);
    Route::get('/menu-items/{slug}', [PublicCatalogController::class, 'menuItem']);
});
