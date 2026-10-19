<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GradebookController;
use App\Http\Controllers\RosterController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

// Signing in
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1')->name('login.attempt');
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');

    // People
    Route::resource('teachers', TeacherController::class);
    Route::resource('students', StudentController::class);
    Route::get('/students/{student}/report-card', [StudentController::class, 'reportCard'])->name('students.report-card');

    // Classes
    Route::resource('classrooms', ClassroomController::class);

    Route::scopeBindings()->prefix('classrooms/{classroom}')->name('classrooms.')->group(function () {
        Route::post('/roster', [RosterController::class, 'store'])->name('roster.store');
        Route::delete('/roster/{student}', [RosterController::class, 'destroy'])->withoutScopedBindings()->name('roster.destroy');

        Route::resource('assignments', AssignmentController::class)->except(['index', 'show']);

        Route::get('/gradebook', [GradebookController::class, 'show'])->name('gradebook');
        Route::put('/gradebook', [GradebookController::class, 'update'])->name('gradebook.update');
        Route::get('/gradebook/export', [GradebookController::class, 'export'])->name('gradebook.export');

        Route::get('/attendance', [AttendanceController::class, 'show'])->name('attendance');
        Route::put('/attendance', [AttendanceController::class, 'update'])->name('attendance.update');
    });

    // Links from the original version of the app keep working.
    Route::redirect('/DisplayTeachers', '/teachers');
    Route::redirect('/DisplayStudents', '/students');
    Route::redirect('/DisplayCombinedData', '/classrooms');
    Route::redirect('/InsertTeacher', '/teachers/create');
    Route::redirect('/InsertStudent', '/students/create');
});
