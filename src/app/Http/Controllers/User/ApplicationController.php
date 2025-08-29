<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;
use Illuminate\Support\Facades\Auth;

class ApplicationController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
    
        $applications = Application::where('user_id', $userId)
                                   ->with('attendance')
                                   ->orderBy('created_at', 'desc')
                                   ->paginate(10);
    
        $currentTab = 'my_applications';
    
        return view('user.index', compact('applications', 'currentTab'));
    }

} 
