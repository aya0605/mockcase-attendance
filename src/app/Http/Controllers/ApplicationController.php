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
        // 承認待ちの申請を取得
        $pendingApplications = Application::where('status', 'pending')
                                ->with('user', 'attendance')
                                ->orderBy('created_at', 'desc')
                                ->get();

        // 承認済みおよび却下の申請を取得
        $approvedApplications = Application::whereIn('status', ['approved', 'rejected'])
                                ->with('user', 'attendance')
                                ->orderBy('created_at', 'desc')
                                ->get();

        // 現在のタブを決定（デフォルトは'pending'）
        $currentTab = $request->input('tab', 'pending');

        // ビューパスを 'applications.application_list' に修正
        return view('applications.application_list', [
            'pendingApplications' => $pendingApplications,
            'approvedApplications' => $approvedApplications,
            'currentTab' => $currentTab,
        ]);
    }

    // approveとrejectのメソッドは省略
    public function approve(Application $application)
    {
        $application->status = 'approved';
        $application->save();

        return response()->json([
            'success' => true,
            'message' => '申請を承認しました。',
            'application' => $application->load('user', 'attendance'),
        ]);
    }

    public function reject(Application $application, Request $request)
    {
        $application->status = 'rejected';
        $application->note = $request->input('reject_reason');
        $application->save();

        return response()->json([
            'success' => true,
            'message' => '申請を却下しました。',
            'application' => $application->load('user', 'attendance'),
        ]);
    }
}
