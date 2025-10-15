<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Application;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Log; 

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

        return view('admin.applications', compact('pendingApplications', 'approvedApplications', 'currentTab'));
    }

    public function approve(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $application = Application::findOrFail($id);
            $attendance = $application->attendance;

            if (!$attendance) {
                throw new \Exception('関連する勤怠データが見つかりません。');
            }

            // 1. 勤怠データの更新
            $attendance->start_time = $application->applied_start_time;
            $attendance->end_time = $application->applied_end_time;
            $attendance->save();

            // 2. 既存の休憩データを削除
            $attendance->breaks()->delete();

            // 3. 新しい休憩データを作成・保存
            $appliedBreaks = json_decode($application->applied_breaks, true); // JSON文字列をデコード

            Log::info('Applied Breaks:', ['data' => $appliedBreaks]);

            $newBreaks = [];
            if (is_array($appliedBreaks)) {
                foreach ($appliedBreaks as $break) {
                    if (isset($break['start_time']) && isset($break['end_time'])) {
                         $newBreaks[] = new \App\Models\AttendanceBreak([
                            'attendance_id' => $attendance->id,
                            'start_time' => $break['start_time'],
                            'end_time' => $break['end_time']
                        ]);
                    } else {
                        Log::error('Invalid break data format', ['break' => $break]);
                    }
                }
            }

            Log::info('New Breaks to be saved:', ['data' => $newBreaks]);

            if (!empty($newBreaks)) {
                $attendance->breaks()->saveMany($newBreaks);
            }

            // 4. 申請のステータスを更新
            $application->status = 'approved';
            $application->save();

            DB::commit();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Approval Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => '承認処理中にエラーが発生しました。'], 500);
        }
    }

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

    public function show(int $applicationId)
    {
        $application = Application::with(['user', 'attendance'])->findOrFail($applicationId);
        
        return view('admin.detail', compact('application'));

    }
}