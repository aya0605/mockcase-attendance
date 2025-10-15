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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $attendanceData = Attendance::with('breaks', 'user')->find($attendanceId);

        if (!$attendanceData) {
            return redirect('/admin/dashboard')->with('error', '勤怠記録が見つかりませんでした。');
        }

        // ビューに勤怠データとユーザー情報を渡す
        return view('admin.attendance_detail', [
            'attendance' => $attendanceData,
            'user' => $attendanceData->user // ユーザー情報を取得して渡す
        ]);
    }

    public function update(Request $request, Attendance $attendance)
    {
        // 入力値のバリデーション
        $validatedData = $request->validate([
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after_or_equal:start_time',
            'break_start_1' => 'nullable|date_format:H:i',
            'break_end_1' => 'nullable|date_format:H:i|after_or_equal:break_start_1',
            'break_start_2' => 'nullable|date_format:H:i',
            'break_end_2' => 'nullable|date_format:H:i|after_or_equal:break_start_2',
            'note' => 'nullable|string|max:500',
        ]);

        $workDate = $attendance->work_date->toDateString();
        
        try {
            DB::beginTransaction();

            // 勤怠情報を更新
            $attendance->update([
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'note' => $request->note,
            ]);

            // 休憩時間を更新 (既存の休憩情報を取得)
            $breaks = $attendance->breaks()->orderBy('id')->get();

            // 休憩1の更新
            if (isset($validatedData['break_start_1']) || isset($validatedData['break_end_1'])) {
                $start1 = $request->break_start_1 ? $workDate . ' ' . $request->break_start_1 . ':00' : null;
                $end1 = $request->break_end_1 ? $workDate . ' ' . $request->break_end_1 . ':00' : null;
            
                if ($breaks->count() > 0) {
                    $breaks[0]->update([
                        'start_time' => $start1,
                        'end_time' => $end1,
                    ]);
                } else {
                    // 新規作成が必要な場合
                    $attendance->breaks()->create([
                        'start_time' => $start1,
                        'end_time' => $end1,
                    ]);
                }
            }

            // 休憩2の更新
            if (isset($validatedData['break_start_2']) || isset($validatedData['break_end_2'])) {
                $start2 = $request->break_start_2 ? $workDate . ' ' . $request->break_start_2 . ':00' : null;
                $end2 = $request->break_end_2 ? $workDate . ' ' . $request->break_end_2 . ':00' : null;

                if ($breaks->count() > 1) {
                    $breaks[1]->update([
                        'start_time' => $start2,
                        'end_time' => $end2,
                    ]);
                } else {
                    // 新規作成が必要な場合
                    $attendance->breaks()->create([
                        'start_time' => $start2,
                        'end_time' => $end2,
                    ]);
                }
            }
            
            DB::commit();

            return redirect()->back()->with('success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('勤怠更新エラー: ' . $e->getMessage());
            return redirect()->back()->withErrors('勤怠情報の更新中にエラーが発生しました。');
        }
    }
}
