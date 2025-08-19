<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\LeaveAllowance;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
// use App\Models\LeaveAllowance;
use Laravel\Passport\HasApiTokens;// Add this import
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Crypt;
// use App\Models\Passport\PersonalAccessClient;


class AuthenticationController extends Controller
{
    
  public function add_employee(Request $request)
  {
        $data = $request->all();
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'employee_code' => 'required|string|max:50|unique:users,employee_code',
            'experience' => 'required',
            'email' => 'required|email|unique:users,email|ends_with:cvinfotech.com',
            'status' => 'required',
            'department' => 'required|string|max:100',
            'password' => 'required',
            'mobile' => 'required|digits:10|unique:users,mobile',
            'reporting_manager' => 'required|string|max:255',
            'blood_group' => 'required',
            'location' => 'required|string|max:255',
            // 'biometrics' => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'dob' => 'required|date|before:today',
            'doj' => 'required|date',
            'image' => 'nullable|image|max:10048',
            'total_experience'=>'nullable',
            'type' => 'required|in:Admin,Employee',
            // 'emergency_number' => 'required|digits:10',
            // 'total_experience' =>'required',
            // 'type' =>  'required|in:Admin,Employee',
        ]);
      
    
        if ($validator->fails()) {
            return response()->json(['authenticated' =>true,'valid' => false, 'mssg' => $validator->errors()->all()]);
        }
    
        $validatedData = $validator->validated();
        $hashedPassword  = Crypt::encrypt($validatedData['password']);
     

        $imageName = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image = $image->storeAs('images', $imageName, 'public');
        }
        
            $timezone = 'Asia/Kolkata';  
            $dateOfJoining = Carbon::parse($validatedData['doj'], $timezone);
          
            // if($validatedData['status']=="Notice Period"){
                 $noticePeriodEndDate = Carbon::parse($request->notice_period_end_date, $timezone)->format('Y-m-d');
            // }else{
                $noticePeriodEndDate =null;
            // }
             if($validatedData['status']=="Prohibition"){
                 $threeMonthsLater = $dateOfJoining->addMonths(3)->format('Y-m-d');
            }else{
                $threeMonthsLater =null;
            }

             if($validatedData['experience']=="Fresher"){
                 $sixMonthsLater = $dateOfJoining->addMonths(6)->format('Y-m-d');
            }else{
                $sixMonthsLater =null;
            }
            
           if($validatedData['type']=="Admin"){
                 $priority_levels ='P2';
            }else{
                 $priority_levels ='P3';
            }
            
           
        $employee = User::create([
            'name' => $validatedData['name'],
            'employee_code' => $validatedData['employee_code'],
            'email' => $validatedData['email'],
            'status' => $validatedData['status'],
            'department' => $validatedData['department'],
            'password' => $hashedPassword,
            'notice_period_end_date' => $noticePeriodEndDate,
            'mobile' => $validatedData['mobile'],
            'dob' => $validatedData['dob'],
            'doj' => $validatedData['doj'],
            'reporting_manager' => $validatedData['reporting_manager'],
            'blood_group' => $validatedData['blood_group'],
            'location' => $validatedData['location'],
            // 'biometrics' => $validatedData['biometrics'],
            'designation' => $validatedData['designation'],
            'experience' => $validatedData['experience'],
            'image' => $imageName,
            'prohibition_end_date' => $threeMonthsLater,
            'type' => $validatedData['type'],
            'total_experience' => $request->input('total_experience'),
            'priority_levels'=>$priority_levels,
            'intern_end_date'=>$sixMonthsLater,
            // 'emergency_number'=>$validatedData['emergency_number'],
        ]);
       

         $employee_id = $employee->id;
         $this->storedata($validator, $employee_id);
        $check_data = LeaveAllowance::where('employee_id', $employee_id)
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->first();
    
        if (!$check_data) {
            $currentYear = Carbon::now()->year;
            $financialYearStart = Carbon::now()->year . '-04-01'; 
            $financialYearEnd = Carbon::create(Carbon::now()->year + 1, 3, 31); 
            
            if ($financialYearEnd->isLeapYear()) {
                $financialYearEnd = Carbon::create($financialYearEnd->year, 2, 29);
            }
                
            $currentDate = Carbon::now();
            $monthsLeft = $currentDate->diffInMonths($financialYearEnd);
    
            $collected_leave = 1;
            $total_leave = (int)$monthsLeft;
          
            if ($employee->status === "Prohibition") {
                $collected_leave = 0;
                // $total_leave = max(0, (int)$monthsLeft - 3);
                $total_leave = 0;
                $collected_leave = 1;
                $total_leave = (int)$monthsLeft;
            }
    
            $new_data = new LeaveAllowance();
            $new_data->employee_id = $employee_id;
            $new_data->total_leave_entitled = $total_leave;
            $new_data->leave_collected = $collected_leave;
            $new_data->financial_year_start = $financialYearStart;
            $new_data->financial_year_end = $financialYearEnd;
            $new_data->save();
        }
            $today = Carbon::now()->format('Y-m-d');

            $check_login_time_status = DB::table('attendance')
                ->where('employee_id', $employee_id)
                ->where('date', $today)
                ->first();
        
            if (!$check_login_time_status) {
                DB::table('attendance')->insert([
                    'employee_id' => $employee_id,
                    'date' => $today,
                    'login_time' =>null,
                    'logout_time' => null,
                    'day' => Carbon::now()->format('l'),
                    'status' => null,
                    'login_time_status' => 0,
                    'month' => Carbon::now()->format('F'), 
                    'year' => Carbon::now()->format('Y'), 
                ]);
            } else {
                if ($check_login_time_status->login_time_status == 0) {
                    DB::table('attendance')->where('employee_id', $employee_id)->update([
                        'login_time_status' => 0,
                        'login_time' =>null,
                    ]);
                }
            }
        return response()->json([
            'authenticated' =>true,
            'valid' => true,
            'success' => true,
            'data' => [$employee],
        ]);
}

  public function login(Request $request)
  {
        $email = $request->input('email');
        $password = $request->input('password');
        $type = $request->input('type');
        $fcm_token = $request->input('fcm_token');
        try {
            $validatedData = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'password' => 'required|min:5',
                'type' => 'required|in:Admin,Employee',
                
            ]);
        
              if ($validatedData->fails()) {
 
                    $errorMessages = $validatedData->errors()->all();
                    
                    return response()->json(['valid' => false, 'mssg' => $errorMessages]);
                }
                
             $today = Carbon::now()->format('Y-m-d'); 
             if ($type == 'Employee') {

                    $today = Carbon::now()->format('Y-m-d'); 
                    $user = User::where('email', $email)->where('type','Employee')->whereNull('deleted_at')->first();
                    if (!$user) {
                        return response()->json(['mssg' => 'Your are not a Employee']);
                    }
                    if($user->status=='Inactive'){
                         return response()->json([
                            'valid' =>true,
                            'success' => false,
                            'login_status' => false,
                            'mssg' => "You can't log in until admin not allow you."
                        ]);
                    }
                    
                    $check_leave = DB::table('attendance')
                        ->where('employee_id',$user->id)
                        ->where('status','Leave')
                        ->where('date',$today)
                        ->first();
                       
                    $currentTime = Carbon::now();
                    $check_status = DB::table('leaves')
                        ->where('employee_id', $user->id)
                        ->where('current_date',$today)
                        ->orderBy('id', 'desc') 
                        ->first();
                    
                      if ($check_status) {
                            $leaveStart = Carbon::parse($check_status->leave_start_date);
                            $leaveEnd = Carbon::parse($check_status->leave_end_date);
                                if ($currentTime->between($leaveStart, $leaveEnd)) {
                                     return response()->json([
                                    'valid' =>true,
                                    'success' => false,
                                    'mssg' => "You are on leave today, you can't log in until admin not allow you."
                                ]);
                            }
                        }

                    if($check_leave){
                        return response()->json([
                            'valid' =>true,
                            'success' => false,
                            'mssg' => "You are on leave today, you can't log in until admin not allow you."
                        ]);
                    }    
                    
                    $decrypted = Crypt::decrypt($user->password);
                    
                    if ($decrypted !== $password) {
                        return response()->json(['mssg' => 'Invalid password']);
                    }
        
                    if ($user->type !== $type) {
                        return response()->json(['mssg' => 'Type mismatch']);
                    }
        
                   $user->tokens()->each(function ($token) {
                        $token->revoke();
                    });
                    $token = $user->createToken('API Token')->accessToken;
            
                $user = User::where('email', $email)->where('type', $type)->first();
            
                if (!$user) {
                    return response()->json(['mssg' => 'User not found after update']);
                }
                $type = 'ON_Time';
                if (now()->format('H:i:s') > '09:15:00') {
                    $type = 'Late';
                }
              $startOfMonth = Carbon::now()->startOfMonth();
              $endOfMonth = Carbon::now()->endOfMonth();
            
              $check_login_time_status= DB::table('attendance')
                    ->where('employee_id',$user->id)
                    ->where('date', $today)
                    // ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->first();
        
                if(!$check_login_time_status){
                    
                     DB::table('attendance')->insert([
                            'employee_id' => $user->id,
                            'date' => Carbon::now()->format('Y-m-d'),
                            // 'login_time' => Carbon::now()->toTimeString(),
                            // 'logout_time' => null,
                            'day' => Carbon::now()->format('l'),
                            // 'status' => 'Present',
                            // 'login_time_status' => 1,
                            'type' => $type,
                            'month' => Carbon::now()->format('F'), 
                            'year' => Carbon::now()->format('Y'),
                        ]);
                    
                }else{
                    if($check_login_time_status->login_time_status==0){
                        // DB::table('attendance')->where('date',Carbon::now()->format('Y-m-d'))->where('employee_id', $user->id)->update([
                        //      'login_time_status' => 1,
                        //      'login_time' => Carbon::now()->toTimeString(),
                        //      'status' => 'Present',
                        //     ]);
                    }
                } 
                    
                if (!$check_login_time_status || $check_login_time_status->login_time_status != 1) {
                    // Insert new attendance record if no record exists or login_time_status is not 1
                 
                }
       
                return response()->json([
                    'valid' =>true,
                    'success' => true,
                    'token' => $token,
                    'login_status' => true,
                    'type' => $user->type,
                    'name' => $user->name,
                    'email' => $user->email,
                    'id' => $user->id,
                    'department' => $user->department,
                    'mssg' => 'Login successfully'
                ]);
                }else{
                    
                    $admin = User::where('email', $email)->where('type','Admin')->whereNull('deleted_at')->first();
         
                    if (!$admin) {
                           return response()->json(['mssg' => 'Your are not a Admin']);
                    }
                    if($admin->status=='Inactive'){
                         return response()->json([
                            'valid' =>true,
                            'success' => false,
                            'login_status' => false,
                            'mssg' => "You can't log in until admin not allow you."
                        ]);
                    }
                    $admin->fcm_token = $fcm_token;
                    $admin->save();

                    $decrypted = Crypt::decrypt($admin->password);
                    
                    if ($decrypted !== $password) {
                        return response()->json(['mssg' => 'Invalid password']);
                    }
            
                    if($admin->priority_levels !=='P1'){
                        
                      $check_leave = DB::table('attendance')
                            ->where('employee_id',$admin->id)
                            ->where('status','Leave')
                            ->where('date',$today)
                            ->first();
                            
                        if($check_leave){
                            return response()->json([
                                'valid' =>true,
                                'success' => false,
                                'mssg' => "You are on leave today, you can't log in until admin not allow you."
                            ]);
                        }    
                        
                     $currentTime = Carbon::now();
                     $check_status = DB::table('leaves')
                        ->where('employee_id', $admin->id)
                        ->orderBy('id', 'desc') 
                        ->first();
                    
                      if ($check_status) {
                            $leaveStart = Carbon::parse($check_status->leave_start_date);
                            $leaveEnd = Carbon::parse($check_status->leave_end_date);
                          
                                if ($currentTime->between($leaveStart, $leaveEnd)) {
                                     return response()->json([
                                    'valid' =>true,
                                    'success' => false,
                                    'mssg' => "You are on leave today, you can't log in until admin not allow you."
                                ]);
                            }
                        }

                      if (now()->format('H:i:s') > '09:15:00') {
                            $type = 'Late';
                        }
                      $startOfMonth = Carbon::now()->startOfMonth();
                      $endOfMonth = Carbon::now()->endOfMonth();
                      $today = Carbon::now()->format('Y-m-d'); 
                      $check_login_time_status= DB::table('attendance')
                            ->where('employee_id',$admin->id)
                            ->where('date', $today)
                            // ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                            ->first();
                            
                        if(!$check_login_time_status){
                            
                             DB::table('attendance')->insert([
                                    'employee_id' => $admin->id,
                                    'date' => Carbon::now()->format('Y-m-d'),
                                    // 'login_time' => Carbon::now()->toTimeString(),
                                    // 'logout_time' => null,
                                    'day' => Carbon::now()->format('l'),
                                    // 'status' => 'Present',
                                    // 'login_time_status' => 1,
                                    'type' => $type,
                                    'month' => Carbon::now()->format('F'), 
                                    'year' => Carbon::now()->format('Y'),
                                ]);
                            
                            }else{
                                if($check_login_time_status->login_time_status==0){
                                    // DB::table('attendance')->where('date',Carbon::now()->format('Y-m-d'))->where('employee_id', $admin->id)->update([
                                    //     //  'login_time_status' => 1,
                                    //     //  'login_time' => Carbon::now()->toTimeString(),
                                    //     //  'status' => 'Present',
                                    //     ]);
                                }
                            } 
                        }
                    $admin->tokens()->each(function ($token) {
                    $token->revoke();
                    });
                    $token = $admin->createToken('API Token')->accessToken;
                
                    return response()->json([
                        'valid' =>true,
                        'success' => true,
                        'token' => $token,
                        'login_status' => true,
                        'type' => $admin->type,
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'id' => $admin->id,
                        // 'department' => $user->department,
                        'mssg' => 'Login successfully'
                    ]);
            }
        
        } catch (ValidationException $e) {
            return response()->json(['valid' => false, 'errors' => $e->errors()]);
        }
        
    }
    
  public function storedata($validator,$employee_id)
  {
        
        $admind_data = Auth::user();
        $data = $validator->getData();
        $dataJson = json_encode($data);
        $processedData = [];
    
        foreach ($data as $key => $value) {
            $processedData[$key] = $value;
        }
      
      DB::table('admin_history')->insert([
          'employee_id'=>$employee_id,
          'updated_by' =>$admind_data->name,
          'data_type' => 'add_employee',
          'data'=>$dataJson
          ]);
    }
}
