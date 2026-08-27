<?php

declare(strict_types=1);

use App\Domain\Auth\Livewire\Permissions\PermissionIndex;
use App\Domain\Auth\Livewire\Roles\RoleIndex;
use App\Domain\Auth\Livewire\Users\UserIndex;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('users', UserIndex::class)->name('users.index');
        Route::get('roles', RoleIndex::class)->name('roles.index');
        Route::get('permissions', PermissionIndex::class)->name('permissions.index');
    });
});

require __DIR__.'/settings.php';
