<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\AssignAssets;
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

class EmployeeController extends Controller
{
    public function get_employee_profile(Request $request)
    {
    $employee_id = $request->input('employee_id');
    $validatedData = Validator::make($request->all(), [
        'employee_id' => 'required',
    ]);

    if ($validatedData->fails()) {
        $errorMessages = $validatedData->errors()->all();
        
        return response()->json([
            'authenticated' => true,
            'valid' => false,
            'mssg' => $errorMessages
        ]);
    }

    $emp_data = User::where('id', $employee_id)
        ->select(
            'users.id',
            'users.name',
            'users.employee_code',
            'users.email',
            'users.status',
            'users.dob',
            'users.doj',
            'users.department',
            'users.password',
            'users.mobile',
            'users.reporting_manager',
            'users.blood_group',
            'users.location',
            'users.biometrics',
            'users.designation',
            'users.notice_period_end_date',
            'users.experience',
            'users.image',
            'users.prohibition_end_date',
            'users.total_experience',
            'users.type'
        )  
        ->first();

        return response()->json([
            'authenticated' => true,
            'valid' => true,
            'success' => true,
            'data' => $emp_data
        ]);
    }

    public function get_employee_leave(Request $request)
    {

        $employee_id = $request->input('employee_id');
        $validatedData = Validator::make($request->all(), [
            'employee_id' => 'required|integer',
        ]);
        
       if ($validatedData->fails()) {
                return response()->json(['authenticated' =>true,'valid' => false, 'mssg' => $validatedData->errors()->all()]);
            }
        
         $leave_data = Leave::where('employee_id',$employee_id)->get();
         $startOfMonth = Carbon::now()->startOfMonth();
         $endOfMonth = Carbon::now()->endOfMonth();
         
         $employee_leave = DB::table('leave_allowances')
            ->where('employee_id',$employee_id)
            // ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->get();
            if($leave_data){
              return response()->json([
                 'authenticated' =>true,
                'valid' => true, 
                'success' => true,
                'leaves' =>$leave_data,
                'total_leaves' => $employee_leave
                
                ]); 
            }
            return response()->json([
                'valid' => true, 
                'success' => false,
                'mssg' => 'data not found'
            ]); 
   }
   
    public function get_employee_attendance(Request $request)
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
            ->select('attendance.id as auto_generated_id','attendance.employee_id','attendance.date', 'attendance.status as attendance_status','attendance.day','attendance.month','attendance.year','attendance.login_time','attendance.logout_time','attendance.login_image','attendance.logout_image','attendance.leave_type','users.name','users.status', 'users.employee_code', 'users.email', 'users.department', 'users.mobile', 'users.reporting_manager', 'users.designation', 'users.image') 
            // ->whereDate('attendance.created_at', $currentDate) 
             ->orderBy('attendance.date', 'desc')
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
    
    public function update_employee_image(Request $request)
    {
         $employee_id = $request->input('employee_id');
         $image = $request->input('image');
        
         $validatedData = Validator::make($request->all(), [
                'employee_id' => 'required',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
        
          if ($validatedData->fails()) {
    
                $errorMessages = $validatedData->errors()->all();
                
                return response()->json([ 'authenticated' => true,'valid' => false, 'mssg' => $errorMessages]);
            }
            
         $emp = User::find($employee_id);

        if (!$emp) {
            return response()->json(['message' => 'Employee not found'], 404);
        }
         if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image = $image->storeAs('images', $imageName, 'public');
             $emp->image = $imageName;
             $emp->save();
        }
        
         return response()->json([
            'authenticated' => true,
            'valid' => true,
            'success' => true,
            'mssg' => 'image updated successfully'
            ]);
    }
    
    public function get_employee_assets(Request $request)
    {
         $validatedData = Validator::make($request->all(), [
            'employee_id' => 'required|integer|exists:users,id',
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
        $data = Asset::where('employee_id', $employee_id)->get()->map(function($asset) {
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
    
    public function get_employee_salary(Request $request)
    {
         $employee_id = $request->input('employee_id');
         $validatedData = Validator::make($request->all(), [
                'employee_id' => 'required',
          ]);
        
          if ($validatedData->fails()) {
                $errorMessages = $validatedData->errors()->all();
                return response()->json(['authenticated' => true,'valid' => false, 'mssg' => $errorMessages]);
            }
        
         $data = DB::table('salary_calculation')
         ->join('users','users.id','=','salary_calculation.employee_id')
         ->select('salary_calculation.*','users.name')
         ->where('employee_id',$employee_id)->get();
         
         
         
         
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
   
    public function get_emp_assign_assets(Request $request)
    {
        
         $employee_id = $request->input('employee_id');
         $validatedData = Validator::make($request->all(), [
                'employee_id' => 'required',
          ]);
        
          if ($validatedData->fails()) {
                $errorMessages = $validatedData->errors()->all();
                return response()->json(['authenticated' => true,'valid' => false, 'mssg' => $errorMessages]);
            }
        
        $data = AssignAssets::join('users', 'assign_assets.employee_id', '=', 'users.id')
            ->select('assign_assets.*', 'users.name as employee_name', 'users.image as user_image')
            ->where('assign_assets.employee_id', $employee_id)
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
}
