<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;
use Illuminate\Support\Facades\Auth;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        // 承認待ちの申請を取得
        $pendingApplications = Application::where('user_id', $userId)
                                         ->where('status', 'pending')
                                         ->with('attendance')
                                         ->orderBy('created_at', 'desc')
                                         ->get();

        // 承認済みと却下の申請を取得
        $approvedApplications = Application::where('user_id', $userId)
                                          ->whereIn('status', ['approved', 'rejected'])
                                          ->with('attendance')
                                          ->orderBy('updated_at', 'desc')
                                          ->get();

        // URLクエリパラメータから現在のタブを取得
        $currentTab = $request->input('tab', 'pending');
        
        // どちらのタブも空の場合、デフォルトを決定
        if ($pendingApplications->isEmpty() && !$approvedApplications->isEmpty()) {
            $currentTab = 'approved';
        }

        return view('users.application', compact('pendingApplications', 'approvedApplications', 'currentTab'));
    }

        public function show($applicationId)
    {
        // 申請IDとユーザーIDで申請情報を取得
        $application = Application::where('id', $applicationId)
                                ->where('user_id', Auth::id())
                                ->with('attendance')
                                ->firstOrFail();

        // 勤怠情報を取得
        $attendance = $application->attendance;

        // 申請のステータスが「pending」なら true
        $isPendingApplication = ($application->status === 'pending');

        // attendance_detail.blade.php にデータを渡して表示
        return view('users.attendance_detail', compact('attendance', 'isPendingApplication'));
    }
}