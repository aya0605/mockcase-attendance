<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Application;
use Carbon\Carbon; 

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

         // 承認待ちの申請を取得
        $pendingApplications = Application::with('user', 'attendance')
                                ->where('user_id', $user->id)
                                ->where('status', 'pending')
                                ->orderBy('created_at', 'desc')
                                ->get();

        // 承認済みと却下の申請を取得 (まとめて「承認済み」タブで表示)
        $approvedApplications = Application::with('user', 'attendance')
                                ->where('user_id', $user->id)
                                ->whereIn('status', ['approved', 'rejected']) // 承認済みと却下を対象
                                ->orderBy('updated_at', 'desc')
                                ->get();

        // 現在のタブ情報をURLクエリパラメータから取得
        $currentTab = $request->query('tab', 'pending'); // デフォルトは'pending'

        return view('applications.index', compact('pendingApplications', 'approvedApplications', 'currentTab'));

    }

    public function approve(Request $request, Application $application)
    {
        // 管理者権限のチェック（例）
        if (Auth::user()->role !== 1) { // 'admin'から1に変更
            abort(403, 'Unauthorized action.');
        }

        $application->status = 'approved';
        $application->save();

        // name属性を使わずに直接URLへリダイレクト
        return redirect('/applications?tab=approved')->with('success', '申請が承認されました。');
    }

    public function reject(Request $request, Application $application)
    {
        // 管理者権限のチェック（例）
        if (Auth::user()->role !== 1) { // 'admin'から1に変更
            abort(403, 'Unauthorized action.');
        }

        $application->status = 'rejected';
        $application->save();

        // name属性を使わずに直接URLへリダイレクト
        return redirect('/applications?tab=approved')->with('success', '申請が却下されました。');
    }
}
