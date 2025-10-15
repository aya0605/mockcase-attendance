<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        
        return view('admin.index', compact('users'));
    }

    public function showAttendanceList(Request $request, User $user)
    {
        $staff = $user;
        
        // リクエストから年月を取得、なければ今月
        $currentMonth = $request->input('date')
            ? \Carbon\Carbon::createFromFormat('Y-m', $request->input('date'))
            : \Carbon\Carbon::now();

        // 前月と翌月
        $prevMonth = $currentMonth->copy()->subMonth();
        $nextMonth = $currentMonth->copy()->addMonth();

        // ここを修正: 勤怠データと、関連する勤怠申請データを一緒に取得
        $attendances = Attendance::where('user_id', $staff->id)
            ->whereYear('work_date', $currentMonth->year)
            ->whereMonth('work_date', $currentMonth->month)
            ->with('application') // 関連するapplicationデータを取得
            ->orderBy('work_date', 'asc')
            ->get();
        
        // 日付をキーにした勤怠データに変換
        $attendanceMap = [];
        foreach ($attendances as $attendance) {
            $attendanceMap[$attendance->work_date->format('Y-m-d')] = $attendance;
        }

        // 月の日数を取得
        $daysInMonth = $currentMonth->daysInMonth;
        $attendanceList = [];
        $emptyDays = [];
        
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = $currentMonth->copy()->day($i)->format('Y-m-d');
            
            if (isset($attendanceMap[$date])) {
                $attendance = $attendanceMap[$date];
                $attendanceList[] = [
                    'work_date' => $attendance->work_date,
                    'start_time' => $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '',
                    'end_time' => $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '',
                    'total_break_time' => $attendance->total_break_time ? \Carbon\Carbon::parse($attendance->total_break_time)->format('H:i:s') : '00:00:00',
                    'total_work_time' => $attendance->total_work_time ? \Carbon\Carbon::parse($attendance->total_work_time)->format('H:i:s') : '00:00:00',
                    'attendance_id' => $attendance->id,
                    'application_id' => $attendance->application ? $attendance->application->id : null, // 申請IDを追加
                ];
            } else {
                $emptyDays[] = $date;
            }
        }
        
        return view('admin.attendance', compact('staff', 'attendanceList', 'prevMonth', 'nextMonth', 'currentMonth', 'emptyDays'));
    }

        public function showMonthlyAttendance(User $user, int $year, int $month)
    {
        // 指定された年月の勤怠データを取得
        $attendances = $user->attendances()
                            ->whereYear('work_date', $year)
                            ->whereMonth('work_date', $month)
                            ->get();

        return view('admin.users.monthly_attendance', compact('user', 'attendances', 'year', 'month'));
    }

        public function showAttendanceDetail(User $user, $attendanceId)
    {
        // 指定された勤怠IDのデータを取得
        $attendance = Attendance::with('application')->findOrFail($attendanceId);
        
        // 勤怠データのユーザーIDが、URLのユーザーIDと一致するか確認
        if ($attendance->user_id !== $user->id) {
            abort(404); // ユーザーと勤怠が一致しない場合は404エラー
        }

        return view('admin.attendance_detail', compact('user', 'attendance'));
    }
}
