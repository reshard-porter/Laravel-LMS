<?php

namespace App\Providers;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Admins manage people and the class list.
        Gate::define('admin', fn (User $user) => $user->isAdmin());

        // Admins, and the teacher assigned to the class, manage its roster, assignments, grades and attendance.
        Gate::define('manage-classroom', fn (User $user, Classroom $classroom) => $user->canManage($classroom));
    }
}
