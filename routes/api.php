<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PreUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordController;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PaperSizeController;
use App\Http\Controllers\PrintModeController;
use App\Http\Controllers\PrintPriceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderController2;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UPIController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\LaminationController;

Route::get('/v1/user', function (Request $request) {
    return $request->user;
})->middleware(JwtMiddleware::class);

Route::post('/v1/pre-users', [PreUserController::class, 'store']);
Route::post('/v1/verify-users', [PreUserController::class, 'verifyOtp']);
Route::post('/v1/resend-otp', [PreUserController::class, 'resendOtp']);
Route::post('/v1/login', [AuthController::class, 'login']);
Route::post('/v1/forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp']);
Route::post('/v1/forgot-password/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
Route::post('/v1/forgot-password/reset', [ForgotPasswordController::class, 'resetPassword']);
Route::middleware(JwtMiddleware::class)->post('/v1/change-password', [PasswordController::class, 'change']);

Route::get('/v1/print-modes', [PrintModeController::class, 'index']);
Route::get('/v1/paper-sizes', [PaperSizeController::class, 'index']);
Route::get('/v1/print-prices', [PrintPriceController::class, 'index']);
// Route::get('/v1/print-orientations', [PrintOrientationController::class, 'index']);
Route::middleware(JwtMiddleware::class)->post('/v1/orders', [OrderController::class, 'store']);
Route::middleware(JwtMiddleware::class)->get('/v1/orders', [OrderController::class, 'index']);
Route::middleware(JwtMiddleware::class)->get('/v1/orders/{id}', [OrderController::class, 'show']);
Route::middleware(JwtMiddleware::class)->get('/v1/orders/{orderId}/documents/{filename}', [OrderController::class, 'getDocument']);
Route::middleware(JwtMiddleware::class)->put('/v1/orders/{id}', [OrderController::class, 'update']);
Route::middleware(JwtMiddleware::class)->delete('/v1/orders/{id}', [OrderController::class, 'destroy']);
Route::middleware(JwtMiddleware::class)->post('/v1/orders/{orderId}/payments', [PaymentController::class, 'addPayment']);
Route::middleware(JwtMiddleware::class)->get('/v1/orders/{orderId}/payments', [PaymentController::class, 'getPaymentDetails']);
Route::middleware(JwtMiddleware::class)->get('/v1/orders/{orderId}/payments/{paymentId}/screenshot', [PaymentController::class, 'getPaymentScreenshot']);
Route::middleware(JwtMiddleware::class)->patch('/v1/user/profile', [UserProfileController::class, 'update']);

Route::post('/v1/admin/login', [AdminController::class, 'login']);
Route::middleware(JwtMiddleware::class)->post('/v1/admin/change-password', [PasswordController::class, 'adminChange']);
Route::middleware(JwtMiddleware::class)->get('/v1/admin/me', [AdminController::class, 'me']);

Route::middleware(JwtMiddleware::class)->get('/v1/admin/orders', [AdminOrderController::class, 'index']);
Route::middleware(JwtMiddleware::class)->get('/v1/admin/orders/{id}', [AdminOrderController::class, 'show']);
Route::middleware(JwtMiddleware::class)->delete('/v1/admin/orders/{id}', [AdminOrderController::class, 'destroy']);
Route::middleware(JwtMiddleware::class)->get('/v1/admin/orders/{orderId}/documents/{filename}', [OrderController::class, 'adminGetDocument']);
Route::middleware(JwtMiddleware::class)->patch('/v1/admin/orders/{id}', [AdminOrderController::class, 'update']);
Route::middleware(JwtMiddleware::class)->post('/v1/admin/orders/{orderId}/payments', [AdminOrderController::class, 'addPayment']);
Route::middleware(JwtMiddleware::class)->get('/v1/admin/orders/{orderId}/payments/{paymentId}/screenshot', [AdminOrderController::class, 'getPaymentScreenshot']);
Route::middleware(JwtMiddleware::class)->patch('/v1/admin/orders/{orderId}/payments/{paymentId}', [AdminOrderController::class, 'updatePayment']);
Route::middleware(JwtMiddleware::class)->delete('/v1/admin/orders/{orderId}/payments/{paymentId}', [AdminOrderController::class, 'deletePayment']);
Route::middleware(JwtMiddleware::class)->patch('/v1/admin/orders/{id}/payment-status', [AdminOrderController::class, 'updatePaymentStatus']);
Route::middleware(JwtMiddleware::class)->patch('/v1/admin/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);
Route::middleware(JwtMiddleware::class)->patch('/v1/admin/orders/{id}/additional-info', [AdminOrderController::class, 'updateAdditionalInfo']);

Route::middleware(JwtMiddleware::class)->post('/v1/admin/print-modes', [PrintModeController::class, 'store']);
Route::middleware(JwtMiddleware::class)->patch('/v1/admin/print-modes/{id}', [PrintModeController::class, 'update']);
Route::middleware(JwtMiddleware::class)->post('/v1/admin/paper-sizes', [PaperSizeController::class, 'store']);
Route::middleware(JwtMiddleware::class)->patch('/v1/admin/paper-sizes/{id}', [PaperSizeController::class, 'update']);

Route::middleware(JwtMiddleware::class)->get('/v1/admin/print-prices', [PrintPriceController::class, 'index']);
Route::middleware(JwtMiddleware::class)->post('/v1/admin/print-prices', [PrintPriceController::class, 'store']);
Route::middleware(JwtMiddleware::class)->delete('/v1/admin/print-prices/{id}', [PrintPriceController::class, 'destroy']);

Route::middleware(JwtMiddleware::class)->post('/v1/admin/upi/upload', [UPIController::class, 'uploadAdminImage']);
Route::get('/v1/admin/upi/image', [UPIController::class, 'getAdminImage']);
Route::middleware(JwtMiddleware::class)->delete('/v1/admin/upi/image', [UPIController::class, 'deleteAdminImage']);


Route::middleware(JwtMiddleware::class)->get('/v1/admin/system-memory', [SuperAdminController::class, 'getSystemMemory']);
Route::middleware(JwtMiddleware::class)->get('/v1/admin/disk-space', [SuperAdminController::class, 'getDiskSpace']);
Route::middleware(JwtMiddleware::class)->get('/v1/admin/mysql-usage', [SuperAdminController::class, 'getMysqlUsage']);
Route::middleware(JwtMiddleware::class)->get('/v1/admin/cpu-load', [SuperAdminController::class, 'getCpuLoad']);

Route::middleware(JwtMiddleware::class)->get('/v1/admin/settings/upi-id', [SettingController::class, 'getUpiId']);
Route::middleware(JwtMiddleware::class)->post('/v1/admin/settings/upi-id', [SettingController::class, 'updateUpiId']);
Route::middleware(JwtMiddleware::class)->delete('/v1/admin/settings/upi-id', [SettingController::class, 'deleteUpiId']);

Route::middleware(JwtMiddleware::class)->get('/v1/settings/lamination-amount', [LaminationController::class, 'getLaminationAmount']);
Route::middleware(JwtMiddleware::class)->post('/v1/admin/settings/lamination-amount', [LaminationController::class, 'updateLaminationAmount']);
Route::middleware(JwtMiddleware::class)->delete('/v1/admin/settings/lamination-amount', [LaminationController::class, 'deleteLaminationAmount']);

Route::middleware(JwtMiddleware::class)->delete('/v2/order/copy/{id}', [OrderController2::class, 'deleteCopy']);
Route::middleware(JwtMiddleware::class)->post('/v2/order/copy/{id}', [OrderController2::class, 'addCopy']);
Route::middleware(JwtMiddleware::class)->patch('/v2/order/copy/{id}', [OrderController2::class, 'updateCopy']);
Route::middleware(JwtMiddleware::class)->post('/v2/order/document/{id}', [OrderController2::class, 'addDocument']);
Route::middleware(JwtMiddleware::class)->patch('/v2/order/document/{id}', [OrderController2::class, 'updateDocument']);
Route::middleware(JwtMiddleware::class)->delete('/v2/order/document/{id}', [OrderController2::class, 'deleteDocument']);

Route::middleware(JwtMiddleware::class)->get('/v1/notifications', [NotificationController::class, 'index']);
Route::middleware(JwtMiddleware::class)->patch('/v1/notifications/{id}', [NotificationController::class, 'update']);
Route::middleware(JwtMiddleware::class)->get('/v1/notifications/unread-count', [NotificationController::class, 'countUnread']);
