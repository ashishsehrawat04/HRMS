<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Leave;
use App\Models\LeaveAllowance;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Storage;

class CronController extends Controller
{
    public function daily_attendance(Request $request)
    {
        $status = null;
        $todayDay = Carbon::now()->format('l');
        $todayDate = Carbon::now()->toDateString();
        $check_holiday = DB::table('holiday')->where('date', $todayDate)->exists();
        if ($todayDay == 'Saturday' || $todayDay == 'Sunday') {
            $status = 'Week Off';
        }
        if($check_holiday){
            $status = 'Holiday';
        }
        
      $all_employee = User::where('priority_levels', '!=', 'P1')
        ->whereNull('deleted_at')
        ->where('status', '!=', 'Inactive')
        ->pluck('id');
       
        $currentDate = Carbon::now()->format('Y-m-d');
        $currentMonth = Carbon::now()->format('F');
        $currentYear = Carbon::now()->format('Y');
    
        foreach ($all_employee as $id) {
            $check_attendance = DB::table('attendance')->where('date', $currentDate)->where('employee_id', $id)->first();
            
            if (!$check_attendance) {
                DB::table('attendance')->insert([
                    'employee_id' => $id,
                    'date' => $currentDate,
                    'day' => Carbon::now()->format('l'),
                    'month' => $currentMonth,
                    'status' => $status,
                    'year' => $currentYear
                ]);
            }
    
            $check_leave = DB::table('leaves')
                ->where('employee_id', $id)
                ->whereDate('leave_start_date', '<=', $currentDate)
                ->whereDate('leave_end_date', '>=', $currentDate)
                ->first();
            
            if ($check_leave) {
                $dedection = "two_days";
                $leave_Status = $check_leave->leave_status;
                
                if ($leave_Status == 'Approved') {
                    $leave_Status = 'Leave';
                    $dedection = "one_day";
                }
    
                DB::table('attendance')->where('date', $currentDate)->where('employee_id', $id)->update([
                    'status' => 'Leave',
                    'type' => '',
                    'dedection' => $dedection,
                ]);
            }
        }
    }
    
    
    // public function daily_attendance(Request $request)
    // {
    //     $status = null;
    //     // $todayDay = Carbon::now()->format('l');
    //     $todayDate = '2024-11-03';
    //     $todayDay = Carbon::createFromFormat('Y-m-d', $todayDate)->format('l');
    //     $check_holiday = DB::table('holiday')->where('date', $todayDate)->exists();
    //     if ($todayDay == 'Saturday' || $todayDay == 'Sunday') {
    //         $status = 'Week Off';
    //     }
    //     if($check_holiday){
    //       $status = 'Holiday';
    //     }
         
    //   $all_employee = User::where('priority_levels', '!=', 'P1')
    //     ->whereNull('deleted_at')
    //     ->where('status', '!=', 'Inactive')
    //     ->pluck('id');
       
    //      $currentDate = '2024-11-03';
    //     $currentMonth = Carbon::now()->format('F');
    //     $currentYear = Carbon::now()->format('Y');
    
    //     foreach ($all_employee as $id) {
    //         $check_attendance = DB::table('attendance')->where('date', $currentDate)->where('employee_id', $id)->first();
            
    //         if (!$check_attendance) {
    //             DB::table('attendance')->insert([
    //                 'employee_id' => $id,
    //                 'date' => $currentDate,
    //                 'day' => $todayDay,
    //                 'month' => $currentMonth,
    //                 'status' => $status,
    //                 'year' => $currentYear
    //             ]);
    //         }
    
    //         $check_leave = DB::table('leaves')
    //             ->where('employee_id', $id)
    //             ->whereDate('leave_start_date', '<=', $currentDate)
    //             ->whereDate('leave_end_date', '>=', $currentDate)
    //             ->first();
            
    //         if ($check_leave) {
    //             $dedection = "two_days";
    //             $leave_Status = $check_leave->leave_status;
                
    //             if ($leave_Status == 'Approved') {
    //                 $leave_Status = 'Leave';
    //                 $dedection = "one_day";
    //             }
    
    //             DB::table('attendance')->where('date', $currentDate)->where('employee_id', $id)->update([
    //                 'status' => 'Leave',
    //                 'type' => '',
    //                 'dedection' => $dedection,
    //             ]);
    //         }
    //     }
    // }
    
