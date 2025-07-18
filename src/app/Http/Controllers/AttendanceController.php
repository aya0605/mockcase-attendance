<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $attendance = Attendance::where('user_id', $user->id)
                                ->whereDate('work_date', $today)
                                ->first();

        // 勤怠ステータスを判別し、ビューに渡す
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
        return view('attendance.index', compact('attendance', 'status'));
    }

    public function startWork(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $existingAttendance = Attendance::where('user_id', $user->id)
                                        ->whereDate('work_date', $today)
                                        ->first();

        if ($existingAttendance && $existingAttendance->start_time) {
            // すでに出勤打刻がある場合
            if ($request->ajax()) {
                // Ajaxリクエストの場合はJSONでエラーを返す
                return response()->json([
                    'message' => '本日すでに出勤打刻済みです。',
                    'new_attendance_status' => '出勤中' 
                ], 409); 
            }
            return redirect()->back()->with('error', '本日すでに出勤打刻済みです。');
        }

         // 新しい出勤打刻を登録
        try {
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'work_date' => $today,
                'start_time' => Carbon::now(),
            ]);

            if ($request->ajax()) {
                // Ajaxリクエストの場合はJSONで成功を返す
                return response()->json(['message' => '出勤しました！', 'new_attendance_status' => '出勤中']);
            }

            return redirect()->back()->with('success', '出勤しました！');

        } catch (\Exception $e) {
            // データベースエラーなどが発生した場合
            if ($request->ajax()) {
                return response()->json(['message' => '出勤処理中にエラーが発生しました。' . $e->getMessage()], 500); // 500 Internal Server Error
            }
            return redirect()->back()->with('error', '出勤処理中にエラーが発生しました。');
        }
    }

    // 退勤
    public function endWork(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
                                ->whereDate('work_date', $today)
                                ->whereNotNull('start_time') // 出勤済みであること
                                ->whereNull('end_time')      // まだ退勤していないこと
                                ->first();

        if (!$attendance) {
            if ($request->ajax()) {
                return response()->json(['message' => 'まだ出勤していません、または既に退勤済みです。'], 409);
            }
            return redirect()->back()->with('error', 'まだ出勤していません、または既に退勤済みです。');
        }

        // 休憩中の場合はエラー
         if ($attendance->breaks()->whereNull('end_time')->exists()) { // ★ここを修正
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

    // 休憩開始
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

        // すでに進行中の休憩があるかチェック
        if ($attendance->breaks()->whereNull('end_time')->exists()) { // ★ここを修正
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

    // 休憩終了
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

        // 進行中の休憩レコードを取得
        $currentBreak = $attendance->breaks()->whereNull('end_time')->first(); // 

        if (!$currentBreak) {
            if ($request->ajax()) {
                return response()->json(['message' => '現在、進行中の休憩がありません。'], 409);
            }
            return redirect()->back()->with('error', '現在、進行中の休憩がありません。');
        }

        try {
            // 進行中の休憩レコードを更新
            $currentBreak->update([
                'end_time' => Carbon::now(),
            ]);


            if ($request->ajax()) {
                return response()->json(['message' => '休憩を終了しました！', 'new_attendance_status' => '出勤中']); // 休憩終了後は「出勤中」に戻る
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

        // 選択された月の初日と終日を取得
        $firstDayOfMonth = $currentMonth->startOfMonth()->toDateString();
        $lastDayOfMonth = $currentMonth->endOfMonth()->toDateString();

        // 当月の勤怠情報を取得
        $attendances = Attendance::where('user_id', $user->id)
                                ->whereBetween('work_date', [$firstDayOfMonth, $lastDayOfMonth])
                                ->with('breaks') // 関連する休憩情報もロード
                                ->orderBy('work_date', 'asc')
                                ->get();

        // 勤怠データの整形（表示用に合計休憩時間などを計算）
        $attendanceData = [];
        foreach ($attendances as $attendance) {
            $totalBreakDuration = 0;
            foreach ($attendance->breaks as $break) {
                if ($break->start_time && $break->end_time) {
                    $totalBreakDuration += $break->start_time->diffInSeconds($break->end_time);
                }
            }

            // 総勤務時間の計算
            $totalWorkDuration = 0;
            if ($attendance->start_time && $attendance->end_time) {
                $totalWorkDuration = $attendance->start_time->diffInSeconds($attendance->end_time) - $totalBreakDuration;
            }

            $attendanceData[] = [
                'work_date' => $attendance->work_date->format('Y-m-d'),
                'start_time' => $attendance->start_time ? $attendance->start_time->format('H:i') : '-',
                'end_time' => $attendance->end_time ? $attendance->end_time->format('H:i') : '-',
                'total_break_time' => gmdate('H:i', $totalBreakDuration), // 秒を時:分に変換
                'total_work_time' => gmdate('H:i', max(0, $totalWorkDuration)), // 秒を時:分に変換 (マイナスにならないように)
                'attendance_id' => $attendance->id, // 詳細画面へのリンク用にIDを渡す
            ];
        }

        return view('attendance.list', [
            'attendanceData' => $attendanceData,
            'currentMonth' => $currentMonth->format('Y年m月'), // 表示用
            'prevMonth' => $currentMonth->copy()->subMonth()->format('Y-m'), // 前月へのリンク用
            'nextMonth' => $currentMonth->copy()->addMonth()->format('Y-m'), // 翌月へのリンク用
        ]);
    }
}

