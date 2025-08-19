<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\AssignAssets;
use App\Models\Stock;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Asset;
use App\Models\Leave;
use Illuminate\Validation\Rule;
use App\Models\LeaveAllowance;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use DateTime;
use Google\Auth\Credentials\ServiceAccountCredentials;

class AdminController extends Controller
{
    public function get_users(Request $request)
    {
         
      $allEmployees = DB::table('users')
        ->where('priority_levels', '!=', 'P1')
        ->where('status', '!=', 'Inactive')
        ->whereNull('deleted_at')
        ->select('id', 'name', 'status', 'employee_code', 'email', 'dob', 'doj', 'department', 'password', 'mobile','total_experience', 'reporting_manager', 'blood_group', 'location', 'biometrics', 'designation', 'notice_period_end_date', 'experience', 'image', 'type', 'prohibition_end_date','intern_end_date','intern_start_date')
        ->orderBy('name', 'asc')
        ->get()
        ->map(function ($employee) {
            try {
                $employee->password = Crypt::decrypt($employee->password);
            } catch (DecryptException $e) {
                $employee->password = 'Decryption failed';
            }
            return $employee;
        });
    
        // $allEmployees = User::withoutTrashed()
        //     ->where('priority_levels', '!=', 'P1')
        //     ->select('id', 'name', 'status', 'employee_code', 'email', 'dob', 'doj', 'department', 'password', 'mobile', 'total_experience', 'reporting_manager', 'blood_group', 'location', 'biometrics', 'designation', 'notice_period_end_date', 'experience', 'image', 'type', 'prohibition_end_date')
        //     ->get()
        //     ->map(function ($employee) {
        //         try {
        //             $employee->password = Crypt::decrypt($employee->password);
        //         } catch (DecryptException $e) {
        //             $employee->password = 'Decryption failed';
        //         }
        //         return $employee;
        //     });
            
            return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' => true,
                'data' => $allEmployees
            ]);
    }
    
    public function getall_users(Request $request)
    {
         
      $allEmployees = DB::table('users')
        ->whereNull('deleted_at')
        ->where('status', '!=', 'Inactive')
        ->select('id', 'name', 'email')
        ->get();
        
            return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' => true,
                'data' => $allEmployees
            ]);
    }
    
    public function update_employee(Request $request)
    {
        $id = $request->input('id');
        $validatedData = Validator::make($request->all(), [
            'id' => 'required|integer',
            'employee_code' => [
                'required',
                'unique:users,employee_code,' . $id, 
            ],
            'email' => [
                'required',
                'email',
                'ends_with:cvinfotech.com',
                'unique:users,email,' . $id,
            ],
            'prohibition_end_date' => [
                'date',
                'date_format:Y-m-d',
                'after_or_equal:today', 
            ],
            
            'mobile' => [
                'required',
                'digits:10',
            ],
            
            'type' => [
                'required',
                'in:Admin,Employee',
            ],
            
             'image' => [
                 'image',
                 'mimes:jpeg,png,jpg,gif,svg',
                 'max:10048'
            ], 
            
        ]);
        
        if ($validatedData->fails()) {
            $errorMessages = $validatedData->errors()->all();
            return response()->json(['authenticated' =>true,'valid' => false, 'mssg' => $errorMessages]);
        }
    
        $employeeCode = $request->input('employee_code');
        $email = $request->input('email');
        $prohibition_end_date = $request->input('prohibition_end_date');
    
         $current_date = Carbon::parse($prohibition_end_date);
         
        if ($current_date->month > 3) {
            $financial_year_end = Carbon::create($current_date->year + 1, 3, 31);
        } else {
            $financial_year_end = Carbon::create($current_date->year, 3, 31);
        }
        $months_left = $current_date->diffInMonths($financial_year_end);
        

        if($employeeCode)
        $employee = User::find($id);
    
        if (!$employee) {
            return response()->json(['authenticated' => true, 'valid' => false, 'mssg' => 'Employee not found.']);
        }
    
        $employeeCode = $request->input('employee_code');
        $email = $request->input('email');
        $status = $request->input('status');
        
        $prohibition_end_date = $request->input('prohibition_end_date');
        
    
        $current_date = Carbon::parse($prohibition_end_date);
        
        if ($current_date->month > 3) {
            $financial_year_end = Carbon::create($current_date->year + 1, 3, 31);
        } else {
            $financial_year_end = Carbon::create($current_date->year, 3, 31);
        }
        $months_left = $current_date->diffInMonths($financial_year_end);
    
        // // Check for unique constraints (employee_code and email)
        // $employeeCodeExists = User::where('employee_code', $employeeCode)->where('id', '!=', $id)->exists();
        // $emailExists = User::where('email', $email)->where('id', '!=', $id)->exists();
    

        $fields = [
            'designation','total_experience','type', 'notice_period_end_date', 'password', 'department',
            'doj', 'dob', 'email', 'employee_code', 'name',
            'location', 'blood_group', 'reporting_manager', 'prohibition_end_date',
            'mobile', 'experience', 'image','status','emergency_number','intern_end_date'
        ];
    
        
        if ($request->hasFile('image')) {
            // $request->validate([
            //     'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048'
            // ]);
    
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('images', $imageName, 'public');
            $employee->image = $imageName; 
        
        }

        foreach ($fields as $field) {
            if ($field == 'image') {
                continue;
            }
            $updated_keys=[];
            if ($request->filled($field)) {
                $updated_keys[] = $field;
                
                  if ($field == 'status') {
                       
                      if($status !=='Prohibition'){
                      $employee->prohibition_end_date =  null;
                      }
                     
                  }
                   
                if ($field == 'password') {
                    $employee->password =  Crypt::encrypt($request->input($field));
                } else {
                    $employee->{$field} = $request->input($field);
                }
                
                if ('experience' == 'Fresher') {
                    $employee->total_experience =  0;
                }
                if ('type' == 'Admin') {
                         $employee->priority_levels ='P2';
                    }else{
                         $employee->priority_levels ='P3';
                    }
             
            }
           
            // dd($updted_key);
        }
        // dd($updated_keys);
    
        if ($request->input('status') == 'Prohibition') {
            LeaveAllowance::where('employee_id', $id)
                // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                ->update(['total_leave_entitled' => $months_left]);
        }
    
        if (in_array($request->input('status'), ['Full time', 'Intern', 'Inactive', 'Prohibition'])) {
            $employee->notice_period_end_date = null;
        }
        $prev_data = User::find($id);
        $employee->save();
        $type ='update_employee';
        
        $this->storedata($prev_data,$id,$type);
    
        return response()->json([
            'authenticated' => true,
            'valid' => true,
            'success' => true,
            'mssg' => 'Employee updated successfully.',
            'data' => $employee
            ]);
     }
     
    public function delete_employee(Request $request)
    {
        $id= $request->input('id');
        $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);
        
        if ($validator->fails()) {
 
                $errorMessages = $validator->errors()->all();
                return response()->json(['authenticated' =>true,'valid' => false, 'mssg' => $errorMessages]);
            }

       $employee = User::find($id);
    
        if (!$employee) {
            return response()->json([
                'mssg' => 'Employee not found.'
            ]);
        }
         $employee->delete();
    
          $type ="delete_employee";
          $admin_data = Auth::user();
          $changesJson = json_encode($employee);
            DB::table('admin_history')->insert([
                'employee_id' => $id,
                'updated_by' => $admin_data->name,
                'data_type' => $type,
                'data' => $changesJson,
                'updated_at' => now(),
            ]);

        return response()->json([
            'authenticated' =>true,
            'valid' => true,
            'success' => true,
            'mssg' => 'Employee deleted successfully.'
        ]);
    } 
    
    public function get_leave_requests(Request $request)
    {
          $allEmployees = DB::table('leaves')
            ->join('users', 'leaves.employee_id', '=', 'users.id')
            ->whereNull('users.deleted_at')
            ->select('leaves.*', 'users.name', 'users.email')
            ->get();

        foreach ($allEmployees as $data) {
            $leaveStartDate = new DateTime($data->leave_start_date);
            $leaveEndDate = new DateTime($data->leave_end_date);
        
            if ($leaveStartDate->format('H:i:s') === '00:00:00') {
                $data->leave_start_date = $leaveStartDate->format('Y-m-d');
                $data->leave_end_date = $leaveEndDate->format('Y-m-d');
            } else {
                $data->leave_start_date = $leaveStartDate->format('Y-m-d H:i:s');
                $data->leave_end_date = $leaveEndDate->format('Y-m-d H:i:s');
            }
        }
        return response()->json([
            'authenticated' => true,
            'valid' => true,
            'success' => true,
            'data' => $allEmployees
        ]);
    }
    
    public function get_user_leave_data(Request $request)
    {
         $id= $request->input('id');
        
         $validator = Validator::make($request->all(), [
                'id'  =>'required',
            ]);
        
         if ($validator->fails()) {
 
                $errorMessages = $validator->errors()->all();
                return response()->json([ 'authenticated' =>true,'valid' => false, 'mssg' => $errorMessages]);
            }
    
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
       $employee_leave = DB::table('leave_allowances')
        ->join('leaves', 'leave_allowances.employee_id', '=', 'leaves.employee_id')
        // ->whereBetween('leaves.created_at', [$startOfMonth, $endOfMonth])
        // ->whereBetween('leave_allowances.created_at', [$startOfMonth, $endOfMonth])
        ->where('leave_allowances.employee_id',$id)
        ->orderBy('leaves.leave_start_date', 'desc')
        ->get();
            
       $total_salary_dec_days = DB::table('leaves')
        ->where('employee_id', $id)
        ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
        ->sum('salary_deduction_days');
        
             
       $leave_data = DB::table('leave_allowances')
         ->where('employee_id',$id)
         ->first(); 
        
        if ($leave_data) {
            $leave_data->total_salary_deduction_days = $total_salary_dec_days;
        } 
            
       if ($leave_data === null) {
            $leave_data = [];
        }
        $allEmployees =$employee_leave;
        foreach ($allEmployees as $data) {
            $leaveStartDate = new DateTime($data->leave_start_date);
            $leaveEndDate = new DateTime($data->leave_end_date);
        
            if ($leaveStartDate->format('H:i:s') === '00:00:00') {
                $data->leave_start_date = $leaveStartDate->format('Y-m-d');
                $data->leave_end_date = $leaveEndDate->format('Y-m-d');
            } else {
                $data->leave_start_date = $leaveStartDate->format('Y-m-d H:i:s');
                $data->leave_end_date = $leaveEndDate->format('Y-m-d H:i:s');
            }
        }
         
        if ($employee_leave) {
            return response()->json([
                'authenticated' =>true,
                'valid' => true,
                'success' => true,
                'leave_data' =>$leave_data,
                'data' => $employee_leave
            ]);
        } else {
            return response()->json([
                'authenticated' =>true,
                'valid' => true,
                'success' => false,
                'mssg' => 'No user found'
            ]);
        }
    }
    
    public function add_leave(Request $request) 
    {
        
        $employee_id= $request->input('id');
        $leave_type= $request->input('leave_type');
        // $leave_name = $request->input('leave_name');
        $leave_start_date= $request->input('leave_start_date');
        $leave_end_date= $request->input('leave_end_date');
        // $total_days= $request->input('total_days');
        $leave_status= $request->input('leave_status');
        $reasone = $request->input('reasone');
        $leave_code = $request->input('leave_code');
        $leave_period = $request->input('leave_period');
        $id = $employee_id;
        
        
        
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'leave_type' => 'required|string|max:255',
            // 'leave_name' => 'required|string|max:255',
            'leave_start_date' => 'required|date',
            'leave_end_date' => 'required|date|after_or_equal:leave_start_date',
            // 'total_days' => 'required|integer|min:1',
            'leave_status' => 'required',  
            'reason' => 'nullable|string|max:1000', 
            'leave_code' => 'required|string|max:50',
            'leave_period' => 'required|string|max:255',
            'reasone'=> 'required|string|',
        ]);
         if ($validator->fails()) {
 
                $errorMessages = $validator->errors()->all();
                return response()->json([ 'authenticated' =>true,'valid' => false, 'mssg' => $errorMessages]);
            }
        
           
        $check_data = LeaveAllowance::where('employee_id',$employee_id)
                    //   ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                      ->first();
                      
         $emp_data = User::where('id',$employee_id)->first();
                      
        if(!$check_data){
            $currentYear = Carbon::now()->year;
            $financialYearStart = Carbon::create($currentYear, 3, 1);
            $financialYearEnd = Carbon::create($currentYear + 1, 2, 28);
            
            if ($financialYearEnd->isLeapYear()) {
                 $financialYearEnd = Carbon::create($currentYear + 1, 2, 29);
            }
            $currentDate = Carbon::now();
            $monthsLeft = $currentDate->diffInMonths($financialYearEnd);
              
              $emp_data = User::where('id',$employee_id)->first();
               $collected_leave =1;
               $total_leave = (int)$monthsLeft;
               if($emp_data->status=="Prohibition"){
                  $collected_leave =0;
                  $total_leave = (int)$monthsLeft-3;
                  $total_leave = abs($total_leave);
              }
                $new_data = new LeaveAllowance();
                $new_data->employee_id = $employee_id;
                $new_data->total_leave_entitled = $total_leave;
                $new_data->leave_collected = $collected_leave;
                $new_data->financial_year_start = $financialYearStart;
                $new_data->financial_year_end = $financialYearEnd;
                $new_data->save();
        }           
        $leave_left = $check_data->leave_collected;
    
            $startDate = Carbon::parse($leave_start_date);
            $endDate = Carbon::parse($leave_end_date);
            $now = Carbon::now();
            $leave = new Leave();
            
            if($leave_type =='Paid')
            {
                
                if ($leave_period == "Half-Day") {
                    if ($startDate->isSameDay($endDate)) {
                        $total_days = 0.5; 
                        
                    if($leave_type =='Paid' && $leave_left < $total_days ){
                          return response()->json([
                                // 'da'=>$leave,
                                // 'days' => $total_days,
                                'authenticated' =>true,
                                'valid' => true,
                                'success' => false,
                                'mssg' => "Oops! You can't apply for {$total_days} paid leaves. Your current leave balance is just {$leave_left}.",
    
                            ]);
                      }
                        $leave->salary_deduction_days = $total_days;
                    } else {
                        $total_days = $startDate->diffInDays($endDate) + 0.5;
                        
                         if($leave_type =='Paid' && $leave_left < $total_days ){
                          return response()->json([
                                // 'da'=>$leave,
                                // 'days' => $total_days,
                                'authenticated' =>true,
                                'valid' => true,
                                'success' => false,
                                  'mssg' => "Oops! You can't apply for {$total_days} paid leaves. Your current leave balance is just {$leave_left}.",
                            ]);
                      }
                        $leave->salary_deduction_days = $total_days;
                    }
                
                    $current_date = Carbon::now(); 
                    $leave_start_date_formatted = (new DateTime($leave_start_date))->format('Y-m-d');
                    if ($leave_start_date_formatted == $current_date->format('Y-m-d')) {
                        DB::table('attendance')
                            ->where('employee_id', $employee_id)
                            ->where('date', $current_date->format('Y-m-d'))
                            ->update([
                                'day' => $current_date->format('l'),
                                'status' => 'Half-Day',
                                'leave_type' => null,
                            ]);
                    }
                    
                } else {
                    $total_days = $startDate->diffInDays($endDate) + 1;
                    $leave->salary_deduction_days = $total_days;
                }
                
        
                // if ($startDate->isSameDay($endDate) && $startDate->hour !== $endDate->hour) {
                    
                //     $total_days = 1; // Treat as a full day
                //     $leave->salary_deduction_days = $total_days;
                // }
                
                // $total_days = max(0, $total_days); 
           
                $dayName = $now->dayName; 
                $leave_start_date= $request->input('leave_start_date');
                // $monthName = $now->monthName; 
                $monthName = Carbon::parse($leave_start_date)->format('F');
                
                $year = $now->year;
                $formattedDate = $now->format('Y-m-d');
        
                
                $leave = new Leave();
                // $leave->leave_name = $leave_name;
                $leave->employee_id = $employee_id;
                $leave->leave_type = $leave_type;
                $leave->leave_start_date = $leave_start_date;
                $leave->leave_end_date = $leave_end_date;
                $leave->total_days = $total_days;
                $leave->leave_status= $leave_status;
                $leave->leave_period = $leave_period;
                $leave->leave_code = $leave_code;
                $leave->reason = $reasone;
                $leave->current_date = $formattedDate;
                $leave->year = $year;
                $leave->month = $monthName;
                $leave->day = $dayName;
                
                if ($leave_status == "Unapproved") {
                    $salary_deduction_days = $total_days*2;
                    $leave->salary_deduction_days = $salary_deduction_days;
                     $startDate = Carbon::parse($leave_start_date);
                     $endDate = Carbon::parse($leave_end_date);
                     
                     
                      if($leave_type =='Paid' && $leave_left < $total_days ){
                              return response()->json([
                                    // 'da'=>$leave,
                                    // 'days' => $total_days,
                                    'authenticated' =>true,
                                    'valid' => true,
                                    'success' => false,
                                   'mssg' => "Oops! You can't apply for {$total_days} paid leaves. Your current leave balance is just {$leave_left}.",
        
                                ]);
                          }
                        
                        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                           
                            if($leave_start_date ==$date->format('Y-m-d')){
                              DB::table('attendance')->where('employee_id',$employee_id)->where('date',$date->format('Y-m-d'))->update([
                                'day' => $date->format('l'),          
                                'login_time' => null,                 
                                'logout_time' => null,                
                                'status' => 'Leave',                  
                                'type' => 'Unapproved_leave',           
                                'leave_type' => null,
                                'dedection'=>'two_days'
                                ]);
                            }
                        }
                        
                    DB::table('leave_allowances')
                        ->where('employee_id', $employee_id)
                        // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                        ->increment('unpaid_leave_taken', $total_days);
                } else if ($leave_status == "Approved") {
                
                        $startDate = Carbon::parse($leave_start_date);
                        $endDate = Carbon::parse($leave_end_date);
                        
                         if($leave_type =='Paid' && $leave_left < $total_days ){
                              return response()->json([
                                    // 'da'=>$leave,
                                    // 'days' => $total_days,
                                    'authenticated' =>true,
                                    'valid' => true,
                                    'success' =>false,
                                    'mssg' => "Oops! You can't apply for {$total_days} paid leaves. Your current leave balance is just {$leave_left}.",
        
                                ]);
                          }
                        
                        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                            
                             if($leave_start_date ==$date->format('Y-m-d')){
                                 
                                 DB::table('attendance')->where('employee_id',$employee_id)->where('date',$date->format('Y-m-d'))->update([
                                'day' => $date->format('l'),          
                                'login_time' => null,                 
                                'logout_time' => null,                
                                'status' => 'Leave',                  
                                'type' => 'Approved_leave',           
                                'leave_type' => null,
                                'dedection'=>'one_days'
                                ]);
                             }
                        }
                    
                    
                    $user_status = User::find($employee_id);
                    $leaveAllowance = LeaveAllowance::where('employee_id', $employee_id)->first();
            
                if ($user_status->status == "Prohibition") {
                   
                    DB::table('leave_allowances')
                        ->where('employee_id', $employee_id)
                        // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                        ->increment('unpaid_leave_taken', $total_days);
                         $leave->salary_deduction_days = $total_days*2;
                        
                } else {
                    $total_leave_collected = $leaveAllowance->leave_collected;
                    
                    if ($total_leave_collected >= $total_days) {
                     
                        DB::table('leave_allowances')
                            ->where('employee_id', $employee_id)
                            // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                            ->decrement('leave_collected', $total_days);
                        DB::table('leave_allowances')
                            ->where('employee_id', $employee_id)
                            // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                            ->increment('paid_leave_taken', $total_days);
                            $leave->salary_deduction_days = 0;
                    } else {
                        $unpaid_leave = $total_days - $total_leave_collected;
                        $leave->salary_deduction_days = $unpaid_leave;
                      
                        DB::table('leave_allowances')
                            ->where('employee_id', $employee_id)
                            // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                            ->update(['leave_collected' => 0]);
                            
                        DB::table('leave_allowances')
                            ->where('employee_id', $employee_id)
                            // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                            ->increment('unpaid_leave_taken', $unpaid_leave);
                            
                        DB::table('leave_allowances')
                            ->where('employee_id', $employee_id)
                            // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                            ->increment('paid_leave_taken', $total_leave_collected);
                    }
                }
            }
        }else{
                
                if ($leave_period == "Half-Day") {
                    if ($startDate->isSameDay($endDate)) {
                        $total_days = 0.5; 
                        
                    if($leave_type =='Paid' && $leave_left < $total_days ){
                          return response()->json([
                                // 'da'=>$leave,
                                // 'days' => $total_days,
                                'authenticated' =>true,
                                'valid' => true,
                                'success' => false,
                                'mssg' => "Oops! You can't apply for {$total_days} paid leaves. Your current leave balance is just {$leave_left}.",
    
                            ]);
                      }
                        $leave->salary_deduction_days = $total_days;
                    } else {
                        $total_days = $startDate->diffInDays($endDate) + 0.5;
                        
                         if($leave_type =='Paid' && $leave_left < $total_days ){
                          return response()->json([
                                // 'da'=>$leave,
                                // 'days' => $total_days,
                                'authenticated' =>true,
                                'valid' => true,
                                'success' => false,
                                  'mssg' => "Oops! You can't apply for {$total_days} paid leaves. Your current leave balance is just {$leave_left}.",
                            ]);
                      }
                        $leave->salary_deduction_days = $total_days;
                    }
                
                    $current_date = Carbon::now(); 
                    $leave_start_date_formatted = (new DateTime($leave_start_date))->format('Y-m-d');
                    if ($leave_start_date_formatted == $current_date->format('Y-m-d')) {
                        DB::table('attendance')
                            ->where('employee_id', $employee_id)
                            ->where('date', $current_date->format('Y-m-d'))
                            ->update([
                                'day' => $current_date->format('l'),
                                'status' => 'Half-Day',
                                'leave_type' => null,
                            ]);
                    }
                    
                } else {
                    $total_days = $startDate->diffInDays($endDate) + 1;
                    $leave->salary_deduction_days = $total_days;
                }
                $user_status = User::find($employee_id);
                 if ($user_status->status == "Prohibition") {
                   
                    // DB::table('leave_allowances')
                    //     ->where('employee_id', $employee_id)
                    //     // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                    //     ->increment('unpaid_leave_taken', $total_days);
                    
                    $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
                    $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

                    $check_thismonth = Leave::whereBetween('leave_start_date', [$startOfMonth, $endOfMonth])
                        ->where('employee_id', $employee_id)
                        ->first();
                        if(!$check_thismonth){
                              $leavesdata= DB::table('leave_allowances')
                                 ->where('employee_id', $employee_id)->first();
                                 
                                    if($leavesdata->leave_collected>1){
                                        DB::table('leave_allowances')
                                            ->where('employee_id', $employee_id)
                                            ->decrement('leave_collected', 1);
                                    }else{
                                        DB::table('leave_allowances')
                                            ->where('employee_id', $employee_id)
                                            ->update(['leave_collected' => 0]);
                                    }
                        }
                   
                         $leave->salary_deduction_days = $total_days*2;
                        
                }
                
        
                // if ($startDate->isSameDay($endDate) && $startDate->hour !== $endDate->hour) {
                    
                //     $total_days = 1; // Treat as a full day
                //     $leave->salary_deduction_days = $total_days;
                // }
                
                // $total_days = max(0, $total_days); 
           
                $dayName = $now->dayName; 
                $monthName = $now->monthName;
                $year = $now->year;
                $formattedDate = $now->format('Y-m-d');
        
                
                $leave = new Leave();
                // $leave->leave_name = $leave_name;
                $leave->employee_id = $employee_id;
                $leave->leave_type = $leave_type;
                $leave->leave_start_date = $leave_start_date;
                $leave->leave_end_date = $leave_end_date;
                $leave->total_days = $total_days;
                $leave->leave_status= $leave_status;
                $leave->leave_period = $leave_period;
                $leave->leave_code = $leave_code;
                $leave->reason = $reasone;
                $leave->current_date = $formattedDate;
                $leave->year = $year;
                $leave->month = $monthName;
                $leave->day = $dayName;
                
                if ($leave_status == "Unapproved") {
                    $salary_deduction_days = $total_days*2;
                    $leave->salary_deduction_days = $salary_deduction_days;
                     $startDate = Carbon::parse($leave_start_date);
                     $endDate = Carbon::parse($leave_end_date);
                     
                     
                      if($leave_type =='Paid' && $leave_left < $total_days ){
                              return response()->json([
                                    // 'da'=>$leave,
                                    // 'days' => $total_days,
                                    'authenticated' =>true,
                                    'valid' => true,
                                    'success' => false,
                                   'mssg' => "Oops! You can't apply for {$total_days} paid leaves. Your current leave balance is just {$leave_left}.",
        
                                ]);
                          }
                        
                        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                           
                            if($leave_start_date ==$date->format('Y-m-d')){
                              DB::table('attendance')->where('employee_id',$employee_id)->where('date',$date->format('Y-m-d'))->update([
                                'day' => $date->format('l'),          
                                'login_time' => null,                 
                                'logout_time' => null,                
                                'status' => 'Leave',                  
                                'type' => 'Unapproved_leave',           
                                'leave_type' => null,
                                'dedection'=>'two_days'
                                ]);
                            }
                        }
                        
                    DB::table('leave_allowances')
                        ->where('employee_id', $employee_id)
                        // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                        ->increment('unpaid_leave_taken', $total_days);
                } else if ($leave_status == "Approved") {
                
                        $startDate = Carbon::parse($leave_start_date);
                        $endDate = Carbon::parse($leave_end_date);
                        
                         if($leave_type =='Paid' && $leave_left < $total_days ){
                              return response()->json([
                                    // 'da'=>$leave,
                                    // 'days' => $total_days,
                                    'authenticated' =>true,
                                    'valid' => true,
                                    'success' =>false,
                                    'mssg' => "Oops! You can't apply for {$total_days} paid leaves. Your current leave balance is just {$leave_left}.",
        
                                ]);
                          }
                        
                        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                            
                             if($leave_start_date ==$date->format('Y-m-d')){
                                 
                                 DB::table('attendance')->where('employee_id',$employee_id)->where('date',$date->format('Y-m-d'))->update([
                                'day' => $date->format('l'),          
                                'login_time' => null,                 
                                'logout_time' => null,                
                                'status' => 'Leave',                  
                                'type' => 'Approved_leave',           
                                'leave_type' => null,
                                'dedection'=>'one_days'
                                ]);
                             }
                        }
                    
                    
                    $user_status = User::find($employee_id);
                    $leaveAllowance = LeaveAllowance::where('employee_id', $employee_id)->first();
            
                if ($user_status->status == "Prohibition") {
                   
                    DB::table('leave_allowances')
                        ->where('employee_id', $employee_id)
                        // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                        ->increment('unpaid_leave_taken', $total_days);
                         $leave->salary_deduction_days = $total_days*2;
                        
                } else {
                    
                     DB::table('leave_allowances')
                        ->where('employee_id', $employee_id)
                        // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                        ->increment('unpaid_leave_taken', $total_days);
                        
                         $leave->salary_deduction_days = $total_days;
                        
                    // $total_leave_collected = $leaveAllowance->leave_collected;
                    
                    //   DB::table('leave_allowances')
                    //         ->where('employee_id', $employee_id)
                    //         // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                    //         ->increment('unpaid_leave_taken', $total_days);
                    
                    // if ($total_leave_collected >= $total_days) {
                     
                    //     DB::table('leave_allowances')
                    //         ->where('employee_id', $employee_id)
                    //         // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                    //         ->decrement('leave_collected', $total_days);
                    //     DB::table('leave_allowances')
                    //         ->where('employee_id', $employee_id)
                    //         // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                    //         ->increment('paid_leave_taken', $total_days);
                    //         $leave->salary_deduction_days = 0;
                    // } else {
                    //     $unpaid_leave = $total_days - $total_leave_collected;
                    //     $leave->salary_deduction_days = $unpaid_leave;
                      
                    //     DB::table('leave_allowances')
                    //         ->where('employee_id', $employee_id)
                    //         // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                    //         ->update(['leave_collected' => 0]);
                            
                    //     DB::table('leave_allowances')
                    //         ->where('employee_id', $employee_id)
                    //         // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                    //         ->increment('unpaid_leave_taken', $unpaid_leave);
                            
                    //     DB::table('leave_allowances')
                    //         ->where('employee_id', $employee_id)
                    //         // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                    //         ->increment('paid_leave_taken', $total_leave_collected);
                    // }
                }
            }
        }
        // $user_status = User::find($employee_id);
        //  if ($user_status->status == "Prohibition") {
       
        //          $leave->salary_deduction_days = $total_days*2;
                
        // }
        
    
        $leave->save();
        $type="add_leave";
        
        $this->add_data($leave,$employee_id,$type);
        // $this->sendLeaveNotification(
        //     $emp_data->email,            // Employee Email
        //     $emp_data->name,              // Employee Name
        //     $emp_data->id,              // Employee ID
        //     $leave_type,                // Leave Type
        //     $leave_start_date,           // Start Date
        //     $leave_end_date,            // End Date
        //     $total_days,                // Total Days
        //     $reasone                     // Reason (optional)
        // );
    
        return response()->json([
            // 'da'=>$leave,
            // 'days' => $total_days,
            'authenticated' =>true,
            'valid' => true,
            'success' => true,
            'mssg' => 'Leave record saved successfully.'
        ]);
    }

    public function update_add_leave(Request $request)
    {
        $auto_generated_id = $request->input('auto_generated_id');
        $login_token = $request->input('login_token');
        $employee_id = $request->input('employee_id');
        $leave_type = $request->input('leave_type');
        // $leave_name = $request->input('leave_name');
        $leave_start_date = $request->input('leave_start_date');
        $leave_end_date = $request->input('leave_end_date');
        $total_days = $request->input('total_days');
        $leave_status = $request->input('leave_status');
        $reasone = $request->input('reasone');
        $leave_code = $request->input('leave_code');
        $leave_period = $request->input('leave_period');
        
         
        //$notice_period_end_date = $request->input('notice_period_end_date'); 
        
         $validator = Validator::make($request->all(), [
            'auto_generated_id' => 'required|integer',
            'leave_type' => 'required|string|max:255',
            // 'leave_name' => 'required|string|max:255',
            'leave_start_date' => 'required|date',
            'leave_end_date' => 'required|date|after_or_equal:leave_start_date', 
            // 'total_days' => 'required|integer|min:1',  // Uncomment this if needed
            'leave_status' => 'required|string', 
            'reason' => 'nullable|string|max:1000',  
            'leave_code' => 'required|string|max:50',
            'leave_period' => 'required|string|max:255',
            'employee_id' =>'required|integer',
        ]);
        
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            return response()->json([
                'authenticated' => true,
                'valid' => false,
                'mssg' => $errorMessages
            ]);
        }
       
          $leave_record = Leave::find($auto_generated_id);
        if (!$leave_record) {
            return response()->json(['mssg' => 'Leave record not found']);
        }
    
        $prev_total_days = $leave_record->total_days;
       
        $employee_leave = DB::table('leave_allowances')
            ->where('employee_id', $employee_id)
            // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->first();
    
        if (!$employee_leave) {
            return response()->json(['mssg' => 'Leave allowance record not found']);
        }
        
     
       if($leave_record->leave_type =='Paid'){
   
            $startDate = Carbon::parse($leave_start_date);
            $endDate = Carbon::parse($leave_end_date);
            $days = $startDate->diffInDays($endDate) + 1;
    
            // if(($employee_leave->leave_collected+$leave_record->paid_leave_taken)<$days){
            //       return response()->json([
            //             'authenticated' =>true,
            //             'valid' => true,
            //             'success' =>false,
            //             'mssg' => "Oops! You can't apply for {$days} paid leaves. Your current leave balance is just " . ($employee_leave->leave_collected + $employee_leave->paid_leave_taken) . ".",
            //         ]);
            // }
        
             if ($prev_total_days <= $employee_leave->paid_leave_taken) {
                DB::table('leave_allowances')
                    ->where('employee_id', $employee_id)
                    ->decrement('paid_leave_taken', $prev_total_days);
                    
                // DB::table('leave_allowances')
                //     ->where('employee_id', $employee_id)
                //     ->decrement('paid_leave_taken', $prev_total_days);
                    
                DB::table('leave_allowances')
                    ->where('employee_id', $employee_id)
                    ->increment('leave_collected', $prev_total_days);
            } else {
             
                $remaining_days = $prev_total_days - $employee_leave->paid_leave_taken;
                
                DB::table('leave_allowances')
                    ->where('employee_id', $employee_id)
                    // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                    ->update(['paid_leave_taken' => 0]);
        
                if ($remaining_days <= $employee_leave->paid_leave_taken) {
                    DB::table('leave_allowances')
                        ->where('employee_id', $employee_id)
                        // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                        ->decrement('paid_leave_taken', $remaining_days);
                        
                    DB::table('leave_allowances')
                        ->where('employee_id', $employee_id)
                        // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                        ->increment('leave_collected', $remaining_days);
                } else {
                    $paid_leave_deficit = $remaining_days - $employee_leave->paid_leave_taken;
        
                    DB::table('leave_allowances')
                        ->where('employee_id', $employee_id)
                        // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                        ->update(['paid_leave_taken' => 0]);
                        
                        // DB::table('leave_allowances')
                        // ->where('employee_id', $employee_id)
                        // // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                        // ->decrement('unpaid_leave_taken', $paid_leave_deficit);
        
                    if ($paid_leave_deficit > 0) {
                        DB::table('leave_allowances')
                            ->where('employee_id', $employee_id)
                            // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                            ->increment('leave_collected', $paid_leave_deficit);
                    }
                }
            }
            
        }else{
            
            if ($prev_total_days <= $employee_leave->unpaid_leave_taken) {
                DB::table('leave_allowances')
                    ->where('employee_id', $employee_id)
                    ->decrement('unpaid_leave_taken', $prev_total_days);
            } else {
             
                $remaining_days = $prev_total_days - $employee_leave->unpaid_leave_taken;
                
                DB::table('leave_allowances')
                    ->where('employee_id', $employee_id)
                    // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                    ->update(['unpaid_leave_taken' => 0]);
        
                if ($remaining_days <= $employee_leave->paid_leave_taken) {
                    DB::table('leave_allowances')
                        ->where('employee_id', $employee_id)
                        // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                        ->decrement('paid_leave_taken', $remaining_days);
                        
                    DB::table('leave_allowances')
                        ->where('employee_id', $employee_id)
                        // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                        ->increment('leave_collected', $remaining_days);
                } else {
                    $paid_leave_deficit = $remaining_days - $employee_leave->paid_leave_taken;
        
                    DB::table('leave_allowances')
                        ->where('employee_id', $employee_id)
                        // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                        ->update(['paid_leave_taken' => 0]);
        
                    if ($paid_leave_deficit > 0) {
                        DB::table('leave_allowances')
                            ->where('employee_id', $employee_id)
                            // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                            ->increment('leave_collected', $paid_leave_deficit);
                    }
                }
            }
        }
   
    
         $check_data = LeaveAllowance::where('employee_id',$employee_id)
                    //   ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                      ->first();
                      
                  
         $leave_left = $check_data->leave_collected;
                      
        if(!$check_data){
            $currentYear = Carbon::now()->year;
            $financialYearStart = Carbon::create($currentYear, 3, 1);
            $financialYearEnd = Carbon::create($currentYear + 1, 2, 28);
            
            if ($financialYearEnd->isLeapYear()) {
                 $financialYearEnd = Carbon::create($currentYear + 1, 2, 29);
            }
            $currentDate = Carbon::now();
            $monthsLeft = $currentDate->diffInMonths($financialYearEnd);
              
              $emp_data = User::where('id',$employee_id)->first();
               $collected_leave =1;
               $total_leave = (int)$monthsLeft;
               if($emp_data->status=="Prohibition"){
                  $collected_leave =0;
                  $total_leave = (int)$monthsLeft-3;
                  $total_leave = abs($total_leave);
              }
                $new_data = new LeaveAllowance();
                $new_data->employee_id = $employee_id;
                $new_data->total_leave_entitled = $total_leave;
                $new_data->leave_collected = $collected_leave;
                $new_data->financial_year_start = $financialYearStart;
                $new_data->financial_year_end = $financialYearEnd;
                $new_data->save();
        }              

        if($leave_type =='Paid')
        {
            $startDate = Carbon::parse($leave_start_date);
            $endDate = Carbon::parse($leave_end_date);
            $now = Carbon::now();
            $leave = new Leave();
            
            $currentDate = Carbon::now(); 
            DB::table('attendance')->where('employee_id', $employee_id)
                ->whereDate('date', $currentDate->format('Y-m-d'))
                ->update([
                    'day' => $currentDate->format('l'),          
                    'status' => 'Present',                  
                    'leave_type' => null,
                ]);
            
            if ($leave_period == "Half-Day") {
            
                if ($startDate->isSameDay($endDate)) {
                
                    //  if($leave_type =='Paid' && $leave_left < $total_days ){
                        //   return response()->json([
                        //         // 'da'=>$leave,
                        //         // 'days' => $total_days,
                        //         'authenticated' =>true,
                        //         'valid' => true,
                        //         'success' => false,
                        //         'mssg' => "Oops! You can't apply for {$total_days} paid leaves. Your current leave balance is just {$leave_left}.",
    
                        //     ]);
                    //   }
                    // dd($total_days);
                    $leave->salary_deduction_days = $total_days;
                } else {
                 
                    $total_days = $startDate->diffInDays($endDate) + 0.5;
                    //  if($leave_type =='Paid' && $leave_left < $total_days ){
                    //       return response()->json([
                    //             // 'da'=>$leave,
                    //             // 'days' => $total_days,
                    //             'authenticated' =>true,
                    //             'valid' => true,
                    //             'success' => false,
                    //             'mssg' => "Oops! You can't apply for {$total_days} paid leaves. Your current leave balance is just {$leave_left}.",
    
                    //         ]);
                    //   }
                    $leave->salary_deduction_days = $total_days;
                }
                
                $current_date = Carbon::now();
                if($leave_start_date == $current_date->format('Y-m-d')){
                    DB::table('attendance')->where('employee_id',$employee_id)->where('date',$date->format('Y-m-d'))->update([
                    'day' => $date->format('l'),          
                    'status' => 'Half-Day',                  
                    'leave_type' => null,
                    'dedection'=>'two_days'
                    ]);
                }
                
                $current_date = Carbon::now(); 
                $leave_start_date_formatted = (new DateTime($leave_start_date))->format('Y-m-d');
                if ($leave_start_date_formatted == $current_date->format('Y-m-d')) {
                    DB::table('attendance')
                        ->where('employee_id', $employee_id)
                        ->where('date', $current_date->format('Y-m-d'))
                        ->update([
                            'day' => $current_date->format('l'),
                            'status' => 'Half-Day',
                            'leave_type' => null,
                        ]);
                }
                
            } else {
                $total_days = $startDate->diffInDays($endDate) + 1;
                $leave->salary_deduction_days = $total_days;
            }
    
            // if ($startDate->isSameDay($endDate) && $startDate->hour !== $endDate->hour) {
                
            //     $total_days = 1; // Treat as a full day
            //     $leave->salary_deduction_days = $total_days;
            // }
            
            // $total_days = max(0, $total_days); 
           
            $dayName = $now->dayName; 
            // $monthName = $now->monthName;
            $monthName = Carbon::parse($leave_start_date)->format('F');
            $year = $now->year;
            $formattedDate = $now->format('Y-m-d');
            $leave = Leave::find($auto_generated_id);
    
            if (!$leave) {
                return response()->json([
                    'mssg' => 'Leave record not found'
                ]);
            }
            
            // $leave->leave_name = $leave_name;
            $leave->employee_id = $employee_id;
            $leave->leave_type = $leave_type;
            $leave->leave_start_date = $leave_start_date;
            $leave->leave_end_date = $leave_end_date;
            $leave->total_days = $total_days;
            $leave->leave_status= $leave_status;
            $leave->leave_period = $leave_period;
            $leave->leave_code = $leave_code;
            $leave->reason = $reasone;
            $leave->current_date = $formattedDate;
            $leave->year = $year;
            $leave->month = $monthName;
            $leave->day = $dayName;
       
            
        if ($leave_status == "Unapproved") {
            
            // $total_days *= 2;
            //  if($leave_type =='Paid' && $leave_left < $total_days ){
            //       return response()->json([
            //             // 'da'=>$leave,
            //             // 'days' => $total_days,
            //             'authenticated' =>true,
            //             'valid' => true,
            //             'success' => false,
            //             'mssg' => "Oops! You can't apply for {$total_days} paid leaves. Your current leave balance is just {$leave_left}.",
    
            //         ]);
            //      }
            $salary_deduction_days = $total_days*2;
            $leave->salary_deduction_days = $salary_deduction_days;
             $startDate = Carbon::parse($leave_start_date);
             $endDate = Carbon::parse($leave_end_date);
            //  $saturdays = 0;
            //  $sundays = 0;
                
                for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                   
                    if($leave_start_date ==$date->format('Y-m-d')){
                      DB::table('attendance')->where('employee_id',$employee_id)->where('date',$date->format('Y-m-d'))->update([
                        'day' => $date->format('l'),          
                        'login_time' => null,                 
                        'logout_time' => null,                
                        'status' => 'Leave',                  
                        'type' => 'Unapproved_leave',           
                        'leave_type' => null,
                        'dedection'=>'two_days'
                        ]);
                    }
                }
            
                 
            DB::table('leave_allowances')
                ->where('employee_id', $employee_id)
                // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                ->increment('unpaid_leave_taken', $total_days);
        } else if ($leave_status == "Approved") {
         
            //  if($leave_type =='Paid' && $leave_left < $total_days ){
            //           return response()->json([
            //                 // 'da'=>$leave,
            //                 // 'days' => $total_days,
            //                 'authenticated' =>true,
            //                 'valid' => true,
            //                 'success' => false,
            //                 'mssg' => "Oops! You can't apply for {$total_days} paid leaves. Your current leave balance is just {$leave_left}.",
        
            //             ]);
            //           }
            
                $startDate = Carbon::parse($leave_start_date);
                $endDate = Carbon::parse($leave_end_date);
                
               for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                   
                    if($leave_start_date ==$date->format('Y-m-d')){
                      DB::table('attendance')->where('employee_id',$employee_id)->where('date',$date->format('Y-m-d'))->update([
                        'day' => $date->format('l'),          
                        'login_time' => null,                 
                        'logout_time' => null,                
                        'status' => 'Leave',                  
                        'type' => 'Approved_leave',           
                        'leave_type' => null,
                        'dedection'=>'two_days'
                        ]);
                    }
                }
        
            
            $user_status = User::find($employee_id);
            $leaveAllowance = LeaveAllowance::where('employee_id', $employee_id)->first();
    
            if ($user_status->status == "Prohibition") {
               
                DB::table('leave_allowances')
                    ->where('employee_id', $employee_id)
                    // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                    ->increment('unpaid_leave_taken', $total_days);
                     $leave->salary_deduction_days = $total_days*2;
                    
            } else {
                $total_leave_collected = $leaveAllowance->leave_collected;
                
                if ($total_leave_collected >= $total_days) {
                 
                    DB::table('leave_allowances')
                        ->where('employee_id', $employee_id)
                        // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                        ->decrement('leave_collected', $total_days);
                    DB::table('leave_allowances')
                        ->where('employee_id', $employee_id)
                        // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                        ->increment('paid_leave_taken', $total_days);
                        $leave->salary_deduction_days = 0;
                } else {
                    $unpaid_leave = $total_days - $total_leave_collected;
                    
                    $leave->salary_deduction_days = $unpaid_leave;
                  
                    DB::table('leave_allowances')
                        ->where('employee_id', $employee_id)
                        // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                        ->update(['leave_collected' => 0]);
                        
                    DB::table('leave_allowances')
                        ->where('employee_id', $employee_id)
                        // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                        ->increment('unpaid_leave_taken', $unpaid_leave);
                        
                    DB::table('leave_allowances')
                        ->where('employee_id', $employee_id)
                        // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                        ->increment('paid_leave_taken', $total_leave_collected);
                }
            }
        }}
        else
        {
                $startDate = Carbon::parse($leave_start_date);
                $endDate = Carbon::parse($leave_end_date);
                $now = Carbon::now();
                $leave = new Leave();
                
                $currentDate = Carbon::now(); 
                DB::table('attendance')->where('employee_id', $employee_id)
                    ->whereDate('date', $currentDate->format('Y-m-d'))
                    ->update([
                        'day' => $currentDate->format('l'),          
                        'status' => 'Present',                  
                        'leave_type' => null,
                    ]);
                
                if ($leave_period == "Half-Day") {
                
                    if ($startDate->isSameDay($endDate)) {
                        $total_days = 0.5; 
                        //  if($leave_type =='Paid' && $leave_left < $total_days ){
                            //   return response()->json([
                            //         // 'da'=>$leave,
                            //         // 'days' => $total_days,
                            //         'authenticated' =>true,
                            //         'valid' => true,
                            //         'success' => false,
                            //         'mssg' => "Oops! You can't apply for {$total_days} paid leaves. Your current leave balance is just {$leave_left}.",
        
                            //     ]);
                        //   }
                        $leave->salary_deduction_days = $total_days;
                    } else {
                     
                        $total_days = $startDate->diffInDays($endDate) + 0.5;
                        //  if($leave_type =='Paid' && $leave_left < $total_days ){
                        //       return response()->json([
                        //             // 'da'=>$leave,
                        //             // 'days' => $total_days,
                        //             'authenticated' =>true,
                        //             'valid' => true,
                        //             'success' => false,
                        //             'mssg' => "Oops! You can't apply for {$total_days} paid leaves. Your current leave balance is just {$leave_left}.",
        
                        //         ]);
                        //   }
                        $leave->salary_deduction_days = $total_days;
                    }
                    
                    $current_date = Carbon::now();
                    if($leave_start_date == $current_date->format('Y-m-d')){
                        DB::table('attendance')->where('employee_id',$employee_id)->where('date',$date->format('Y-m-d'))->update([
                        'day' => $date->format('l'),          
                        'status' => 'Half-Day',                  
                        'leave_type' => null,
                        'dedection'=>'two_days'
                        ]);
                    }
                    
                    $current_date = Carbon::now(); 
                    $leave_start_date_formatted = (new DateTime($leave_start_date))->format('Y-m-d');
                    if ($leave_start_date_formatted == $current_date->format('Y-m-d')) {
                        DB::table('attendance')
                            ->where('employee_id', $employee_id)
                            ->where('date', $current_date->format('Y-m-d'))
                            ->update([
                                'day' => $current_date->format('l'),
                                'status' => 'Half-Day',
                                'leave_type' => null,
                            ]);
                    }
                    
                } else {
                    $total_days = $startDate->diffInDays($endDate) + 1;
                    $leave->salary_deduction_days = $total_days;
                }
        
                // if ($startDate->isSameDay($endDate) && $startDate->hour !== $endDate->hour) {
                    
                //     $total_days = 1; // Treat as a full day
                //     $leave->salary_deduction_days = $total_days;
                // }
                
                // $total_days = max(0, $total_days); 
               
                $dayName = $now->dayName; 
                $monthName = $now->monthName;
                $year = $now->year;
                $formattedDate = $now->format('Y-m-d');
        
                
                 $leave = Leave::find($auto_generated_id);
        
                if (!$leave) {
                    return response()->json([
                        'mssg' => 'Leave record not found'
                    ]);
                }
          
                // $leave->leave_name = $leave_name;
                $leave->employee_id = $employee_id;
                $leave->leave_type = $leave_type;
                $leave->leave_start_date = $leave_start_date;
                $leave->leave_end_date = $leave_end_date;
                $leave->total_days = $total_days;
                $leave->leave_status= $leave_status;
                $leave->leave_period = $leave_period;
                $leave->leave_code = $leave_code;
                $leave->reason = $reasone;
                $leave->current_date = $formattedDate;
                $leave->year = $year;
                $leave->month = $monthName;
                $leave->day = $dayName;
           
                
            if ($leave_status == "Unapproved") {
                
                // $total_days *= 2;
                //  if($leave_type =='Paid' && $leave_left < $total_days ){
                //       return response()->json([
                //             // 'da'=>$leave,
                //             // 'days' => $total_days,
                //             'authenticated' =>true,
                //             'valid' => true,
                //             'success' => false,
                //             'mssg' => "Oops! You can't apply for {$total_days} paid leaves. Your current leave balance is just {$leave_left}.",
        
                //         ]);
                //      }
                $salary_deduction_days = $total_days*2;
                $leave->salary_deduction_days = $salary_deduction_days;
                 $startDate = Carbon::parse($leave_start_date);
                 $endDate = Carbon::parse($leave_end_date);
                //  $saturdays = 0;
                //  $sundays = 0;
                    
                    for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                       
                        if($leave_start_date ==$date->format('Y-m-d')){
                          DB::table('attendance')->where('employee_id',$employee_id)->where('date',$date->format('Y-m-d'))->update([
                            'day' => $date->format('l'),          
                            'login_time' => null,                 
                            'logout_time' => null,                
                            'status' => 'Leave',                  
                            'type' => 'Unapproved_leave',           
                            'leave_type' => null,
                            'dedection'=>'two_days'
                            ]);
                        }
                    }
                
                     
                DB::table('leave_allowances')
                    ->where('employee_id', $employee_id)
                    // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                    ->increment('unpaid_leave_taken', $total_days);
            } else if ($leave_status == "Approved") {
                //  if($leave_type =='Paid' && $leave_left < $total_days ){
                //           return response()->json([
                //                 // 'da'=>$leave,
                //                 // 'days' => $total_days,
                //                 'authenticated' =>true,
                //                 'valid' => true,
                //                 'success' => false,
                //                 'mssg' => "Oops! You can't apply for {$total_days} paid leaves. Your current leave balance is just {$leave_left}.",
            
                //             ]);
                //           }
                
                    $startDate = Carbon::parse($leave_start_date);
                    $endDate = Carbon::parse($leave_end_date);
                    
                   for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                       
                        if($leave_start_date ==$date->format('Y-m-d')){
                          DB::table('attendance')->where('employee_id',$employee_id)->where('date',$date->format('Y-m-d'))->update([
                            'day' => $date->format('l'),          
                            'login_time' => null,                 
                            'logout_time' => null,                
                            'status' => 'Leave',                  
                            'type' => 'Approved_leave',           
                            'leave_type' => null,
                            'dedection'=>'two_days'
                            ]);
                        }
                    }
            
                
                $user_status = User::find($employee_id);
                $leaveAllowance = LeaveAllowance::where('employee_id', $employee_id)->first();
        
                if ($user_status->status == "Prohibition") {
                   
                    DB::table('leave_allowances')
                        ->where('employee_id', $employee_id)
                        // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                        ->increment('unpaid_leave_taken', $total_days);
                         $leave->salary_deduction_days = $total_days*2;
                        
                } else {
                    
                     DB::table('leave_allowances')
                        ->where('employee_id', $employee_id)
                        // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                        ->increment('unpaid_leave_taken', $total_days);
                        $leave->salary_deduction_days = $total_days;
                    // $total_leave_collected = $leaveAllowance->leave_collected;
                    
                    // if ($total_leave_collected >= $total_days) {
                     
                    //     DB::table('leave_allowances')
                    //         ->where('employee_id', $employee_id)
                    //         // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                    //         ->decrement('leave_collected', $total_days);
                    //     DB::table('leave_allowances')
                    //         ->where('employee_id', $employee_id)
                    //         // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                    //         ->increment('paid_leave_taken', $total_days);
                    //         $leave->salary_deduction_days = 0;
                    // } else {
                    //     $unpaid_leave = $total_days - $total_leave_collected;
                    //     $leave->salary_deduction_days = $unpaid_leave;
                      
                    //     DB::table('leave_allowances')
                    //         ->where('employee_id', $employee_id)
                    //         // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                    //         ->update(['leave_collected' => 0]);
                            
                    //     DB::table('leave_allowances')
                    //         ->where('employee_id', $employee_id)
                    //         // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                    //         ->increment('unpaid_leave_taken', $unpaid_leave);
                            
                    //     DB::table('leave_allowances')
                    //         ->where('employee_id', $employee_id)
                    //         // ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                    //         ->increment('paid_leave_taken', $total_leave_collected);
                    // }
                }
            }
        }

    $prev_data =Leave::where('id',$auto_generated_id);
    $leave->save();
    $type ="update_leave";
    $this->update_leave_data($leave,$prev_data,$employee_id,$type,$auto_generated_id);

    return response()->json([
        // 'da'=>$leave,
        // 'days' => $total_days,
        'authenticated' =>true,
        'valid' => true,
        'success' => true,
        'mssg' => 'Leave record updated successfully.'
    ]);
    
        }
        
    public function get_attndance(Request $request)
    {
        
        $currentDate = Carbon::today(); 
        $attendance_data = DB::table('attendance')
            ->join('users', 'attendance.employee_id', '=', 'users.id')
            ->whereNull('users.deleted_at')
            // ->whereDate('attendance.date', $currentDate) // Filter by current date
            ->select('attendance.id as auto_generated_id','attendance.employee_id', 'attendance.status as attendance_status','attendance.date','attendance.day','attendance.month','attendance.year','attendance.login_time','attendance.logout_time','attendance.leave_type', 'attendance.logout_image','attendance.login_image','users.name','users.location','users.status', 'users.employee_code', 'users.email', 'users.department', 'users.mobile', 'users.reporting_manager', 'users.designation', 'users.image')
            ->orderBy('users.name', 'asc')
            ->get();
            
            $compareSales = Carbon::parse('14:10:00');
            $compareOther = Carbon::parse('09:15:13');
            
            foreach ($attendance_data as $data) {
                $check_data = User::where('id', $data->employee_id)->first();
                
                if ($check_data && $data->login_time) {
                    $loginTime = Carbon::parse($data->login_time);  // Parse login_time to Carbon instance
        
                    if ($check_data->department == 'Sales Dept') {
                        $data->attendance_status = ($loginTime < $compareSales) ? "On Time" : "Late";
                    } else {
                        $data->attendance_status = ($loginTime < $compareOther) ? "On Time" : "Late";
                    }
                }
            }

        
        return response()->json([
             'authenticated' => true,
             'valid' => true,
             'success' => true,
             'data' =>$attendance_data
            ]);
            
        }
        
    public function get_user_attndance(Request $request)
    {
        $employee_id = $request->input('employee_id');
        
         $validatedData = Validator::make($request->all(), [
                'employee_id' => 'required',
            ]);
        
          if ($validatedData->fails()) {

                $errorMessages = $validatedData->errors()->all();
                
                return response()->json([ 'authenticated' => true,'valid' => false, 'mssg' => $errorMessages]);
            }
            
        $currentDate = Carbon::today();

        $attendance_data = DB::table('attendance')
            ->join('users', 'attendance.employee_id', '=', 'users.id')
            ->where('attendance.employee_id',$employee_id)
            ->select('attendance.id as auto_generated_id','attendance.employee_id','attendance.date','attendance.day','attendance.login_image','attendance.logout_image','attendance.month','attendance.year','attendance.login_time','attendance.logout_time','attendance.leave_type','attendance.status as attendance_status','users.name','users.status', 'users.employee_code', 'users.email', 'users.department', 'users.mobile', 'users.reporting_manager', 'users.designation', 'users.image') 
            // ->whereDate('attendance.created_at', $currentDate) 
             ->orderBy('attendance.date', 'desc')
            ->get();
            
             $compareSales = Carbon::parse('14:10:00');
            $compareOther = Carbon::parse('09:15:13');
            
            foreach ($attendance_data as $data) {
                $check_data = User::where('id', $data->employee_id)->first();
                
                if ($check_data && $data->login_time) {
                    $loginTime = Carbon::parse($data->login_time); 
        
                    if ($check_data->department == 'Sales Dept') {
                        $data->attendance_status = ($loginTime < $compareSales) ? "On Time" : "Late";
                    } else {
                        $data->attendance_status = ($loginTime < $compareOther) ? "On Time" : "Late";
                    }
                }
            }
        
        return response()->json([
            'authenticated' => true,
             'valid' => true,
             'success' => true,
             'data' =>$attendance_data
            ]);
    }
    
    public function add_holidays(Request $request)
    {
        
        $holiday_name = $request->input('holiday_name');
        $day = $request->input('day');
        $date = $request->input('date');        
        $month = $request->input('month');
        $year = $request->input('year');
        
        $validatedData = Validator::make($request->all(), [
            'holiday_name' => 'required|string|max:255',
            'day' => 'required|string|max:50',
            'date' => 'required|date',
            'month' => 'required|string|max:50',
            'year'  => 'required'
        ]);
        
        if ($validatedData->fails()) {
            $errorMessages = $validatedData->errors()->all();
            return response()->json(['authenticated' => true,'valid' => false, 'mssg' => $errorMessages]);
        }

           DB::table('holiday')->insert([
                'holiday_name' =>$holiday_name,
                'day' => $day,
                'date' => $date,
                'month' => $month,
                'year' => $year,
            ]);

             $data = [
                    'holiday_name' =>$holiday_name,
                    'day' => $day,
                    'data' => $date,
                    'month' => $month,
                    'year' => $year,
                 ];
            $type ="add_holidays";
            $admin_data = Auth::user();
            $changesJson = json_encode($data);
            DB::table('admin_history')->insert([
                'updated_by' => $admin_data->name,
                'data_type' => $type,
                'data' => $changesJson,
                'updated_at' => now(),
            ]);
            

         return response()->json([
                 'authenticated' => true,
                 'valid' => true,
                 'mssg'=>"holiday added succssfully"
            ]);
            
    }
    
    public function get_holidays(Request $request)
    {
         
         $data = DB::table('holiday')
                ->orderBy('date', 'asc')
                ->get();
         
          return response()->json([
                'authenticated' => true,
                 'valid' => true,
                 'success' => true,
                 'data' =>$data
            ]);
    }
    
    public function logout(Request $request)
    {
        
        $email = $request->input('email');
        $type = $request->input('type');
        
        
         $validatedData = Validator::make($request->all(), [
                'email' => 'required|email',
                'type' => 'required|in:Admin,Employee',
            ]);

       if ($validatedData->fails()) {
 
            $errorMessages = $validatedData->errors()->all();
            
            return response()->json(['valid' => false, 'mssg' => $errorMessages]);
        }
                    
        
        if($type =='Employee' || $type =='Admin')
        {
            
          $user = User::where('email', $email)->first();
        
            if (!$user) {
                return response()->json(['mssg' => 'Email not matched']);
            }
        
            if ($user->type !== $type) {
                return response()->json(['mssg' => 'Type mismatch']);
            }
            
            $user = User::where('email', $email)->where('type', $type)->first();
        
            if (!$user) {
                return response()->json(['mssg' => 'User not found after update']);
            }
           $startOfMonth = Carbon::now()->startOfMonth();
           $endOfMonth = Carbon::now()->endOfMonth();
           $today = Carbon::now()->format('Y-m-d');   
           $check_login_time_status= DB::table('attendance')
                ->where('employee_id',$user->id)
                ->where('date', $today)
                ->first();
                
            if($check_login_time_status){
                // DB::table('attendance')
                //         ->where('date', $today)
                //         ->where('employee_id', $user->id)->update
                //         ([
                //          'logout_time' => Carbon::now()->toTimeString(),
                //         ]);
            }
                
            $data =[
                'type' => $user->type,
                'name' => $user->name,
                'email' => $user->email,
                'id' => $user->id,
                'department' => $user->department,
                ];
   
            return response()->json([
                'valid' => true,
                'success' => true,
                'mssg' => 'Logout successfully'
            ]);
          }
    }
    
    public function update_attndance(Request $request)
    {
         $auto_generated_id = $request->input('auto_generated_id');
         $status = $request->input('status');
         $login_time = $request->input('login_time');
         $logout_time = $request->input('logout_time');  
         if($login_time=='null'){
             $login_time =null;
         }
          if($logout_time=='null'){
             $logout_time =null;
         }
        
         $validatedData = Validator::make($request->all(), [
                'auto_generated_id' => 'required|integer',
                'status' => 'required|string',
                // 'login_time' => 'required|regex:/^(0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/',
                // 'logout_time' => 'nullable|regex:/^(0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/|after:login_time',
            ]);
    
          if ($validatedData->fails()) {

                $errorMessages = $validatedData->errors()->all();
                return response()->json(['authenticated' => true,'valid' => false, 'mssg' => $errorMessages]);
            }
        
          $today = Carbon::now()->format('Y-m-d');
          $check_login_time_status = DB::table('attendance')
            ->where('id', $auto_generated_id)
            // ->where('date', $today)
            ->first();
        
    
        if (!$check_login_time_status) {
            DB::table('attendance')->insert([
                // 'employee_id' => $employee_id,
                'date' => $today,
                'login_time' =>$login_time ,
                'logout_time' => $logout_time,
                'day' => Carbon::now()->format('l'),
                'status' => 'Leave',
                // 'login_time_status' => 1,
                'month' => Carbon::now()->format('F'), 
                'year' => Carbon::now()->format('Y'), 
            ]);
        } else {
            // if ($check_login_time_status->login_time_status == 0) {
                DB::table('attendance')->where('id', $auto_generated_id)->update([
                    'status' => $status,
                    'login_time' =>$login_time ,
                    'logout_time' => $logout_time,
                ]);
            // }else{
            //       return response()->json([
            //             'authenticated' => true,
            //             'valid' => true,
            //             'success' => true,
            //              'mssg' => 'You are already logged in successfully',
            //         ]);
                
            // }
        }
        
           $admin_data = Auth::user();
           $employee_id = $check_login_time_status->employee_id;
           $prev_data= $check_login_time_status;
           $type = "update_attndance";
        
            $attendance_Data = DB::table('attendance')->where('id', $auto_generated_id)->first();
            
 
    
            $changes = [];
        
        
            $dataKeys = ["status", "login_time", "logout_time"];
         
    
            foreach ($dataKeys as $key) {
    
                $newValue = $attendance_Data->{$key} ?? null;
                $oldValue = isset($prev_data->{$key}) ? $prev_data->{$key} : null;
        
                if ($oldValue !== $newValue) {
                    $changes[$key] = [
                        'previous' => $oldValue,
                        'updated' => $newValue
                    ];
                }
            }
            $changesJson = json_encode($changes);
           
                    DB::table('admin_history')->insert([
                        'employee_id' => $employee_id,
                        'updated_by' => $admin_data->name,
                        'data_type' => $type,
                        'data' => $changesJson,
                        'updated_at' => now(),
                    ]);
        
          return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' => true,
                'mssg' => 'update successfully'
            ]);

    }
    
    public function add_reporting_managers(Request $request)
    {
         $name = $request->input('name');
         $validatedData = Validator::make($request->all(), [
                'name' => 'required',
            ]);
            
          if ($validatedData->fails()) {
                $errorMessages = $validatedData->errors()->all();
                return response()->json(['authenticated' => true,'valid' => false, 'mssg' => $errorMessages]);
            }
           
          DB::table('reporting_managers')->insert([
              'name'=>$name
              ]);
              
                $type ="add_reporting_managers";
                $admin_data = Auth::user();
                $changesJson = json_encode($name);
            DB::table('admin_history')->insert([
                'updated_by' => $admin_data->name,
                'data_type' => $type,
                'data' => $changesJson,
                'updated_at' => now(),
            ]);
              
          return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' => true,
                'mssg' => 'add successfully'
            ]);
    }
    
    public function get_reporting_managers(Request $request)
    {
        $reporting_managers =  DB::table('reporting_managers')->get();
          return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' => true,
                'data' =>$reporting_managers,
            ]);
    }
    
    public function add_departments(Request $request)
    {
         $department = $request->input('department');
         $validatedData = Validator::make($request->all(), [
                'department' => 'required',
            ]);
            
          if ($validatedData->fails()) {
                $errorMessages = $validatedData->errors()->all();
                return response()->json(['authenticated' => true,'valid' => false, 'mssg' => $errorMessages]);
            }
           
          DB::table('departments')->insert([
              'department'=>$department
              ]);
              
               $type = "add_departments";
               $admin_data = Auth::user();
               $changesJson = json_encode($department);
            DB::table('admin_history')->insert([
                'updated_by' => $admin_data->name,
                'data_type' => $type,
                'data' => $changesJson,
                'updated_at' => now(),
            ]);
              
          return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' => true,
                'mssg' => 'add successfully'
            ]);
    }
    
    public function get_departments(Request $request)
    {
    
        $departments =  DB::table('departments')->get();
          return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' => true,
                'data' =>$departments,
            ]);
    }
    
    public function delete_departments(Request $request)
    {
         $auto_gen_id = $request->input('auto_gen_id');
         $validatedData = Validator::make($request->all(), [
                'auto_gen_id' => 'required',
            ]);
            
          if ($validatedData->fails()) {

                $errorMessages = $validatedData->errors()->all();
                
                return response()->json(['authenticated' => true,'valid' => false, 'mssg' => $errorMessages]);
            }
           
          $delete_data = DB::table('departments')->where('id',$auto_gen_id)->first();
          DB::table('departments')->where('id',$auto_gen_id)->delete();
          
          
          $admin_data = Auth::user();
          $type = "delete_departments";
          $changesJson = json_encode($delete_data);
            DB::table('admin_history')->insert([
                'updated_by' => $admin_data->name,
                'data_type' => $type,
                'data' => $changesJson,
                'updated_at' => now(),
            ]);
              
          return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' => true,
                'mssg' => 'delete successfully'
            ]);
    }
    
    public function delete_reporting_managers(Request $request)
    {
         $auto_gen_id = $request->input('auto_gen_id');
         $validatedData = Validator::make($request->all(), [
                'auto_gen_id' => 'required',
            ]);
            
          if ($validatedData->fails()) {

                $errorMessages = $validatedData->errors()->all();
                
                return response()->json(['authenticated' => true,'valid' => false, 'mssg' => $errorMessages]);
            }
           
          $delete_data = DB::table('reporting_managers')->where('id',$auto_gen_id)->first();
          DB::table('reporting_managers')->where('id',$auto_gen_id)->delete();
          $admin_data = Auth::user();
          $type = "delete_reporting_managers";
          $changesJson = json_encode($delete_data);
            DB::table('admin_history')->insert([
                'updated_by' => $admin_data->name,
                'data_type' => $type,
                'data' => $changesJson,
                'updated_at' => now(),
            ]);
              
          return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' => true,
                'mssg' => 'delete successfully'
            ]);
    }
    
    public function get_admin(Request $request)
    {
        
      $allAdmin = User::withoutTrashed()
        ->where('type','Admin')
        ->select('id', 'name', 'status', 'employee_code', 'email', 'dob', 'doj', 'department', 'password', 'mobile','total_experience', 'reporting_manager', 'blood_group', 'location', 'biometrics', 'designation', 'notice_period_end_date', 'experience', 'image', 'type', 'prohibition_end_date')
        ->get()
        ->map(function ($allAdmin) {
            try {
                $allAdmin->password = Crypt::decrypt($allAdmin->password);
            } catch (DecryptException $e) {
                $allAdmin->password = 'Decryption failed';
            }
            return $allAdmin;
        });
        
        return response()->json([
            'authenticated' => true,
            'valid' => true,
            'success' => true,
            'data' => $allAdmin
        ]);
    }
    
    public function update_password(Request $request)
    {
         $email = $request->input('email');
         $oldPassword =$request->input('oldPassword');
         $newPassword =$request->input('newPassword');
         $validatedData = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'oldPassword' => 'required|string',
                'newPassword' => 'required|string',
            ]);
            
          if ($validatedData->fails()) {

                $errorMessages = $validatedData->errors()->all();
                
                return response()->json(['authenticated' => true,'valid' => false, 'mssg' => $errorMessages]);
            }
            
             $admin = User::where('email', $email)->first();
 
            if (!$admin) {
                return response()->json([
                    'authenticated' => true,
                    'valid' => true,
                    'success' => false,
                    'mssg' => 'Email not matched',
                    
                    ]);
            }
            $decrypted = Crypt::decrypt($admin->password);
            
            if ($decrypted !== $oldPassword) {
                
                return response()->json([
                    'authenticated' => true,
                    'valid' => true,
                    'success' => false,
                    'mssg' => 'Invalid Old password',
                    
                    ]);
            }
            
            $hashedPassword  = Crypt::encrypt($newPassword);
            $admin->password = $hashedPassword; 
            $admin->save(); 
            $type = 'update_password';
            $admin_data = Auth::user();
            $changesJson = json_encode($newPassword);
            DB::table('admin_history')->insert([
                'employee_id' => $admin->id,
                'updated_by' => $admin_data->name,
                'data_type' => $type,
                'data' => $changesJson,
                'updated_at' => now(),
            ]);
            
            return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' => true,
                'mssg' => 'Password updated successfully'
            ]);
    }
    
    public function send_notification(Request $request)
    {
        echo "hloo";
    }

    public function login_attendance(Request $request)
    {
        $emp_code = $request->input('emp_code');
        $image = $request->input('image');
        $email = $request->input('email');
        $validatedData = Validator::make($request->all(), [
            'emp_code' => 'required|digits:3',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'email' => 'required|email|exists:users,email',
        ]);
        
        if ($validatedData->fails()) {
            $errorMessages = $validatedData->errors()->all();
            return response()->json(['authenticated' => true, 'valid' => false, 'mssg' => $errorMessages]);
        }
        $emp_data = User::whereRaw('RIGHT(employee_code, 3) = ?', [$emp_code])->where('email',$email)->first();
        if ($emp_data) {
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('attendance_image', $imageName, 'public');
            }
                $current_date = date('Y-m-d');
                $current_time = date('H:i:s');
         $attendance_data =   DB::table('attendance')->where('date', $current_date)->where('employee_id', $emp_data->id)->first();
        //  dd($attendance_data);

         if($attendance_data->status=='Leave'){
               return response()->json([
                        'valid' =>true,
                        'success' => false,
                        'mssg' => "You are on leave today, you can't log in until admin not allow you."
                    ]);
         }
        
        $currenttime = date('h:i:s a');
            if($attendance_data){
                  $notificationData = [
                      'message' => "{$emp_data->name} login at {$currenttime}",
                        'notification_id' => 'testing',
                        'booking_id' => 'testing',
                        'title' => "{$emp_data->name} Login",
                        'image' =>'https://as1.ftcdn.net/v2/jpg/06/93/42/68/1000_F_693426842_F1nSwc31OEmN9bl78aAoQcURJB7cG8tN.jpg'
                    ];

                if ($attendance_data->login_time_status != 1) {
                    
                    $compareSales = Carbon::parse('14:10:00');
                    $compareOther = Carbon::parse('09:15:13');
                    $currentTime = Carbon::now();
                    $check_data = User::where('id', $emp_data->id)->first();
                    
                    if ($check_data) {
                        if ($check_data->department == 'Sales Dept') {
                            $login_status = ($currentTime < $compareSales) ? "On Time" : "Late";
                        } else {
                            $login_status = ($currentTime < $compareOther) ? "On Time" : "Late";
                        }
                    }
                    
                    DB::table('attendance')->where('date', $current_date)->where('employee_id', $emp_data->id)->update([
                        'login_time_status' => 1,
                        'login_time' => $current_time,
                        'status' => 'Present',
                        'type' =>$login_status,
                        'login_image' => $imageName,
                    ]);
                    
                    $admin_data = User::where('type','Admin')->get();
                    foreach($admin_data as $admin_data){
                                  
                        if($admin_data->fcm_token && !empty($admin_data->fcm_token)){
                        $response = $this->sendFirebasePush([$admin_data->fcm_token], $notificationData);
                        }
                    }
                
                    return response()->json([
                        'authenticated' => true,
                        'valid' => true,
                        'success' => true,
                        'mssg' => 'You are Logged In'
                    ]);
                } else {
                    return response()->json([
                        'authenticated' => true,
                        'valid' => true,
                        'success' => false,
                        'mssg' => 'You are already Logged In'
                    ]);
                }
            }
        }
        return response()->json([
            'authenticated' => true,
            'valid' => true,
            'success' => false,
            'mssg' => 'Invalid employee code'
        ]);
    }
    
    public function logout_attendance(Request $request)
    {
        $emp_code = $request->input('emp_code');
        $image = $request->input('image');
        $email = $request->input('email');
        $validatedData = Validator::make($request->all(), [
            'emp_code' => 'required|digits:3',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'email' => 'required|email|exists:users,email',
        ]);
        
        if ($validatedData->fails()) {
            $errorMessages = $validatedData->errors()->all();
            return response()->json(['authenticated' => true, 'valid' => false, 'mssg' => $errorMessages]);
        }
        
        $emp_data = User::whereRaw('RIGHT(employee_code, 3) = ?', [$emp_code])->where('email',$email)->first();
        if ($emp_data) {
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('attendance_image', $imageName, 'public');
            }
                $current_date = date('Y-m-d');
                $current_time = date('H:i:s');
           $attendance_data =   DB::table('attendance')->where('date', $current_date)->where('employee_id', $emp_data->id)->first();
        
            if($attendance_data){
                if ($attendance_data->login_time_status == 1) {
                    
                      if ($attendance_data->logout_time != null) {
                          
                          return response()->json([
                            'authenticated' => true,
                            'valid' => true,
                            'success' => false,
                            'mssg' => 'You are already Logged Out' 
                        ]);
                          
                      }
                      
                    //"Emp name" is login/logout at "time"
                    $currenttime = date('h:i:s a');
                    $notificationData = [
                        'message' => "{$emp_data->name} logout at {$currenttime}",
                        'notification_id' => 'testing',
                        'booking_id' => 'testing',
                        'title' => "{$emp_data->name} Logout",
                        'image' =>'https://as1.ftcdn.net/v2/jpg/06/93/42/68/1000_F_693426842_F1nSwc31OEmN9bl78aAoQcURJB7cG8tN.jpg'
                    ];
                    
                    DB::table('attendance')->where('date', $current_date)->where('employee_id', $emp_data->id)->update([
                        'logout_time' => $current_time,
                        'logout_image' => $imageName,
                    ]);
                    
                     $admin_data = User::where('type','Admin')->get();
                    foreach($admin_data as $admin_data){
                        
                        if($admin_data->fcm_token && !empty($admin_data->fcm_token)){
                        $response = $this->sendFirebasePush([$admin_data->fcm_token], $notificationData);
                        }
                    }
                
                    return response()->json([
                        'authenticated' => true,
                        'valid' => true,
                        'success' => true,
                        'mssg' => 'You are Logged out'
                    ]);
                    
                } else {
                    return response()->json([
                        'authenticated' => true,
                        'valid' => true,
                        'success' => false,
                        'mssg' => 'Please login first'
                    ]);
                }
            }
        }
        
        return response()->json([
            'authenticated' => true,
            'valid' => true,
            'success' => false,
            'mssg' => 'Invalid data'
        ]);
    }
    
    public function get_employee(Request $request)
    {
      $allEmployees = DB::table('users')
        ->where('priority_levels', '!=', 'P1')
        ->whereNull('deleted_at')
        ->select('id', 'name', 'employee_code', 'email','image')
        ->orderBy('name', 'asc')
        ->get();
            
        return response()->json([
            'authenticated' => true,
            'valid' => true,
            'success' => true,
            'data' => $allEmployees
        ]);
    }
    
    public function sendFirebasePush($tokens, $data)
    {
        $pathToServiceAccount = storage_path('app/public/hr-management-a1a12-firebase-adminsdk-oyms3-13ecca7643.json');
        if (!file_exists($pathToServiceAccount)) {
            die("Service account file does not exist.");
        }
        
        $projectId = 'hr-management-a1a12'; 
        $endpoint = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
        $credentials = new ServiceAccountCredentials($scopes, $pathToServiceAccount);
        
        try {
            $accessToken = $credentials->fetchAuthToken()['access_token'];
        } catch (\Exception $e) {
            error_log('Error fetching access token: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Unable to fetch access token'];
        }
    
        $fields = [
            'message' => [
                'token' => $tokens[0],
                'notification' => [
                    'title' => $data['title'],
                    'body' => $data['message'],
                ],
                'data' => [
                    'message' => $data['message'],
                    'notification_id' => 'Test',
                    'type' => $data['type'] ?? 'default_type',
                    'booking_id' => 'kwh_unit_100%',
                    'image' => $data['image'] ?? null,
                ],
                'android' => [
                    'priority' => 'high',
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'mutable-content' => 1,
                        ],
                    ],
                ],
            ],
        ];
    
    
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ];
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    
        $result = curl_exec($ch);
        if ($result === FALSE) {
            error_log('FCM Send Error: ' . curl_error($ch));
            return ['status' => 'error', 'message' => 'Failed to send notification'];
        }
        curl_close($ch);
        $responseData = json_decode($result, true);
    
        error_log('FCM Response: ' . print_r($responseData, true));
    
        if (isset($responseData['name'])) {
            return [
                'status' => 'success',
                'message' => 'Notification sent successfully.',
                'response' => $responseData,
            ];
        } else {
            return [
                'status' => 'failure',
                'message' => 'Failed to send notification.',
                'response' => $responseData,
            ];
        }
    }
    
    public function reminderall()
    {
        $timeZone = 'Asia/Kolkata';
        $currentDate = Carbon::now($timeZone);
        $currentMonth = $currentDate->month;
        $currentYear = $currentDate->year;
    
        // Arrays to hold categorized reminders
        $upcomingEvents = [];
        $currentEvents = [];
        $olderEvents = [];
    
        $all_employee = User::where('status', '!=', 'Inactive')
            ->where('priority_levels', '!=', 'P1')
            ->whereNull('deleted_at')
            ->get();
    
        foreach ($all_employee as $employee) {

            // if ($employee->doj) {
            //     $dojAnniversary = Carbon::parse($employee->doj)->setYear($currentYear);
            //     if ($dojAnniversary->month == $currentMonth) {
            //         $daysLeft = $currentDate->diffInDays($dojAnniversary, false);
            //         $eventData = [
            //             'type' => 'Anniversary',
            //             'name' => $employee->name,
            //             'email' => $employee->email,
            //             'employee_code' => $employee->employee_code,
            //             'date' => $dojAnniversary->format('Y-m-d'),
            //             'days_left' => $daysLeft
            //         ];
    
            //         // Categorize event
            //         if ($daysLeft > 0) {
            //             $upcomingEvents[] = $eventData;
            //         } elseif ($daysLeft === 0) {
            //             $currentEvents[] = $eventData;
            //         } else {
            //             $olderEvents[] = $eventData;
            //         }
            //     }
            // }
          if (!empty($employee->doj)) {
                try {
                    $currentDate = Carbon::now();
                    $currentMonth = $currentDate->month;
                    $doj = Carbon::parse($employee->doj);
                    if ($doj->copy()->addYear()->lessThanOrEqualTo($currentDate)) {
                        $yearsSinceJoining = $doj->diffInYears($currentDate);
                        $dojAnniversary = $doj->copy()->addYears($yearsSinceJoining);
            
                        if ($dojAnniversary->month === $currentMonth) {
                            $daysLeft = $currentDate->diffInDays($dojAnniversary, false);
            
                            $eventData = [
                                'type' => 'Anniversary',
                                'name' => $employee->name,
                                'email' => $employee->email,
                                'employee_code' => $employee->employee_code,
                                'date' => $dojAnniversary->format('Y-m-d'),
                                'days_left' => $daysLeft,
                            ];
            
                            if ($daysLeft > 0) {
                                $upcomingEvents[] = $eventData; 
                            } elseif ($daysLeft === 0) {
                                $currentEvents[] = $eventData; 
                            } else {
                                $olderEvents[] = $eventData;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error("Error processing DOJ for employee {$employee->id}: {$e->getMessage()}");
                }
            }
    
            // 2. Birthday Reminder
            if ($employee->dob) {
                $birthday = Carbon::parse($employee->dob)->setYear($currentYear);
                if ($birthday->month == $currentMonth) {
                    $daysLeft = $currentDate->diffInDays($birthday, false);
                    $eventData = [
                        'type' => 'Birthday',
                        'name' => $employee->name,
                        'email' => $employee->email,
                        'employee_code' => $employee->employee_code,
                        'date' => $birthday->format('Y-m-d'),
                        'days_left' => $daysLeft
                    ];
    
                    // Categorize event
                    if ($daysLeft > 0) {
                        $upcomingEvents[] = $eventData;
                    } elseif ($daysLeft === 0) {
                        $currentEvents[] = $eventData;
                    } else {
                        $olderEvents[] = $eventData;
                    }
                }
            }
    
            // 3. Prohibition Period Completion Reminder
            if ($employee->status == 'Prohibition' && $employee->prohibition_end_date) {
                $prohibitionEndDate = Carbon::parse($employee->prohibition_end_date, $timeZone);
                if ($prohibitionEndDate->month == $currentMonth && $prohibitionEndDate->year == $currentYear) {
                    $daysLeft = $currentDate->diffInDays($prohibitionEndDate, false);
                    $eventData = [
                        'type' => 'Prohibition End',
                        'name' => $employee->name,
                        'email' => $employee->email,
                        'employee_code' => $employee->employee_code,
                        'date' => $prohibitionEndDate->format('Y-m-d'),
                        'days_left' => $daysLeft
                    ];
    
                    // Categorize event
                    if ($daysLeft > 0) {
                        $upcomingEvents[] = $eventData;
                    } elseif ($daysLeft === 0) {
                        $currentEvents[] = $eventData;
                    } else {
                        $olderEvents[] = $eventData;
                    }
                }
            }
    
            // 4. Internship Completion Reminder
           if ($employee->status == 'Intern' && $employee->intern_end_date) {
                $internshipEndDate = Carbon::parse($employee->intern_end_date);
                
                if ($internshipEndDate->month == $currentMonth && $internshipEndDate->year == $currentYear) {
                    $daysLeft = $currentDate->diffInDays($internshipEndDate, false);
                    
                    $eventData = [
                        'type' => 'Internship Completion',
                        'name' => $employee->name,
                        'email' => $employee->email,
                        'employee_code' => $employee->employee_code,
                        'date' => $internshipEndDate->format('Y-m-d'),
                        'days_left' => $daysLeft
                    ];
            
                    // Categorize event
                    if ($daysLeft > 0) {
                        $upcomingEvents[] = $eventData;
                    } elseif ($daysLeft === 0) {
                        $currentEvents[] = $eventData;
                    } else {
                        $olderEvents[] = $eventData;
                    }
                }
            }
    
            // 5. Performance Review Reminder (For employees on Prohibition)
            if ($employee->status == 'Prohibition' && $employee->doj) {
                $nextReviewDate = Carbon::parse($employee->doj)->addMonths($currentDate->diffInMonths($employee->doj));
                if ($nextReviewDate->month == $currentMonth && $nextReviewDate->year == $currentYear) {
                    $daysLeft = $currentDate->diffInDays($nextReviewDate, false);
                    $eventData = [
                        'type' => 'Performance Review',
                        'name' => $employee->name,
                        'email' => $employee->email,
                        'employee_code' => $employee->employee_code,
                        'date' => $nextReviewDate->format('Y-m-d'),
                        'days_left' => $daysLeft
                    ];
    
                    // Categorize event
                    if ($daysLeft > 0) {
                        $upcomingEvents[] = $eventData;
                    } elseif ($daysLeft === 0) {
                        $currentEvents[] = $eventData;
                    } else {
                        $olderEvents[] = $eventData;
                    }
                }
            }
        }
        
        
        //  if (empty($upcomingEvents)) {
        // $upcomingEvents[] = ['message' => 'No upcoming events.'];
        // }
    
        // if (empty($currentEvents)) {
        //     $currentEvents[] = ['message' => 'No events today.'];
        // }
    
        // if (empty($olderEvents)) {
        //     $olderEvents[] = ['message' => 'No past events this month.'];
        // }

    //   'authenticated' => true,
    //               'valid' =>true,
    //               'success' => false,
    //               'mssg' => 'data allrady inserted'
        return [
            'authenticated' => true,
            'valid' =>true,
            'upcoming_events' => $upcomingEvents,
            'current_events' => $currentEvents,
            'older_events' => $olderEvents,
        ];
    }
    
    private function categorizeReminder(&$reminderArray, $reminder, $reminderDate, $oneMonthAgo, $currentDate, $oneMonthAhead)
    {
        if ($reminderDate->between($currentDate, $oneMonthAhead)) {
            $reminderArray['upcoming'][] = $reminder;
        } elseif ($reminderDate->between($oneMonthAgo, $currentDate)) {
            $reminderArray['current'][] = $reminder;
        } else {
            $reminderArray['older'][] = $reminder;
        }
    }

    public function storedata($prev_data, $id, $type)
    {
        $admin_data = Auth::user();
        $employee_id = $id;
    
        $employee_Data = User::where('id', $employee_id)->first();

        if (!$employee_Data) {
            return response()->json(['error' => 'Employee not found'], 404);
        }
    
        $changes = [];
    
    
          $dataKeys = [
            "name", "employee_code", "experience", "email", "status", "department", 
            "password", "mobile", "reporting_manager", "blood_group", "location", 
            "biometrics", "login_token", "designation", "notice_period_end_date", 
            "prohibition_end_date", "dob", "doj", "image", "type", 
            "total_experience", "priority_levels", "fcm_token", "emergency_number"
            ];
     

        foreach ($dataKeys as $key) {

            $newValue = $employee_Data->{$key} ?? null;
    

            $oldValue = isset($prev_data->{$key}) ? $prev_data->{$key} : null;
    
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'previous' => $oldValue,
                    'updated' => $newValue
                ];
            }
        }
        $changesJson = json_encode($changes);
       
                DB::table('admin_history')->insert([
                    'employee_id' => $employee_id,
                    'updated_by' => $admin_data->name,
                    'data_type' => $type,
                    'data' => $changesJson,
                    'updated_at' => now(),
                ]);
        

        return response()->json($employee_Data);
    }
    
    public function add_data($leave,$employee_id,$type)
    {
        $admin_data = Auth::user();
        $changesJson = json_encode($leave);
            DB::table('admin_history')->insert([
                'employee_id' => $employee_id,
                'updated_by' => $admin_data->name,
                'data_type' => $type,
                'data' => $changesJson,
                'updated_at' => now(),
            ]);
    }
    
    public function update_leave_data($leave,$prev_data,$employee_id,$type,$auto_generated_id)
    {
         $admin_data = Auth::user();
        $Leave_Data = Leave::where('id', $auto_generated_id)->first();
        if (!$Leave_Data) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

         $changes = [];
         $dataKeys = [
            "login_token", "leave_type", "leave_name", "leave_start_date", "leave_end_date", "total_days", 
            "leave_status", "reasone", "leave_code", "leave_period"
            ];

        foreach ($dataKeys as $key) {

            $newValue = $Leave_Data->{$key} ?? null;
            $oldValue = isset($prev_data->{$key}) ? $prev_data->{$key} : null;
            // Check if the old value is different from the new value
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'previous' => $oldValue,
                    'updated' => $newValue
                ];
            }
        }
        $changesJson = json_encode($changes);
       
                DB::table('admin_history')->insert([
                    'employee_id' => $employee_id,
                    'updated_by' => $admin_data->name,
                    'data_type' => $type,
                    'data' => $changesJson,
                    'updated_at' => now(),
                ]);

        return response()->json($Leave_Data);
    }
     
    public function salary_calculation(Request $request)
    {
         $id = $request->input('id');
         $validatedData = Validator::make($request->all(), [
             'id' => 'required|exists:users,id'
            ]);

       if ($validatedData->fails()) {
            $errorMessages = $validatedData->errors()->all();
            return response()->json(['valid' => false, 'mssg' => $errorMessages]);
        }
                    
        $month = $month ?? Carbon::now()->format('F');
        $year = $year ?? Carbon::now()->format('Y');
        $previousMonth = Carbon::now()->subMonth();
        $previousMonth = Carbon::now();
        $month = $previousMonth->format('F');
        // dd($month);
        $month ='November';//
        $daysInPreviousMonth = $previousMonth->daysInMonth;

        $lateThreshold = '09:15:00';
        $all_employee = User::where('priority_levels', '!=', 'P1')
            ->whereNull('deleted_at')
            ->where('status', '!=', 'Inactive')
            ->pluck('id');
            
        $employee_data = User::where('priority_levels', '!=', 'P1')
                ->whereNull('deleted_at')
                ->where('id',$id)
                ->where('status', '!=', 'Inactive')
                ->first();
        
        if($employee_data->department =="Sales Dept"){
            $lateThreshold = '14:10:00';
        }
    
        $data=[];
            
        $lateCount = DB::table('attendance')
                ->where('year', $year)
                ->where('month', $month)
                ->where('employee_id',$id)
                ->where('login_time', '>', $lateThreshold)
                ->count();
            
        $ontime = DB::table('attendance')
                ->where('year', $year)
                ->where('month', $month)
                ->where('employee_id',$id)
                ->where('login_time', '<', $lateThreshold)
                ->count();
        
            
         $leave = DB::table('attendance')
                ->where('year', $year)
                ->where('month', $month)
                ->where('employee_id',$id)
                ->where('status','Leave')
                ->count();
                 
        $sallary_deduction = DB::table('leaves')
                ->where('year', $year)
                ->where('month', $month)
                ->where('employee_id', $id)
                ->sum('salary_deduction_days');
                
        
        $paid_full_day = Leave::where('employee_id', $id)
             ->where('month',$month)
             ->where('year', $year)
             ->where('leave_status','Approved')
             ->where('leave_type','paid')
            //  ->where('salary_deduction_days',0)
             ->where('leave_period','days')
             ->sum('total_days');
        
        
        $paid_half_day = Leave::where('employee_id', $id)
             ->where('month',$month)
             ->where('year', $year)
             ->where('leave_status','Approved')
             ->where('leave_type','paid')
             ->where('leave_period','Half-Day')
            //  ->where('salary_deduction_days',0)
             ->sum('total_days');
            
             
         $unpaid_full_day = Leave::where('employee_id', $id)
             ->where('month',$month)
             ->where('year', $year)
             ->where('leave_status','Approved')
             ->where('leave_type','unpaid')
            //  ->where('salary_deduction_days',0)
             ->where('leave_period','days')
             ->sum('total_days');
             
        $unpaid_half_day = Leave::where('employee_id', $id)
             ->where('month',$month)
             ->where('year', $year)
             ->where('leave_status','Approved')
             ->where('leave_type','unpaid')
            //  ->where('salary_deduction_days',0)
             ->where('leave_period','Half-Day')
             ->sum('total_days');
             
             
        $unaprove_full_day = Leave::where('employee_id', $id)
             ->where('month',$month)
             ->where('year', $year)
             ->where('leave_status','Unapproved')
             ->where('leave_type','unpaid')
            //  ->where('salary_deduction_days',0)
             ->where('leave_period','days')
             ->sum('total_days');
             
        $unaprove_half_day = Leave::where('employee_id', $id)
             ->where('month',$month)
             ->where('year', $year)
             ->where('leave_status','Unapproved')
             ->where('leave_type','unpaid')
            //  ->where('salary_deduction_days',0)
             ->where('leave_period','Half-Day')
             ->sum('total_days');
                
        $data[] = [
                'employee_id' => $id,
                'month' => $month,
                'lateCount' => $lateCount,
                'ontime' => $ontime,
                // 'leave' => $leave,
                'total_Leave' => $paid_full_day+$paid_half_day+$unpaid_full_day+$unpaid_half_day+$unaprove_full_day+$unaprove_half_day,
                'paid_full_day' =>$paid_full_day,
                'paid_half_day' =>$paid_half_day,
                'unpaid_full_day' =>$unpaid_full_day,
                'unpaid_half_day' =>$unpaid_half_day,
                'unaprove_full_day' =>$unaprove_full_day,
                'unaprove_half_day'=>$unaprove_half_day,
                'total_salary_deduction'=>$sallary_deduction,
                'total_days' =>$daysInPreviousMonth
            ];            
            
         return response()->json([
                   'authenticated' => true,
                   'valid' =>true,
                   'success' => true,
                   'data'=>$data
                ]);
    }
    
    public function salary_submit(Request $request)
    {
        
         $employee_id = $request->input('employee_id');
         $lateCount = $request->input('lateCount');
         $ontime = $request->input('ontime');
         $total_Leave = $request->input('total_Leave');
         $paid_full_day = $request->input('paid_full_day');
         $paid_half_day = $request->input('paid_half_day');
         $total_leave = $request->input('total_leave');
         $unpaid_full_day = $request->input('unpaid_full_day');
         $unpaid_half_day = $request->input('unpaid_half_day');
         $unaprove_full_day = $request->input('unaprove_full_day');
         $unaprove_half_day = $request->input('unaprove_half_day');
        //  $unapproved_leave = $request->input('unapproved_leave');
         $total_salary_deduction = $request->input('total_salary_deduction');
         $total_days = $request->input('total_days');
         $salary_days = $request->input('salary_days');
         $salary_calculated_by = $request->input('salary_calculated_by');
        //  $day = $request->input('day');
        //  $date = $request->input('date');
        //  $month = $request->input('month');
        //  $year = $request->input('year');
        //  $previousMonthDate = Carbon::now()->subMonth();
        //  $previousMonth = $previousMonthDate->format('F');
        
        $validatedData = Validator::make($request->all(), [
            'employee_id' => 'required|integer|exists:users,id',
            'lateCount' => 'required|integer|min:0',
            'ontime' => 'required|integer|min:0',
            'total_Leave' => 'required|integer|min:0',
            'paid_full_day' => 'required|integer|min:0',
            'paid_half_day' => 'required|integer|min:0',
            'unpaid_full_day' => 'required|integer|min:0',
            'unpaid_half_day' => 'required|integer|min:0',
            'unaprove_full_day' => 'required|integer|min:0',
            'unaprove_half_day' => 'required|integer|min:0',
            // 'unapproved_leave' => 'required|integer|min:0',
            'total_salary_deduction' => 'required|numeric|min:0',
            'total_days' => 'required|min:0',
            'salary_days'=> 'required|min:0',
            'salary_calculated_by' =>'required',
            // 'day' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            // 'date' => 'required|date',
            // 'month' => 'required|string|in:January,February,March,April,May,June,July,August,September,October,November,December',
            // 'year' => 'required|integer|min:2000'
        ]);
        //  dd(a);


          if ($validatedData->fails()) {
            $errorMessages = $validatedData->errors()->all();
            return response()->json(['valid' => false, 'mssg' => $errorMessages]);
        }
          
        $previousMonth = Carbon::now()->subMonth();
        $month = $previousMonth->format('F');
        $month ='October';
        $currentMonth = Carbon::now()->format('F');
        $currentYear = Carbon::now()->format('Y');
        $currentDate = Carbon::now()->format('Y-m-d');
        $currentDay = Carbon::now()->format('l');
         
        $check_data = DB::table('salary_calculation')->where('employee_id',$employee_id)->where('month',$month)->first();
         if($check_data){
                return response()->json([
                   'authenticated' => true,
                   'valid' =>true,
                   'success' => false,
                   'mssg' => 'data allrady inserted'
                ]);
         }
         
         DB::table('salary_calculation')->insert([
            'employee_id' => $employee_id,
            'lateCount' => $lateCount,
            'ontime' => $ontime,
            'total_Leave' => $total_Leave,
            'paid_full_day' => $paid_full_day,
            'paid_half_day' => $paid_half_day,
            'unpaid_full_day' => $unpaid_full_day,
            'unpaid_half_day' => $unpaid_half_day,
            'unaprove_full_day' => $unaprove_full_day,
            'unaprove_half_day' => $unaprove_half_day,
            // 'unapproved_leave' => $unapproved_leave,
            'total_salary_deduction' => $total_salary_deduction,
            'total_days' => $total_days,
            'salary_days' =>$salary_days,
            'salary_calculated_by' =>$salary_calculated_by,
            'day' => $currentDay,
            'date' => $currentDate,
            'month' => $month,
            'year' => $currentYear,
            ]); 
            $salaryData = [
                'employee_id' => $employee_id,
                'lateCount' => $lateCount,
                'ontime' => $ontime,
                'total_Leave' => $total_Leave,
                'paid_full_day' => $paid_full_day,
                'paid_half_day' => $paid_half_day,
                'salary_days' =>$salary_days,
                'salary_calculated_by' =>$salary_calculated_by,
                'unpaid_full_day' => $unpaid_full_day,
                'unpaid_half_day' => $unpaid_half_day,
                'unaprove_full_day' => $unaprove_full_day,
                'unaprove_half_day' => $unaprove_half_day,
                // 'unapproved_leave' => $unapproved_leave,
                'total_salary_deduction' => $total_salary_deduction,
                'total_days' => $total_days,
                'day' => $currentDay,
                'date' => $currentDate,
                'month' => $month,
                'year' => $currentYear,
            ];
            
           $admin_data = Auth::user();
           $type = "salary_submit";
           $changesJson = json_encode($salaryData);
            DB::table('admin_history')->insert([
                'employee_id' => $employee_id,
                'updated_by' => $admin_data->name,
                'data_type' => $type,
                'data' => $changesJson,
                'updated_at' => now(),
            ]);
           return response()->json([
                   'authenticated' => true,
                   'valid' =>true,
                   'success' => true,
                   'mssg' => 'data inserted'
                ]);
    }
    
    public function get_approved_salary(Request $request)
    {
        
         $employee_id = $request->input('employee_id');
         $month = $request->input('month');
         $validatedData = Validator::make($request->all(), [
                'employee_id' => 'required|integer|exists:users,id',
                'month' => 'required|string|max:50',
            ]);
         
          if ($validatedData->fails()) {
 
            $errorMessages = $validatedData->errors()->all();
            return response()->json([  'authenticated' => true,'valid' => false, 'mssg' => $errorMessages]);
         }
         
         $check_data = DB::table('salary_calculation')->where('employee_id',$employee_id)->where('month',$month)->first();
         
         if($check_data){
              return response()->json([
                   'authenticated' => true,
                   'valid' =>true,
                   'success' => true,
                   'data' =>$check_data,
                ]);
         }
         
          return response()->json([
                   'authenticated' => true,
                   'valid' =>true,
                   'success' => false,
                   'mssg' =>'data not found'
                ]);
    }
    
    public function add_assets(Request $request)
    {
           $validatedData = Validator::make($request->all(), [
                'employee_id' => 'required|integer|exists:users,id',
                'item_name' => 'required|string',
                'serial_number' => 'required|string',
                'issued_by'=> 'required',
                'note'=> 'required',
                'image' => 'required|array', 
                'image.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

        if ($validatedData->fails()) {
            $errorMessages = $validatedData->errors()->all();
            return response()->json([
                'authenticated' => true,
                'valid' => false,
                'mssg' => $errorMessages
            ]);
        }
    
        $employee_id = $request->input('employee_id');
        $note = $request->input('note');
        $item_name = $request->input('item_name');
        $serial_number = $request->input('serial_number');
        $issued_by = $request->input('issued_by');
        $currentDate = date('Y-m-d');

        $imagename = [];
        if ($request->has('image')) {
            foreach ($request->file('image') as $image) {
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image = $image->storeAs('asset_image', $imageName, 'public');
                $imagename[] = $imageName;
            }
        }
        $array_image = json_encode($imagename);

        $asset = new Asset();
        $asset->employee_id = $employee_id;
        $asset->item_name = $item_name;
        $asset->serial_number = $serial_number;
        $asset->date = $currentDate;
        $asset->note = $note;
        $asset->image = $array_image;
        $asset->issued_by = $issued_by;
        $asset->save();
        
          $admin_data = Auth::user();
          $type = "add_asset";
          $changesJson = json_encode($asset);
            DB::table('admin_history')->insert([
                'employee_id' =>$admin_data->id,
                'updated_by' => $admin_data->name,
                'data_type' => $type,
                'data' => $changesJson,
                'updated_at' => now(),
            ]);
    
        return response()->json([
            'authenticated' => true,
            'valid' => true,
            'success' => true,
            'mssg' => 'Data inserted successfully'
        ]);
    }
    
    public function get_assets(Request $request)
    {
        $data = Asset::join('users', 'assets.employee_id', '=', 'users.id')
        ->select('assets.*', 'users.name as employee_name')
        ->get()
        ->map(function($asset) {
            $images = json_decode($asset->image) ?: [];
            $asset->image = array_map(function($imagePath) {
                return [
                    'image' => url('storage/asset_image/' . $imagePath),
                ];
            }, $images);
    
            return $asset;
        });
    
    return response()->json([
        'authenticated' => true,
        'valid' => true,
        'success' => true,
        'data' => $data,
    ]);
        
    }
    
    public function delete_assets(Request $request)
    {
         $auto_generated_id = $request->input('auto_generated_id');
         $validatedData = Validator::make($request->all(), [
            'auto_generated_id' => 'required|integer',
          ]);

        if ($validatedData->fails()) {
            $errorMessages = $validatedData->errors()->all();
            return response()->json([
                'authenticated' => true,
                'valid' => false,
                'mssg' => $errorMessages
            ]);
        }
        
        $asset = Asset::find($auto_generated_id);
      
        if ($asset) {
            $asset->delete();
            
              $admin_data = Auth::user();
              $type = "delete_asset";
              $changesJson = json_encode($asset);
                DB::table('admin_history')->insert([
                    'employee_id' => $asset->employee_id,
                    'updated_by' => $admin_data->name,
                    'data_type' => $type,
                    'data' => $changesJson,
                    'updated_at' => now(),
                ]);
           return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' =>true,
                'mssg' => 'data deleted successfully',
            ]);
        } else {
             return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' =>false,
                'mssg' => 'no data found',
            ]);
        }
    }
    
    public function update_assets(Request $request)
    {
    $validatedData = Validator::make($request->all(), [
        'employee_id' => 'integer|exists:users,id',
        'item_name' => 'sometimes|string',
        'serial_number' => 'sometimes|string',
        'issued_by' => 'sometimes|string',
        'note' => 'sometimes|string',
        'auto_generated_id' => 'required|integer|exists:assets,id',
        'image.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    if ($validatedData->fails()) {
        $errorMessages = $validatedData->errors()->all();
        return response()->json([
            'authenticated' => true,
            'valid' => false,
            'mssg' => $errorMessages
        ]);
    }

    $auto_generated_id = $request->input('auto_generated_id');
    $asset = Asset::find($auto_generated_id);

    if (!$asset) {
        return response()->json([
            'authenticated' => true,
            'valid' => true,
            'success' => false,
            'mssg' => 'Asset not found.'
        ]);
    }
    

    // if ($request->has('employee_id')) {
    //     $asset->employee_id = $request->input('employee_id');
    // }
    if ($request->has('item_name')) {
        $asset->item_name = $request->input('item_name');
    }
    if ($request->has('serial_number')) {
        $asset->serial_number = $request->input('serial_number');
    }
    if ($request->has('issued_by')) {
        $asset->issued_by = $request->input('issued_by');
    }
    if ($request->has('note')) {
        $asset->note = $request->input('note');
    }

    if ($request->has('image')) {
        $imagename = [];
        foreach ($request->file('image') as $image) {
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('asset_image', $imageName, 'public');
            $imagename[] = $imageName;
        }
        $asset->image = json_encode($imagename);
    }
    
    $asset->save();

    return response()->json([
        'authenticated' => true,
        'valid' => true,
        'success' => true,
        'mssg' => 'Asset updated successfully.'
    ]);
    }
    
    public function get_admin_history()
    {
        $history = DB::table('admin_history')->get();
        $dataArray = $history->toArray();
    
        $organizedData = [
            "add_employee" => [],
            "update_employee" => [],
            "add_leave" => [],
            "update_leave" => [],
            "delete_asset" => [],
        ];
    
        foreach ($dataArray as $record) {
            $dataType = $record->data_type;
            
            $employeeData = json_decode($record->data, true);
            $entry = [
                "employee_id" => $record->employee_id,
                "updated_by" => $record->updated_by,
                "updated_at" => $record->updated_at,
                "data" => $employeeData
            ];
            
            if (($dataType === "update_employee" || $dataType === "update_leave") && property_exists($record, 'previous_data') && !empty($record->previous_data)) {
                $previousData = json_decode($record->previous_data, true); 
                $entry['previous_data'] = $previousData;
            }
        
            if (array_key_exists($dataType, $organizedData)) {
                $organizedData[$dataType][] = $entry;
            }
        }
        return response()->json($organizedData, 200);
    }
    
    public function get_past_emp()
    {
        $past_emp = User::onlyTrashed()->get(); // Retrieve only soft-deleted records
        
        if (!$past_emp->isEmpty()) {
            return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' => true,
                'data' => $past_emp
            ]);
        }
    
        return response()->json([
            'authenticated' => true,
            'valid' => true,
            'success' => false,
            'mssg' => 'No data found'
        ]);
    }
    
    public function get_all_salary(Request $request)
    {
         
         $data = DB::table('salary_calculation')
                ->join('users','users.id','=','salary_calculation.employee_id')
                ->select('salary_calculation.*','users.name')
                ->get();
         
         if(!($data->isEmpty())){
             return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' => true,
                'data' => $data,
            ]);
         }
          return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' => false,
                'mssg' =>'no data found'
         ]);
            
   }
   
    public function add_stock(Request $request)
    {
     
         $itemname = $request->input('itemname');
         $model_brand = $request->input('model_brand');
         $model_number = $request->input('model_number');
         $serial_number = $request->input('serial_number');
         $accessories = $request->input('accessories');
        //  $quantity = $request->input('quantity');
         $item_condition = $request->input('item_condition');
         $added_by = $request->input('added_by');
         $location = $request->input('location');
         $description = $request->input('description');
         $status = $request->input('status');
         $image = $request->input('image');
         $category = $request->input('category');
        
         $validatedData = Validator::make($request->all(), [
                'itemname'       => 'required|string|max:255',
                'added_by'      =>  'required',
                'category'       => 'required',
                'model_brand'    => 'required|string|max:255',
                'model_number'   => 'required|string|max:255|unique:stocks,model_number',
                'serial_number'  => 'required|string|max:255',
                'accessories'    => 'required|string',
                // 'quantity'       => 'required|integer|min:1',
                'item_condition' => 'required|string',
                'entry_date'  => 'required|date_format:Y-m-d',
                'location'       => 'required|string|max:255',
                'description'    => 'required|string',
                'status'         => 'required|in:Active,Inactive', 
                'image'          => 'required|array', 
                'image.*'        => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
            
            
            
          if ($validatedData->fails()) {
                $errorMessages = $validatedData->errors()->all();
                return response()->json(['authenticated' => true,'valid' => false, 'mssg' => $errorMessages]);
            }
          
           $stock = new Stock();
            $stock->itemname = $request->input('itemname');
            $stock->added_by = $request->input('added_by');
            $stock->model_brand = $request->input('model_brand');
            $stock->model_number = $request->input('model_number');
            $stock->serial_number = $request->input('serial_number');
            $stock->accessories = $request->input('accessories');
            // $stock->quantity = $request->input('quantity');
            $stock->item_condition = $request->input('item_condition');
            $stock->entry_date = $request->input('entry_date');
            $stock->location = $request->input('location');
            $stock->description = $request->input('description');
            $stock->status = $request->input('status');
            $stock->category = $request->input('category');
           
             if ($request->has('image')) {
                foreach ($request->file('image') as $image) {
                    $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    $image = $image->storeAs('stock_image', $imageName, 'public');
                    $imagename[] = $imageName;
                }
              }
            $stock->image = json_encode($imagename);
            $stock->save();
            
            $type ="add_stock";
            $admin_data = Auth::user();
            $changesJson = json_encode($stock);
            DB::table('admin_history')->insert([
                // 'employee_id' => $id,
                'updated_by' => $admin_data->name,
                'data_type' => $type,
                'data' => $changesJson,
                'updated_at' => now(),
            ]);
        
        
            return response()->json([
            'authenticated' => true,
            'valid' => true, 
            'success' => true,
            'mssg' => 'Stock added successfully.']);
        
    }
    
    public function get_stock(Request $request)
    {
        $data = Stock::all()->map(function($asset) {
        $images = json_decode($asset->image) ?: [];
        $asset->image = array_map(function($imagePath) {
            return [
                'image' => url('storage/stock_image/' . $imagePath),
            ];
        }, $images);
    
        return $asset;
    });
        
        return response()->json([
            'authenticated' => true,
            'valid' => true,
            'success' => true,
            'data' => $data,
        ]);
    }
    
    public function update_stock(Request $request)
    {
        $id = $request->input('id');

        $validatedData = Validator::make($request->all(), [
            'id'             => 'required|exists:stocks,id',
            'itemname'       => 'nullable|string|max:255',
            'added_by'       => 'nullable',
            'category'       => 'nullable',
            'model_brand'    => 'nullable|string|max:255',
            'model_number'   => 'nullable|string|max:255|unique:stocks,model_number,' . $id,
            'serial_number'  => 'nullable|string|max:255',
            'accessories'    => 'nullable|string',
            // 'quantity'       => 'nullable|integer|min:1',
            'item_condition' => 'nullable|string',
            'entry_date'     => 'nullable|date_format:Y-m-d',
            'location'       => 'nullable|string|max:255',
            'description'    => 'nullable|string',
            'status'         => 'nullable|in:Active,Inactive',
            'image'          => 'sometimes|array',
            'image.*'        => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
    
        if ($validatedData->fails()) {
            $errorMessages = $validatedData->errors()->all();
            return response()->json(['authenticated' => true, 'valid' => false, 'mssg' => $errorMessages]);
        }
    

        $stock = Stock::find($id);
        $prev_data = Stock::find($id);
       
        if ($request->filled('itemname')) $stock->itemname = $request->input('itemname');
        if ($request->filled('added_by')) $stock->added_by = $request->input('added_by');
        if ($request->filled('category')) $stock->category = $request->input('category');
        if ($request->filled('model_brand')) $stock->model_brand = $request->input('model_brand');
        if ($request->filled('model_number')) $stock->model_number = $request->input('model_number');
        if ($request->filled('serial_number')) $stock->serial_number = $request->input('serial_number');
        if ($request->filled('accessories')) $stock->accessories = $request->input('accessories');
        // if ($request->filled('quantity')) $stock->quantity = $request->input('quantity');
        if ($request->filled('item_condition')) $stock->item_condition = $request->input('item_condition');
        if ($request->filled('entry_date')) $stock->entry_date = $request->input('entry_date');
        if ($request->filled('location')) $stock->location = $request->input('location');
        if ($request->filled('description')) $stock->description = $request->input('description');
        if ($request->filled('status')) $stock->status = $request->input('status');
    
        if ($request->has('image')) {
            $imagename = [];
            foreach ($request->file('image') as $image) {
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image = $image->storeAs('stock_image', $imageName, 'public');
                $imagename[] = $imageName;
            }
            $stock->image = json_encode($imagename);
        }

        $stock->save();
        $type = "update_stock";
        $this->update_stock_data($stock,$prev_data,$type,$id);
    
        return response()->json([
            'authenticated' => true,
            'valid' => true,
            'success' => true,
            'mssg' => 'Stock updated successfully.'
        ]);
    }
    
    public function get_monthly_attendance(Request $request)
    {
        // $employee_id = $request->input('employee_id');
        // $validatedData = Validator::make($request->all(), [
        //     'employee_id'             => 'required|exists:users,id',
        // ]);
    
        // if ($validatedData->fails()) {
        //     $errorMessages = $validatedData->errors()->all();
        //     return response()->json(['authenticated' => true, 'valid' => false, 'mssg' => $errorMessages]);
        // }
    

       $currentDate = Carbon::today(); 
       $currentMonth = Carbon::now()->format('F'); 
       $totalDaysInCurrentMonth = Carbon::now()->daysInMonth;
       $attendance_data = DB::table('attendance')
            ->join('users', 'attendance.employee_id', '=', 'users.id')
            ->whereNull('users.deleted_at')
            ->where('attendance.month',$currentMonth)
            // ->where('attendance.employee_id',$employee_id)
            // ->whereDate('attendance.date', $currentDate) // Filter by current date
            ->select('attendance.id as auto_generated_id','attendance.employee_id', 'attendance.status as attendance_status','attendance.login_time','attendance.date','attendance.logout_time', 'attendance.logout_image','attendance.login_image','attendance.login_time_status','users.name', 'users.department' ,'users.image')
            ->orderBy('users.name', 'asc')
            ->get();
            
        $compareSales = Carbon::parse('14:10:00');
        $compareOther = Carbon::parse('09:15:13');
        
        $formatted_data = [];
        // dd($attendance_data);
        
        
        $year = $year ?? Carbon::now()->format('Y');
        $previousMonth = Carbon::now()->subMonth();
        $previousMonth = Carbon::now();
        $month = $previousMonth->format('F');
        // dd($month);
        $month ='November';//
        
        $lateThreshold = '09:15:00';
    
        foreach ($attendance_data as $data) {
            $employeeId = $data->employee_id;
            if($data->department =="Sales Dept"){
                            $lateThreshold = '14:10:00';
                        }
            
            $lateCount = DB::table('attendance')
                ->where('year', $year)
                ->where('month', $month)
                ->where('employee_id',$employeeId)
                ->where('login_time', '>', $lateThreshold)
                ->count();
                
            $paid_Leave = Leave::where('employee_id', $employeeId)
                 ->where('month',$month)
                 ->where('year', $year)
                //  ->where('leave_status','Approved')
                 ->where('leave_type','paid')
                //  ->where('salary_deduction_days',0)
                //  ->where('leave_period','days')
                 ->sum('total_days');
                
             $unpaid_leave = Leave::where('employee_id', $employeeId)
                 ->where('month',$month)
                 ->where('year', $year)
                //  ->where('leave_status','Unapproved')
                 ->where('leave_type','unpaid')
                //  ->where('salary_deduction_days',0)
                //  ->where('leave_period','days')
                 ->sum('salary_deduction_days');
                
            if (!isset($formatted_data[$employeeId])) {
                $formatted_data[$employeeId] = [
                    'name' => $data->name,
                    'image' => $data->image,
                    'late_count' =>$lateCount,
                    'unpaid_leave' =>$unpaid_leave,
                    'paid_Leave' =>$paid_Leave,
                    'attendance' => [],
                ];
            }
 
            
            $loginTime = $data->login_time ? Carbon::parse($data->login_time) : null;
            $logoutTime = $data->logout_time ? Carbon::parse($data->logout_time) : null;
            // dd($loginTime);
    
            $status = "Late";
            
            if ($loginTime) {
                if ($data->department == 'Sales Dept') {
                    $status = ($loginTime < $compareSales) ? "On Time" : "Late";
                } else {
                    $status = ($loginTime < $compareOther) ? "On Time" : "Late";
                }
            }else{
                  $status = $data->attendance_status;
            }
            
            
       
            $day = Carbon::parse($data->date)->day;
    
            $formatted_data[$employeeId]['attendance']["Day $day"] = [
                'in_time' => $loginTime ? $loginTime->format('H:i') : null,
                'out_time' => $logoutTime ? $logoutTime->format('H:i') : null,
                'status' => $status
            ];
        }

        $formatted_data = array_values($formatted_data);
    
        return response()->json([
            'authenticated' => true,
            'valid' => true,
            'success' => true,
            'data' => $formatted_data
        ]);
   } 
   
    public function sendLeaveNotification($employeeEmail, $employeeName, $employeeId, $leaveType, $startDate, $endDate, $totalDays, $reason = null)
    {

        // $leaveDetails = [
        //     'employee_name' => $employeeName,
        //     'employee_id' => $employeeId,
        //     'leave_type' => $leaveType,
        //     'start_date' => $startDate,
        //     'end_date' => $endDate,
        //     'total_days' => $totalDays,
        //     'reason' => $reason,
        //     'hr_email' => 'hr@cvinfotech.com',
        //     'hr_contact_number' => '+1234567890',
        //     'hr_portal_link' => 'http://hrportal.company.com',
        // ];
    
        
        //  Mail::send('leave_notification', $leaveDetails, function ($message) use ($employeeEmail) {
        //     $message->to($employeeEmail)
        //             ->subject('Leave Notification: Your Leave Status in HR Management System');
        // });
    
        // return response()->json(['message' => 'Leave notification email sent successfully!']);
    }

    public function assign_assets(Request $request)
    {
        
        $validatedData = Validator::make($request->all(), [
            'employee_id'    => 'required|exists:users,id',
            'categories'     => 'required|string|max:255',
            'model_number'   => 'required|max:255',
            'model'          => 'required|max:255',
            'status'         => 'required|max:255',
            'itemname'       => 'required|max:255',
            // 'serial_number'  => 'required|max:255|unique:assign_assets,serial_number',
            'assigned_by'    => 'required|exists:users,id',
            'item_remark'    => 'required|string|max:255',
            'assigned_date'  => 'required|date_format:Y-m-d',
            'description'    => 'required|string|max:255',
            'image.*'        => 'required|file|mimes:jpg,jpeg,png|max:2048',
            'image'          => 'required|array',
    
            'serial_number' => [
                'required',
                'max:255',
                Rule::unique('assign_assets')->where(function ($query) {
                    return $query->where('status', 'Active');
                }),
                Rule::exists('stocks', 'serial_number')->where(function ($query) {
                    return $query->where('status', 'Active');
                }),
            ],
        ]);
    
        if ($validatedData->fails()) {
            return response()->json([
                'authenticated' => true,
                'valid' => false,
                'mssg' => $validatedData->errors()->all(),
            ]);
        }
    
        $data = $validatedData->validated();
    
        $beforeImages = [];
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $image) {
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->storeAs('asset_image', $imageName, 'public');
                $beforeImages[] = $imageName;
            }
            // $data['status'] = 'Active';
        }
        $data['image'] = json_encode($beforeImages);
        $AssignAssets_data = AssignAssets::create($data);
        $inactive_stock = Stock::where('serial_number',$request->serial_number)->first();
        if ($inactive_stock) {
            $inactive_stock->status = 'Inactive';
            $inactive_stock->save();
        }
        
        $type ="assign_assets";
        $admin_data = Auth::user();
        $changesJson = json_encode($AssignAssets_data);
        DB::table('admin_history')->insert([
            'employee_id' => $request->employee_id,
            'updated_by' => $admin_data->name,
            'data_type' => $type,
            'data' => $changesJson,
            'updated_at' => now(),
        ]);
    
    
        return response()->json([
            'authenticated' => true,
            'valid' => true,
            'success' => true,
            'mssg' => ' stored successfully!',
        ]);
    }

    public function get_assign_assets(Request $request)
    {
        // Retrieve data without filtering by employee_id
        $data = AssignAssets::join('users', 'assign_assets.employee_id', '=', 'users.id')
            ->select('assign_assets.*', 'users.name as employee_name', 'users.image as user_image')
            ->get()
            ->map(function ($asset) {
                // Decode before_image
                $beforeImages = json_decode($asset->before_image) ?: [];
                $asset->before_images = array_map(function ($imagePath) {
                    return [
                        'image' => url('storage/asset_image/' . $imagePath),
                    ];
                }, $beforeImages);
    
                // Decode after_image
                $afterImages = json_decode($asset->after_image) ?: [];
                $asset->after_images = array_map(function ($imagePath) {
                    return [
                        'image' => url('storage/asset_image/' . $imagePath),
                    ];
                }, $afterImages);
    
                // Update image key to include formatted URLs
                $images = json_decode($asset->image) ?: [];
                $asset->image = array_map(function ($imagePath) {
                    return [
                        'image' => url('storage/asset_image/' . $imagePath),
                    ];
                }, $images);
    
                return $asset;
            });
    
        // Add admin name for each asset
        $data = $data->map(function ($asset) {
            $assigned_by = User::find($asset->assigned_by); // Fetch admin details
            $asset->admin_name = $assigned_by ? $assigned_by->name : 'N/A'; // Add admin name
            return $asset;
        });
    
        // Handle case where no data is found
        if ($data->isEmpty()) {
            return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' => false,
                'mssg' => 'No data found'
            ]);
        }
    
        // Return successful response with data
        return response()->json([
            'authenticated' => true,
            'valid' => true,
            'success' => true,
            'data' => $data
        ]);
    }

    public function delete_stock(Request $request)
    {
         $id = $request->input('auto_generated_id');
        $validatedData = Validator::make($request->all(), [
            'auto_generated_id' => 'required|exists:stocks,id',
        ]);
    
        if ($validatedData->fails()) {
            $errorMessages = $validatedData->errors()->all();
            return response()->json(['authenticated' => true, 'valid' => false, 'mssg' => $errorMessages]);
        }
        
        $stock = Stock::find($id);
        $stock->delete();
        
        $type ="delete_stock";
        $admin_data = Auth::user();
        $changesJson = json_encode($stock);
        DB::table('admin_history')->insert([
            // 'employee_id' => $id,
            'updated_by' => $admin_data->name,
            'data_type' => $type,
            'data' => $changesJson,
            'updated_at' => now(),
        ]);
        
        return response()->json([
            'authenticated' =>true,
            'valid' => true,
            'success' => true,
            'mssg' => 'Stock deleted successfully.'
        ]);
        
    }
    
    public function update_assign_assets(Request $request)
    {
         $validatedData = Validator::make($request->all(), [
            'employee_id'    => 'required|exists:users,id|exists:assign_assets,employee_id',
            'auto_generated_id' => 'required|exists:assign_assets,id',
            'return_id'      => 'required|max:255',
            // 'reciver_name'   => 'required|max:255',
            'after_description' =>'required|max:255',
            'return_date'    => 'required|date_format:Y-m-d',
             'after_image.*' => 'required|file|mimes:jpg,jpeg,png|max:2048',
            'after_image'    => 'required|array',
        ]);
    
        if ($validatedData->fails()) {
            return response()->json([
                'authenticated' => true,
                'valid' => false,
                'mssg' => $validatedData->errors()->all(),
            ]);
        }
        
        
        
        $data = AssignAssets::where('id',$request->auto_generated_id)
                             ->where('employee_id',$request->employee_id)
                             ->where('status','!=','Inactive')
                             ->first();
        
        if($data){
              Stock::where('serial_number', $data->serial_number)
                 ->update(['status' => 'Active']);
                 
         if ($request->hasFile('after_image')) {
                foreach ($request->file('after_image') as $image) {
                    $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    $image->storeAs('asset_image', $imageName, 'public');
                    $afterImages[] = $imageName;
                }
                $data['after_image'] = json_encode($afterImages);
            }
            
            $return_name =  User::where('id',$request->return_id)->first();
            
            $data->return_id = $request->return_id;
            $data->after_description = $request->after_description;
            $data->return_date = $request->return_date; 
            $data->reciver_name = $return_name->name; 
            $data->return_status = "Returned";
            $data->status  ='Inactive';
            $data->save();
            
             return response()->json([
                'authenticated' =>true,
                'valid' => true,
                'success' => true,
                'mssg' => 'Asset Return successfully.'
            ]);
        }
        
          return response()->json([
            'authenticated' =>true,
            'valid' => true,
            'success' => false,
            'mssg' => 'Asset already Return'
        ]);
    
    }
   
    public function update_stock_data($stock, $prev_data, $type, $id)
    {
        $admin_data = Auth::user();
        if (!$admin_data) {
            return response()->json(['error' => 'Admin not authenticated'], 401);
        }
    
        $stock_Data = Stock::find($id);
        if (!$stock_Data) {
            return response()->json(['error' => 'Stock data not found'], 404);
        }
    
        $changes = [];
        $dataKeys = [
            "added_by", "itemname", "model_brand", "model_number", "serial_number", "accessories",
            "quantity", "item_condition", "entry_date", "location", "description", "status", "image", "category"
        ];
    
        foreach ($dataKeys as $key) {
            $newValue = $stock_Data->{$key} ?? null;
            $oldValue = isset($prev_data->{$key}) ? $prev_data->{$key} : null;
    
            // Check for differences
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'previous' => $oldValue,
                    'updated' => $newValue
                ];
            }
        }
    
        if (!empty($changes)) {
            $changesJson = json_encode($changes);
    
            // Insert into admin_history table
            DB::table('admin_history')->insert([
                'updated_by' => $admin_data->name,
                'data_type' => $type,
                'data' => $changesJson,
                'updated_at' => now(),
               
            ]);
    
            return response()->json(['success' => 'Stock data updated successfully']);
        }
    
        return response()->json(['message' => 'No changes detected']);
    }
    
    public function add_Customer(Request $request)
    {
        
         $validatedData = Validator::make($request->all(), [
            'customer_name'    => 'required|max:255',
            'company_name' => 'required|max:255|unique:customers,company_name',
            'email_addresses'  => 'required|array', 
            'email_addresses.*' => 'email|max:255', 
            'state'            => 'required|max:255',
            'work_phone'       => 'required|max:15',
            'mobile'           => 'required|max:15',
            'country'          => 'required|max:255',
            'city'             => 'required|max:255',
            'address'          => 'required|max:255',
            'pincode'          => 'required|digits_between:4,10',
            'currency'         => 'required',
            
        ]);
    
        if ($validatedData->fails()) {
            return response()->json([
                'authenticated' => true,
                'valid' => false,
                'mssg' => $validatedData->errors()->all(),
            ]);
        }
        
        $customer = new Customer();
        $customer->customer_name = $request->customer_name;
        $customer->company_name = $request->company_name;
        $customer->email_addresses = json_encode($request->email_addresses);
        $customer->state = $request->state;
        $customer->work_phone = $request->work_phone;
        $customer->mobile = $request->mobile;
        $customer->country = $request->country;
        $customer->city = $request->city;
        $customer->address = $request->address;
        $customer->pincode = $request->pincode; 
        $customer->currency = $request->currency;
        $customer->save();
    
        return response()->json([
            'authenticated' => true,
            'valid' => true,
            'success' => true,
            'mssg' => 'Customer data stored successfully.',
        ]);
        
        
    }
    
    public function delete_Customer(Request $request)
    {
         
         $validatedData = Validator::make($request->all(), [
              'customer_id'      => 'required|exists:customers,id'
          ]);
    
        if ($validatedData->fails()) {
            return response()->json([
                'authenticated' => true,
                'valid' => false,
                'mssg' => $validatedData->errors()->all(),
            ]);
        }
        
        $customer_data = Customer::find($request->customer_id);
        if($customer_data){
            $customer_data->delete();
        
           return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' => true,
                'mssg' => 'Customer data deleted successfully.',
            ]);
        }
        
        return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' => false,
                'mssg' => 'Customer data not found',
            ]);
    }
    
    public function get_Customer(Request $request)
    {
        
         
        //  $validatedData = Validator::make($request->all(), [
        //       'customer_id'      => 'required|exists:customers,id'
        //   ]);
    
        // if ($validatedData->fails()) {
        //     return response()->json([
        //         'authenticated' => true,
        //         'valid' => false,
        //         'mssg' => $validatedData->errors()->all(),
        //     ]);
        // }
        
        $customer_data = Customer::all();
        
          if($customer_data){
           return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' => true,
                'data' => $customer_data,
            ]);
        }
        
          return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' => false,
                'mssg' => "data not found",
            ]);
    }
    
    public function update_Customer(Request $request)
    {
        
          $validatedData = Validator::make($request->all(), [
                'customer_id'      => 'required|exists:customers,id',
                'customer_name'    => 'nullable|max:255',
                'company_name'     => 'nullable',
                'email_addresses'  => 'nullable|array', 
                'email_addresses.*' => 'email|max:255', 
                'state'            => 'nullable|max:255',
                'work_phone'       => 'nullable|max:15',
                'mobile'           => 'nullable|max:15',
                'country'          => 'nullable|max:255',
                'city'             => 'nullable|max:255',
                'address'          => 'nullable|max:255',
                'pincode'          => 'nullable|digits_between:4,10',
                'currency'         => 'nullable',
            ]);
        
            if ($validatedData->fails()) {
                return response()->json([
                    'authenticated' => true,
                    'valid' => false,
                    'mssg' => $validatedData->errors()->all(),
                ]);
            }
        
            $customer = Customer::find($request->customer_id);
            if (!$customer) {
                return response()->json([
                    'authenticated' => true,
                    'valid' => false,
                    'success' => false,
                    'mssg' => 'Customer not found.',
                ]);
            }
        
            $dataToUpdate = $request->except(['customer_id']);
        
            foreach ($dataToUpdate as $key => $value) {
                if ($value !== null && $value !== '') {
                    if ($key === 'email_addresses') {
                        $customer->$key = json_encode($value);
                    } else {
                        $customer->$key = $value;
                    }
                }
            }
        
            $customer->save();
        
            return response()->json([
                'authenticated' => true,
                'valid' => true,
                'success' => true,
                'mssg' => 'Customer data updated successfully.',
                'data' => $customer,
            ]);
        
    }
    
    public function create_invoice(Request $request)
    {
        
           $validatedData = Validator::make($request->all(), [
                    'customer_id'      => 'required|exists:customers,id',
                    'invoice_no'       => 'required|string|max:255',
                    'invoice_date'     => 'required|date',
                    'due_date'         => 'required|date',
                    'item_array'       => 'required|array', 
                    'total'            => 'required|numeric',
                ]);
                
            if ($validatedData->fails()) {
                return response()->json([
                    'authenticated' => true,
                    'valid' => false,
                    'mssg' => $validatedData->errors()->all(),
                ]);
            }
    
             $data = new Invoice;
             $data->customer_id = $request->customer_id;
             $data->invoice_no = $request->invoice_no;
             $data->invoice_date = $request->invoice_date;
             $data->due_date = $request->due_date;
             $data->item_array = json_encode($request->item_array);
             $data->total = $request->total;
             $data->save();
             
              return response()->json([
                    'authenticated' => true,
                    'valid' => true,
                    'success' => true,
                    'mssg' => 'Invoice Generated succssfully',
                    'data' => $data,
                ]);
             
        
    }
    
    public function getall_invoice(Request $request)
    {
            $data = Invoice::all();
            if ($data) {
                return response()->json([
                    'authenticated' => true,
                    'valid' => false,
                    'success' => true,
                    'data' =>$data,
                ]);
            }else{
                   return response()->json([
                    'authenticated' => true,
                    'valid' => false,
                    'success' => false,
                    'mssg' =>"No Data found",
                ]);
            } 
    }
    
    public function delete_invoice(Request $request)
    {
        
         $auto_generated_id = $request->input('auto_generated_id');
         $validatedData = Validator::make($request->all(), [
                'auto_generated_id'=> 'required|exists:invoices,id',
            ]);
        
            if ($validatedData->fails()) {
                return response()->json([
                    'authenticated' => true,
                    'valid' => false,
                    'mssg' => $validatedData->errors()->all(),
                ]);
            }
            
         $invoice_data = Invoice::find($auto_generated_id);
         if($invoice_data){
             $invoice_data->delete();
              return response()->json([
                    'authenticated' => true,
                    'valid' => false,
                    'success' => true,
                    'mssg' =>"Deleted successfully",
                ]);
         }
         
           return response()->json([
                'authenticated' => true,
                'valid' => false,
                'success' => false,
                'mssg' =>"No Data found",
            ]);
    }
    
    public function update_invoice(Request $request)
    {
         $validatedData = Validator::make($request->all(), [
               'auto_generated_id' => 'required|exists:invoices,id',
                // 'customer_id'       => 'nullable',
                'invoice_no'        => 'nullable|string|max:255',
                'invoice_date'      => 'nullable|date',
                'due_date'          => 'nullable|date',
                'item_array'        => 'nullable|array',
                'total'             => 'nullable|numeric',
            ]);
                
            if ($validatedData->fails()) {
                return response()->json([
                    'authenticated' => true,
                    'valid' => false,
                    'mssg' => $validatedData->errors()->all(),
                ]);
            }
              
              $invoice_data = Invoice::find($request->auto_generated_id);
            if ($invoice_data) {
                foreach ($request->all() as $key => $value) {
                    if (in_array($key, ['invoice_no', 'invoice_date', 'due_date', 'item_array', 'total']) && $value !== null && $value !== '') {
                        $invoice_data->$key = $key === 'item_array' ? json_encode($value) : $value;
                    }
                }
        
                $invoice_data->save();
                return response()->json([
                    'authenticated' => true,
                    'valid' => true,
                    'success' => true,
                    'mssg' => 'Invoice updated successfully',
                    'data' => $invoice_data,
                ]);
            }
        
            return response()->json([
                'authenticated' => true,
                'valid' => false,
                'success' => false,
                'mssg' => 'Invoice not found',
            ]);
    }
    
}
