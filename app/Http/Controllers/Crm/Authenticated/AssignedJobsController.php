<?php

namespace App\Http\Controllers\Crm\Authenticated;

use App\Http\Controllers\Controller;
use App\Models\AssignedJobs;
use Illuminate\Http\Request;

class AssignedJobsController extends Controller
{

    public function getAssignedJobsCounts(Request $request)
    {
        $user = $request->user();

        // Get assigned jobs for the user with job relationship
        $assignedJobsQuery = AssignedJobs::query();
        if ($user->role === 'administrator') {
        } elseif ($user->role === 'branch-manager' || $user->role === 'accountant') {
            $assignedJobsQuery->where('branch_id', $user->branch_id);
        } else {
            $assignedJobsQuery->where('assigned_to', $user->id);
        }

        // Get the counts
        $openCount = (clone $assignedJobsQuery)->where('status', 'open')->count();
        $closedCount = (clone $assignedJobsQuery)->where('status', 'closed')->count();
        $pendingCount = (clone $assignedJobsQuery)->where('status', 'pending')->count();
        $totalCount = $openCount + $closedCount + $pendingCount;

        return response()->json([
            'total' => $totalCount ?? 0,
            'closed' => $closedCount ?? 0,
            'open' => $openCount ?? 0,
            'pending' => $pendingCount ?? 0
        ]);
    }
    public function getAssignedJobs(Request $request)
    {
        $user = $request->user();
        $complaints = AssignedJobs::with('job');
        if ($user->role === 'administrator') {
        } elseif ($user->role === 'branch-manager') {
            $complaints->where('branch_id', $request->branch_id);
        } elseif ($user->role === 'accountant') {
            $complaints->where('branch_id', $user->branch_id); // Use user's branch_id instead of request
        } else {
            $complaints->where('assigned_to', $user->id);
        }
        if ($request->status == 'total') {
            $complaints->where('status', 'open')->orWhere('status', 'closed');
        } else {
            $complaints->where('status', $request->status);
        }
        $complaints = $complaints->get();
        return response()->json($complaints);
    }
    public function getAssignedJob(Request $request, $id)
    {
        $complaint = AssignedJobs::with([
            'job' => function ($query) {
                $query->with(['brand:id,name', 'branch:id,name']);
            },
            'assignedBy:id,full_name',
            'assignedTo:id,full_name', 
            'branch:id,name'
        ])
        ->where('status', '!=', 'rejected')
        ->find($id);

        return response()->json($complaint);
    }
    public function updateAssignedJobStatus(Request $request, $id)
    {
        $complaint = AssignedJobs::find($id);
        $complaint->status = $request->status;
        $complaint->remarks = $request->remarks;
        $complaint->save();
        return response()->json($complaint);
    }
}
