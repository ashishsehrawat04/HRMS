<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthenticationController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\CronController;
use App\Http\Controllers\Api\EmployeeController;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group(['namespace' => 'Api', 'middleware' => 'auth:api'], function () {
    
    Route::middleware(['checkUserType:admin'])->group(function () {
         Route::get('/get_users', [AdminController::class, 'get_users']);
         Route::get('/get_admin', [AdminController::class, 'get_admin']);
         Route::get('/daily_attendance', [CronController::class, 'daily_attendance']);
         Route::post('add_employee', [AuthenticationController::class, 'add_employee']);
         Route::post('/delete_employee', [AdminController::class, 'delete_employee']);
         Route::get('/get_leave_requests', [AdminController::class, 'get_leave_requests']);
         Route::post('/add_leave', [AdminController::class, 'add_leave']);
         Route::post('/update_employee', [AdminController::class, 'update_employee']); 
         Route::get('/get_user_leave_data', [AdminController::class, 'get_user_leave_data']);
         Route::post('/update_add_leave', [AdminController::class, 'update_add_leave']);
         Route::get('/get_attndance', [AdminController::class, 'get_attndance']);
         Route::get('/get_user_attndance', [AdminController::class, 'get_user_attndance']);
         Route::post('/update_attndance', [AdminController::class, 'update_attndance']);
         Route::post('/add_reporting_managers', [AdminController::class, 'add_reporting_managers']);
         Route::get('/get_reporting_managers', [AdminController::class, 'get_reporting_managers']);
         Route::post('/add_departments', [AdminController::class, 'add_departments']);
         Route::post('/delete_departments', [AdminController::class, 'delete_departments']);
         Route::post('/delete_reporting_managers', [AdminController::class, 'delete_reporting_managers']);
         Route::post('/update_password', [AdminController::class, 'update_password']);
         Route::post('/add_holidays', [AdminController::class, 'add_holidays']);
         Route::get('/get_approved_salary', [AdminController::class, 'get_approved_salary']);
         Route::get('/get_assets', [AdminController::class, 'get_assets']);
         Route::post('/delete_assets', [AdminController::class, 'delete_assets']);
         Route::post('/update_assets', [AdminController::class, 'update_assets']);
         Route::post('/add_assets', [AdminController::class, 'add_assets']);
         Route::get('/get_admin_history', [AdminController::class, 'get_admin_history']);
         Route::get('/get_past_emp', [AdminController::class, 'get_past_emp']);
         Route::get('/reminderall', [AdminController::class, 'reminderall']);
         Route::post('/salary_submit', [AdminController::class, 'salary_submit']);
         Route::get('/get_all_salary', [AdminController::class, 'get_all_salary']);
         Route::get('/get_monthly_attendance', [AdminController::class, 'get_monthly_attendance']);
         Route::post('/delete_stock', [AdminController::class, 'delete_stock']);
         Route::post('/add_stock', [AdminController::class, 'add_stock']);
         Route::get('salary_calculation', [AdminController::class, 'salary_calculation']); 
         Route::get('/get_stock', [AdminController::class, 'get_stock']);
         Route::post('/update_stock', [AdminController::class, 'update_stock']);
         Route::get('/get_monthly_attendance', [AdminController::class, 'get_monthly_attendance']);
         Route::get('getall_users', [AdminController::class, 'getall_users']); 
         Route::post('/assign_assets', [AdminController::class, 'assign_assets']);
         Route::get('/get_assign_assets', [AdminController::class, 'get_assign_assets']);
         
    });

     Route::get('/get_holidays', [AdminController::class, 'get_holidays']);
     Route::get('/get_departments', [AdminController::class, 'get_departments']);
     Route::get('/get_employee_profile', [EmployeeController::class, 'get_employee_profile']); 
     Route::get('/get_employee_leave', [EmployeeController::class, 'get_employee_leave']);
     Route::get('/get_employee_attendance', [EmployeeController::class, 'get_employee_attendance']);
     Route::post('/update_employee_image', [EmployeeController::class, 'update_employee_image']);
     Route::get('/get_employee_assets', [EmployeeController::class, 'get_employee_assets']);
     Route::post('/update_assets', [AdminController::class, 'update_assets']);
     Route::get('/get_employee_salary', [EmployeeController::class, 'get_employee_salary']); 
     Route::get('/get_emp_assign_assets', [EmployeeController::class, 'get_emp_assign_assets']);
     
     
});
     Route::get('/get_employee', [AdminController::class, 'get_employee']);
     
     Route::post('login', [AuthenticationController::class, 'login']);
     Route::post('/logout', [AdminController::class, 'logout']);
     Route::get('/daily_attendance', [CronController::class, 'daily_attendance']);  
     Route::get('/check_prohibition_end_date', [CronController::class, 'check_prohibition_end_date']);  
     Route::get('/check_leave_status', [CronController::class, 'check_leave_status']);
     Route::get('/check_absent_status', [CronController::class, 'check_absent_status']);
     Route::get('/addleave_monthly', [CronController::class, 'addleave_monthly']);  
//  Route::get('/send_notification', [AdminController::class, 'send_notification']);  
     Route::post('/login_attendance', [AdminController::class, 'login_attendance']);  
     Route::post('/logout_attendance', [AdminController::class, 'logout_attendance']);
    
     Route::post('/update_assign_assets', [AdminController::class, 'update_assign_assets']);
     
     Route::post('/add_Customer', [AdminController::class, 'add_Customer']);
     Route::post('/delete_Customer', [AdminController::class, 'delete_Customer']);
     Route::post('/update_Customer', [AdminController::class, 'update_Customer']);
     Route::get('/get_Customer', [AdminController::class, 'get_Customer']);
     
     Route::post('/create_invoice', [AdminController::class, 'create_invoice']);
     Route::get('/getall_invoice', [AdminController::class, 'getall_invoice']);
     Route::post('/delete_invoice', [AdminController::class, 'delete_invoice']);
     Route::post('/update_invoice', [AdminController::class, 'update_invoice']);
     
     
    
         
     Route::get('/get_stock', [AdminController::class, 'get_stock']);
     Route::get('/get_assign_assets', [AdminController::class, 'get_assign_assets']);
    Route::get('/get_employee_attendance', [EmployeeController::class, 'get_employee_attendance']);
 
 
 