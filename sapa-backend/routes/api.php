<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\AspirationController;
use App\Http\Controllers\Api\FacilityReportController;
use App\Http\Controllers\Api\BullyingReportController;
use App\Http\Controllers\Api\ReportCommentController;
use App\Http\Controllers\Api\ReportAttachmentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\SchoolClassController;
use App\Http\Controllers\Api\Admin\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES (tanpa auth)
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/classes/options', [SchoolClassController::class, 'options']);
Route::get('/settings/general', [SettingController::class, 'general']);
Route::get('/settings/appearance', [SettingController::class, 'appearance']);
Route::get('/settings/seo', [SettingController::class, 'seo']);
/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES (semua role, wajib login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // ================= REPORTS (umum, dicek Policy per-request) =================
    Route::get('/my-reports', [ReportController::class, 'myReports']);
    Route::get('/my-reports/stats', [ReportController::class, 'myStats']);

    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/{report}', [ReportController::class, 'show']);
    Route::patch('/reports/{report}', [ReportController::class, 'update']);
    Route::patch('/reports/{report}/status', [ReportController::class, 'updateStatus']); // staff/BK/admin
    Route::patch('/reports/{report}/assign', [ReportController::class, 'assign']);
    Route::delete('/reports/{report}', [ReportController::class, 'destroy']);

    // ================= ASPIRASI =================
    Route::get('/aspirations', [AspirationController::class, 'index']); // feed publik
    Route::post('/aspirations', [AspirationController::class, 'store']);
    Route::post('/aspirations/{report}/upvote', [AspirationController::class, 'upvote']);

    // ================= PENGADUAN FASILITAS =================
    Route::post('/facility-reports', [FacilityReportController::class, 'store']);
    Route::get('/facility-reports', [FacilityReportController::class, 'index'])
        ->middleware('permission:facility.view_all');
    Route::get('/facility-reports/available-staff', [FacilityReportController::class, 'availableStaff'])->middleware('permission:report.assign');

    // ================= PENGADUAN BULLYING =================
    Route::post('/bullying-reports', [BullyingReportController::class, 'store']);
    Route::get('/bullying-reports', [BullyingReportController::class, 'index'])
        ->middleware('permission:bullying.handle'); // HANYA counselor, bukan admin
    Route::patch('/bullying-reports/{report}/handle', [BullyingReportController::class, 'handle'])
        ->middleware('permission:bullying.handle');

    // ================= KOMENTAR =================
    Route::get('/reports/{report}/comments', [ReportCommentController::class, 'index']);
    Route::post('/reports/{report}/comments', [ReportCommentController::class, 'store']);

    // ================= LAMPIRAN =================
    Route::post('/reports/{report}/attachments', [ReportAttachmentController::class, 'store']);
    Route::delete('/attachments/{attachment}', [ReportAttachmentController::class, 'destroy']);

    // ================= NOTIFIKASI =================
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::delete('/notifications', [NotificationController::class, 'destroyAll']);

    /*
    |--------------------------------------------------------------------------
    | ADMIN ONLY ROUTES
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/dashboard/analytics', [DashboardController::class, 'analytics']);
        Route::get('/reports', [DashboardController::class, 'allReports']);
        Route::get('/reports/export', [DashboardController::class, 'export']);

        Route::get('/users/stats', [UserManagementController::class, 'stats']);
        Route::get('/users', [UserManagementController::class, 'index']);
        Route::post('/users', [UserManagementController::class, 'store']);
        Route::get('/users/{user}', [UserManagementController::class, 'show']);
        Route::patch('/users/{user}', [UserManagementController::class, 'update']);
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy']);
        Route::patch('/users/{user}/toggle-active', [UserManagementController::class, 'toggleActive']);
        Route::post('/users/{user}/assign-role', [UserManagementController::class, 'assignRole']);
        Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword']);

        Route::get('/dashboard/bullying-metadata', [DashboardController::class, 'bullyingMetadata']);
        Route::get('/aspirations', [DashboardController::class, 'aspirations']);
        Route::get('/assignable-users', [DashboardController::class, 'assignableUsers']);

        Route::apiResource('classes', SchoolClassController::class)->except(['show']);

        Route::get('/students/export', [UserManagementController::class, 'exportStudents']);
        Route::get('/students/import-template', [UserManagementController::class, 'downloadStudentTemplate']);
        Route::post('/students/import', [UserManagementController::class, 'importStudents']);
        Route::get('/classes/academic-years', [SchoolClassController::class, 'academicYears']);

        Route::get('/staff/export', [UserManagementController::class, 'exportStaff']);

        Route::patch('/settings/general', [SettingController::class, 'updateGeneral']);
        Route::patch('/settings/appearance', [SettingController::class, 'updateAppearance']);
        Route::patch('/settings/seo', [SettingController::class, 'updateSeo']);
    });

    /*
    |--------------------------------------------------------------------------
    | STAFF ONLY ROUTES
    |--------------------------------------------------------------------------
    */
    Route::prefix('staff')->middleware('permission:facility.manage')->group(function () {
        Route::get('/facility-queue', [FacilityReportController::class, 'queue']);
        Route::get('/facility-stats', [FacilityReportController::class, 'stats']);
    });

    Route::get('/facility-reports', [FacilityReportController::class, 'index'])->middleware('permission:facility.view_all');
    Route::post('/facility-reports', [FacilityReportController::class, 'store']);
    /*
    |--------------------------------------------------------------------------
    | COUNSELOR (BK) ONLY ROUTES
    |--------------------------------------------------------------------------
    */
    Route::prefix('counselor')->middleware('permission:bullying.handle')->group(function () {
        Route::get('/bullying-queue', [BullyingReportController::class, 'queue']);
        Route::get('/bullying-stats', [BullyingReportController::class, 'stats']);
        Route::get('/bullying-reports/{report}', [BullyingReportController::class, 'show']);
        Route::patch('/bullying-reports/{report}/handle', [BullyingReportController::class, 'handle']);
        Route::post('/bullying-reports/{report}/reveal-identity', [BullyingReportController::class, 'revealIdentity']);
    });

    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::patch('/profile/password', [ProfileController::class, 'changePassword']);
});
