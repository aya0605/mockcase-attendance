<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\Application; 
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use App\Http\Requests\AttendanceUpdateRequest;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $attendance = Attendance::where('user_id', $user->id)
                                ->whereDate('work_date', $today)
                                ->first();

        $status = '勤務外';
        if ($attendance) {
            if ($attendance->end_time) {
                $status = '退勤済';
            } elseif ($attendance->breaks()->whereNull('end_time')->exists()) {
                $status = '休憩中';
            } elseif ($attendance->start_time && !$attendance->end_time) {
                $status = '出勤中';
            }
        }
        return view('users.index', compact('attendance', 'status'));
    }

    public function startWork(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $existingAttendance = Attendance::where('user_id', $user->id)
                                        ->whereDate('work_date', $today)
                                        ->first();

        if ($existingAttendance && $existingAttendance->start_time) {
            if ($request->ajax()) {
                return response()->json([
                    'message' => '本日すでに出勤打刻済みです。',
                    'new_attendance_status' => '出勤中'
                ], 409);
            }
            return redirect()->back()->with('error', '本日すでに出勤打刻済みです。');
        }

        try {
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'work_date' => $today,
                'start_time' => Carbon::now(),
            ]);

            if ($request->ajax()) {
                return response()->json(['message' => '出勤しました！', 'new_attendance_status' => '出勤中']);
            }

            return redirect()->back()->with('success', '出勤しました！');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['message' => '出勤処理中にエラーが発生しました。' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', '出勤処理中にエラーが発生しました。');
        }
    }

    public function endWork(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
                                ->whereDate('work_date', $today)
                                ->whereNotNull('start_time')
                                ->whereNull('end_time')
                                ->first();

        if (!$attendance) {
            if ($request->ajax()) {
                return response()->json(['message' => 'まだ出勤していません、または既に退勤済みです。'], 409);
            }
            return redirect()->back()->with('error', 'まだ出勤していません、または既に退勤済みです。');
        }

        if ($attendance->breaks()->whereNull('end_time')->exists()) {
            if ($request->ajax()) {
                return response()->json(['message' => '休憩中です。休憩を終了してから退勤してください。'], 409);
            }
            return redirect()->back()->with('error', '休憩中です。休憩を終了してから退勤してください。');
        }

        try {
            $attendance->update([
                'end_time' => Carbon::now(),
            ]);

            if ($request->ajax()) {
                return response()->json(['message' => '退勤しました！', 'new_attendance_status' => '退勤済']);
            }
            return redirect()->back()->with('success', '退勤しました！');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['message' => '退勤処理中にエラーが発生しました。' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', '退勤処理中にエラーが発生しました。');
        }
    }

    public function startBreak(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
                                ->whereDate('work_date', $today)
                                ->whereNotNull('start_time')
                                ->whereNull('end_time')
                                ->first();

        if (!$attendance) {
            if ($request->ajax()) {
                return response()->json(['message' => '出勤していません。'], 409);
            }
            return redirect()->back()->with('error', '出勤していません。');
        }

        if ($attendance->breaks()->whereNull('end_time')->exists()) {
            if ($request->ajax()) {
                return response()->json(['message' => 'すでに休憩中です。'], 409);
            }
            return redirect()->back()->with('error', 'すでに休憩中です。');
        }

        try {
            $attendance->breaks()->create([
                'start_time' => Carbon::now(),
            ]);

            if ($request->ajax()) {
                return response()->json(['message' => '休憩を開始しました！', 'new_attendance_status' => '休憩中']);
            }
            return redirect()->back()->with('success', '休憩を開始しました！');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['message' => '休憩開始処理中にエラーが発生しました。' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', '休憩開始処理中にエラーが発生しました。');
        }
    }

    public function endBreak(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
                                ->whereDate('work_date', $today)
                                ->whereNotNull('start_time')
                                ->whereNull('end_time')
                                ->first();

        if (!$attendance) {
            if ($request->ajax()) {
                return response()->json(['message' => '出勤していません。'], 409);
            }
            return redirect()->back()->with('error', '出勤していません。');
        }

        $currentBreak = $attendance->breaks()->whereNull('end_time')->first();

        if (!$currentBreak) {
            if ($request->ajax()) {
                return response()->json(['message' => '現在、進行中の休憩がありません。'], 409);
            }
            return redirect()->back()->with('error', '現在、進行中の休憩がありません。');
        }

        try {
            $currentBreak->update([
                'end_time' => Carbon::now(),
            ]);

            if ($request->ajax()) {
                return response()->json(['message' => '休憩を終了しました！', 'new_attendance_status' => '出勤中']);
            }
            return redirect()->back()->with('success', '休憩を終了しました！');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['message' => '休憩終了処理中にエラーが発生しました。' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', '休憩終了処理中にエラーが発生しました。');
        }
    }

    public function list(Request $request)
    {
        $user = Auth::user();
        $currentMonth = $request->input('month') ? Carbon::parse($request->input('month')) : Carbon::now();

        $firstDayOfMonth = $currentMonth->copy()->startOfMonth()->toDateString();
        $lastDayOfMonth = $currentMonth->copy()->endOfMonth()->toDateString();

        $attendances = Attendance::where('user_id', $user->id)
                                ->whereBetween('work_date', [$firstDayOfMonth, $lastDayOfMonth])
                                ->with('breaks')
                                ->orderBy('work_date', 'asc')
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

        return view('users.list', [
            'attendanceData' => $attendanceData,
            'currentMonth' => $currentMonth->format('Y年m月'), 
            'prevMonth' => $currentMonth->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $currentMonth->copy()->addMonth()->format('Y-m'),
        ]);
    }

    public function detail($attendanceId)
    {
        $user = Auth::user();

        $attendance = Attendance::where('id', $attendanceId)
                                ->where('user_id', $user->id)
                                ->with('breaks')
                                ->firstOrFail();

        $isPendingApplication = false;
        
        if (Application::where('user_id', $user->id)->where('attendance_id', $attendanceId)->where('status', 'pending')->exists()) {
            $isPendingApplication = true;
        }

        return view('users.detail', compact('attendance', 'isPendingApplication'));

    }

    public function submitApplication(AttendanceUpdateRequest $request, $attendanceId)
    {
        $attendance = Attendance::findOrFail($attendanceId);

        $existingPendingApplication = Application::where('user_id', Auth::id())
            ->where('attendance_id', $attendanceId)
            ->where('status', 'pending')
            ->first();

        if ($existingPendingApplication) {
            return redirect()->back()->with('error', 'この勤怠には既に承認待ちの修正申請があります。');
        }

        $application = new Application();
        $application->user_id = Auth::id();
        $application->attendance_id = $attendance->id;
        $application->applied_start_time = $request->start_time;
        $application->applied_end_time = $request->end_time;
        $application->applied_breaks = json_encode($request->breaks);
        $application->note = $request->note;
        $application->status = 'pending';
        $application->save();

        return redirect('/applications')->with('success');

    }

}