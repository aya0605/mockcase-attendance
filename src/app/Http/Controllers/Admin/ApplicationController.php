<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Application;

class ApplicationController extends Controller
{
    public function index()
    {
        $pendingApplications = Application::where('status', 'pending')
                                ->with(['user', 'attendance'])
                                ->orderBy('created_at', 'desc')
                                ->get();
        $approvedApplications = Application::whereIn('status', ['approved', 'rejected'])
                                ->with(['user', 'attendance'])
                                ->orderBy('created_at', 'desc')
                                ->get();

        $currentTab = request()->query('tab', 'pending');

        return view('admin.applications.application_list', compact('pendingApplications', 'approvedApplications', 'currentTab'));
    }

    /**
     * Approve the application.
     *
     * @param \App\Models\Application $application
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(Application $application)
    {
        try {
            DB::beginTransaction();

            $application->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);

            $attendance = $application->attendance;
            if ($attendance) {
                $attendance->update([
                    'start_time' => $application->applied_start_time,
                    'end_time' => $application->applied_end_time,
                    'note' => $application->note
                ]);
            }
            
            $appliedBreaks = json_decode($application->applied_breaks, true);
            foreach ($appliedBreaks as $breakData) {
                if (isset($breakData['id'])) {
                    $break = $attendance->breaks()->find($breakData['id']);
                    if ($break) {
                        $break->update([
                            'start_time' => $breakData['start_time'],
                            'end_time' => $breakData['end_time']
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '申請を承認しました。',
                'application' => $application->load(['user', 'attendance'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Application approval failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => '承認処理中にエラーが発生しました。'], 500);
        }
    }

    /**
     * Reject the application.
     *
     * @param \App\Models\Application $application
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Application $application, Request $request)
    {
        try {
            DB::beginTransaction();
            
            $application->update([
                'status' => 'rejected',
                'reject_reason' => $request->input('reject_reason'),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '申請を却下しました。',
                'application' => $application->load(['user', 'attendance'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Application rejection failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => '却下処理中にエラーが発生しました。'], 500);
        }
    }

    /**
     * Display the specified application.
     *
     * @param int $applicationId
     * @return \Illuminate\View\View
     */
    public function show(int $applicationId)
    {
        $application = Application::with(['user', 'attendance'])->findOrFail($applicationId);
        
        return view('admin.applications.application_detail', compact('application'));
    }
}