    public function check_leave_status(Request $request)
    {
        $all_employee = User::where('type', 'Employee')->pluck('id'); 
       
        $currentDate = Carbon::now()->format('Y-m-d');
        $currentMonth = Carbon::now()->format('F');
        $currentYear = Carbon::now()->format('Y');
    
        foreach ($all_employee as $id) {
            $check_attendance = DB::table('attendance')->where('date', $currentDate)->where('employee_id', $id)->first();
    
            if (!$check_attendance) {
                DB::table('attendance')->insert([
                    'employee_id' => $id,
                    'date' => $currentDate,
                    'day' => Carbon::now()->format('l'),
                    'month' => $currentMonth,
                    'year' => $currentYear
                ]);
            }
    
            $check_leave = DB::table('leaves')
                ->where('employee_id', $id)
                ->whereDate('leave_start_date', '<=', $currentDate)
                ->whereDate('leave_end_date', '>=', $currentDate)
                ->first();
    
            if ($check_leave) {
                $dedection = "two_days";
                $leave_Status = $check_leave->leave_status;
                
                if ($leave_Status == 'Approved') {
                    $leave_Status = 'Leave';
                    $dedection = "one_day";
                }
    
                DB::table('attendance')->where('date', $currentDate)->where('employee_id', $id)->update([
                    'status' => 'Leave',
                    'type' => '',
                    'dedection' => $dedection,
                ]);
            }
        }
    }

//     public function addleave_monthly(Request $request)
//     {
       
//     $currentDate = Carbon::now();
//     $previousMonth = $currentDate->subMonth();
//     $previousMonthStart = $previousMonth->startOfMonth()->format('Y-m-d H:i:s');
//     $previousMonthEnd = $previousMonth->endOfMonth()->format('Y-m-d H:i:s');
//     $all_employee = User::where('priority_levels','!=','P1')->pluck('id');

//     foreach ($all_employee as $id) {
//         $prevMonthLeaves = DB::table('leave_allowances')
//             ->where('employee_id', $id) 
//             ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
//             ->first();
        
//             $check_status =   DB::table('users')->where('id',$id)->first();   
        
//             $financialYearStart = Carbon::now()->year . '-04-01'; 
//             $financialYearEnd = Carbon::create(Carbon::now()->year + 1, 3, 31);
            
//             if ($financialYearEnd->isLeapYear()) {
//                 $financialYearEnd = Carbon::create($financialYearEnd->year, 2, 29); 
//             }
                
//             $currentDate = Carbon::now();
//             $monthsLeft = $currentDate->diffInMonths($financialYearEnd);
    
      
//             $total_leave = (int)$monthsLeft;
          
//         if ($prevMonthLeaves) {
            
//             $collectedLeaves = $prevMonthLeaves->leave_collected;
//             $currentLeaves = $collectedLeaves + 1;
              
//             if($check_status->status == 'Prohibition'){
//                     $collectedLeaves = 0;
//                     // $total_leave = max(0, (int)$monthsLeft - 3);
//                     $currentLeaves = 0;
//                     $total_leave = 0;
//             }
            
//             DB::table('leave_allowances')->insert([
//                 'employee_id' => $id,
//                 'leave_collected' => $currentLeaves,
//                 'total_leave_entitled' => $total_leave,  
//                 'financial_year_start' => $financialYearStart,
//                 'financial_year_end' => $financialYearEnd,
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ]);
//         }
//     }
//     return response()->json(['message' => 'Leave balance updated successfully.']);
//   }

    public function addleave_monthly(Request $request)
    {
        $currentDateTime = Carbon::now();
        $firstOfMonth = $currentDateTime->copy()->startOfMonth();
        $timeWindowStart = $firstOfMonth->copy()->setTime(8, 35); 
        $timeWindowEnd = $firstOfMonth->copy()->setTime(8, 50);
    
        if ($currentDateTime->between($timeWindowStart, $timeWindowEnd)) {
            $all_employee = User::where('priority_levels', '!=', 'P1')->where('status','!=','Prohibition')>pluck('id');
    
            foreach ($all_employee as $id) {
                DB::table('leave_allowances')
                    ->where('employee_id', $id)
                    ->increment('leave_collected', 1);
            }
    
            return response()->json(['message' => 'Leave allowances updated successfully.']);
        } else {
            
            return response()->json(['message' => 'Leave allowances can only be updated on the first day of the month between 8:35 AM and 8:50 AM.']);
        }
    }
   
//     public function check_prohibition_end_date(Request $request)
//     {
//         $currentDate = now();
//         $allEmployees = User::where('type', 'Employee')
//             ->where('priority_levels', '!=', 'P1')
//             ->pluck('id');
    
//         foreach ($allEmployees as $empId) {
//             $prohibitionEmployee = User::where('status', 'Prohibition')
//                 ->where('id', 123)
//                 ->whereNotNull('prohibition_end_date')
//                 ->first();
    
//             if ($prohibitionEmployee) {
//                 if (strtotime($prohibitionEmployee->prohibition_end_date) <= time()) {
//                     $prohibitionEmployee->update([
//                         'status' => 'Full time',
//                         'prohibition_end_date' => null,
//                     ]);
//                 }
//             }
//             $internEmployee = User::where('status', 'Intern')
//                 ->where('id', 123)
//                 ->first();
    
//             if ($internEmployee) {
         
//                 if (!empty($internEmployee->intern_end_date) && strtotime($internEmployee->intern_end_date) <= time()) {
//                     $internEmployee->update([
//                         'status' => 'Full time',
//                         'doj' => $currentDate,
//                     ]);
//                 }
//             }
//         }
//   }
   
    public function check_absent_status()
    {
        $current_time = date("H:i:s"); 
        $today_Date = date('Y-m-d');
        $check_status = DB::table('attendance')
            ->where('date',$today_Date)
            ->where('login_time_status',0)
        
            // ->where('status','Leave')
            ->pluck('employee_id');
        
        foreach($check_status as $emp_ids){
            if($current_time >'14:30:00'){
                DB::table('attendance')->where('employee_id',$emp_ids)->update([
                    'status'=>'Half-Day'
                    ]);
            }
            if($current_time >'15:15:00'){
                DB::table('attendance')->where('employee_id',$emp_ids)->update([
                    'status'=>'Leave'
                    ]);
            }
        }
           
     }
     
    public function logoutall()
    {
         $all_employee = User::where('priority_levels', '!=', 'P1')
            ->where('status', '!=', 'Inactive')
            ->pluck('id');
            
            dd($all_employee);
            // foreach($all_employee as $ids)
            
    }
}
