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

        $pendingApplications = Application::where('user_id', $userId)
                                         ->where('status', 'pending')
                                         ->with('attendance')
                                         ->orderBy('created_at', 'desc')
                                         ->get();

        $approvedApplications = Application::where('user_id', $userId)
                                          ->whereIn('status', ['approved', 'rejected'])
                                          ->with('attendance')
                                          ->orderBy('updated_at', 'desc')
                                          ->get();

        $currentTab = $request->input('tab', 'pending');
        
        if ($pendingApplications->isEmpty() && !$approvedApplications->isEmpty()) {
            $currentTab = 'approved';
        }

        return view('users.application', compact('pendingApplications', 'approvedApplications', 'currentTab'));
    }

        public function show($applicationId)
    {
        $application = Application::where('id', $applicationId)
                                ->where('user_id', Auth::id())
                                ->with('attendance')
                                ->firstOrFail();

        $attendance = $application->attendance;

        $isPendingApplication = ($application->status === 'pending');

        return view('users.detail', compact('attendance', 'isPendingApplication'));
    }
}