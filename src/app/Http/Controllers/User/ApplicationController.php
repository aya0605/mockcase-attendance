<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;
use Illuminate\Support\Facades\Auth;

class ApplicationController extends Controller
{
    /**
     * ユーザー自身の勤怠申請の一覧を表示します。
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $userId = Auth::id();
    
        // ログイン中のユーザーの申請一覧を取得
        $applications = Application::where('user_id', $userId)
                                   ->with('attendance')
                                   ->orderBy('created_at', 'desc')
                                   ->paginate(10);
    
        // ビューに $currentTab 変数を渡します。
        $currentTab = 'my_applications';
    
        // 'user.index' ビューにデータを渡して返す
        return view('user.index', compact('applications', 'currentTab'));
    }

} 
