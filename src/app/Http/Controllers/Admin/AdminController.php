<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminDetailRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;

class AdminController extends Controller
{
   public function handleLoginRedirect()
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->role === 1) {
                return redirect('/admin/dashboard');
            } else {
                return redirect('/attendance/index');
            }
        }
        
        return redirect('/login');
    }


    public function dashboard(Request $request)
    {
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
        $attendances = Attendance::with('user', 'breaks')
                                ->whereDate('work_date', $date->toDateString())
                                ->get();
        
        $attendanceData = [];
        foreach ($attendances as $attendance) {
            $totalBreakDuration = 0;
            foreach ($attendance->breaks as $break) {
                if ($break->start_time && $break->end_time) {
                    $totalBreakDuration += $break->start_time->diffInSeconds($break->end_time);
                }
            }
            
            $totalWorkDuration = 0;
            if ($attendance->start_time && $attendance->end_time) {
                $totalWorkDuration = $attendance->start_time->diffInSeconds($attendance->end_time) - $totalBreakDuration;
            }
            
            $attendanceData[] = [
                'user_name' => $attendance->user->name,
                'work_date' => $attendance->work_date->format('Y-m-d'),
                'start_time' => $attendance->start_time ? $attendance->start_time->format('H:i') : '-',
                'end_time' => $attendance->end_time ? $attendance->end_time->format('H:i') : '-',
                'total_break_time' => gmdate('H:i', $totalBreakDuration),
                'total_work_time' => gmdate('H:i', max(0, $totalWorkDuration)),
                'attendance_id' => $attendance->id,
            ];
        }

        $prevDay = $date->copy()->subDay()->format('Y-m-d');
        $nextDay = $date->copy()->addDay()->format('Y-m-d');

        return view('admin.dashboard', [
            'attendanceData' => $attendanceData,
            'currentDate' => $date->format('Y-m-d'),
            'prevDay' => $prevDay,
            'nextDay' => $nextDay,
        ]);
    }
    
    public function usersIndex()
    {
        $users = User::all();
        
        return view('admin.users.index', compact('users'));
    }

    public function usersMonthlyAttendances(Request $request, $id)
    {
         $staff = User::findOrFail($id); // 変数名を`staff`に変更
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();

        $attendances = Attendance::with('breaks')
                                 ->where('user_id', $staff->id) // `staff->id`に変更
                                 ->whereMonth('work_date', $date->month)
                                 ->whereYear('work_date', $date->year)
                                 ->get();
        
        $attendanceData = [];
        foreach ($attendances as $attendance) {
            $totalBreakDuration = 0;
            foreach ($attendance->breaks as $break) {
                if ($break->start_time && $break->end_time) {
                    $totalBreakDuration += $break->start_time->diffInSeconds($break->end_time);
                }
            }

            $totalWorkDuration = 0;
            if ($attendance->start_time && $attendance->end_time) {
                $totalWorkDuration = $attendance->start_time->diffInSeconds($attendance->end_time) - $totalBreakDuration;
            }

            $attendanceData[] = [
                'work_date' => $attendance->work_date->format('Y-m-d'),
                'start_time' => $attendance->start_time ? $attendance->start_time->format('H:i') : '-',
                'end_time' => $attendance->end_time ? $attendance->end_time->format('H:i') : '-',
                'total_break_time' => gmdate('H:i', $totalBreakDuration),
                'total_work_time' => gmdate('H:i', max(0, $totalWorkDuration)),
                'attendance_id' => $attendance->id,
            ];
        }

        $prevMonth = $date->copy()->subMonth(); 
        $nextMonth = $date->copy()->addMonth(); 

        $daysInMonth = $date->daysInMonth;
        $allDates = [];
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $currentDay = Carbon::createFromDate($date->year, $date->month, $i);
            $allDates[] = $currentDay->format('Y-m-d');
        }

        $recordedDates = collect($attendanceData)->pluck('work_date')->toArray();
        $emptyDays = array_diff($allDates, $recordedDates);

        return view('admin.users.attendance', [
            'staff' => $staff, 
            'attendanceList' => $attendanceData,
            'currentMonth' => $date, 
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'emptyDays' => $emptyDays, 
        ]);
    }
    
    public function detail($attendanceId)
    {
        $attendanceData = Attendance::with('breaks')->find($attendanceId);

        if (!$attendanceData) {
            return redirect('/admin/dashboard')->with('error', '勤怠記録が見つかりませんでした。');
        }

        return view('admin.detail', [
            'attendanceData' => $attendanceData
        ]);
    }

    public function update(AdminDetailRequest $request, $attendanceId)
    {
        $attendance = Attendance::find($attendanceId);
        if (!$attendance) {
            return redirect('/admin/dashboard')->with('error', '勤怠記録が見つかりませんでした。');
        }

        $attendance->start_time = $request->input('start_time');
        $attendance->end_time = $request->input('end_time');
        $attendance->note = $request->input('note');
        $attendance->save();

        if ($attendance->breaks->isNotEmpty()) {
            $break1 = $attendance->breaks->first();
            $break1->start_time = $request->input('break_start_1');
            $break1->end_time = $request->input('break_end_1');
            $break1->save();
        }

        if ($attendance->breaks->count() > 1) {
            $break2 = $attendance->breaks->skip(1)->first();
            $break2->start_time = $request->input('break_start_2');
            $break2->end_time = $request->input('break_end_2');
            $break2->save();
        }

        return redirect()->back()->with('success', '勤怠記録を更新しました。');
    }
}
