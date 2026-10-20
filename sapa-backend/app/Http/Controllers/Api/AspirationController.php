<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreAspirationRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use Illuminate\Http\Request;
use App\Services\NotificationService;

class AspirationController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $query = Report::query()
            ->ofType('aspiration')
            ->whereHas('aspirationDetail', fn($q) => $q->where('is_public', true))
            ->with(['reporter:id,name,avatar', 'reporter.schoolClass:id,name', 'aspirationDetail'])
            ->withCount('comments');

        // Filter "Didukung" — hanya aspirasi yang di-vote user ini
        if ($request->boolean('liked_only')) {
            $query->whereHas('votes', fn($q) => $q->where('user_id', $userId));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort
        if ($request->sort === 'top') {
            $query->join('aspiration_report_details', 'reports.id', '=', 'aspiration_report_details.report_id')
                ->orderByDesc('aspiration_report_details.upvotes_count')
                ->select('reports.*');
        } else {
            $query->latest();
        }

        $aspirations = $query->paginate(20);

        $reportIds = $aspirations->pluck('id');
        $likedIds = \App\Models\AspirationVote::where('user_id', $userId)
            ->whereIn('report_id', $reportIds)
            ->pluck('report_id')
            ->flip();

        $aspirations->getCollection()->transform(function ($report) use ($likedIds) {
            $resource = (new ReportResource($report))->resolve();
            $resource['is_liked'] = $likedIds->has($report->id);
            return $resource;
        });

        return response()->json($aspirations);
    }

    public function store(StoreAspirationRequest $request, NotificationService $notificationService)
    {
        $validated = $request->validated();
        $user = $request->user();

        $report = Report::create([
            'reporter_id' => $validated['is_anonymous'] ? null : $user->id,
            'type' => 'aspiration',
            'title' => $validated['title'],
            'description' => $validated['description'],
            'is_anonymous' => $validated['is_anonymous'],
            'status' => 'pending',
        ]);

        $report->aspirationDetail()->create([
            'category' => $validated['category'],
            'is_public' => $validated['is_public'],
        ]);

        $report->statusLogs()->create([
            'old_status' => null,
            'new_status' => 'pending',
            'changed_by' => $user->id,
            'note' => 'Aspirasi diajukan.',
        ]);

        $notificationService->notifyAdminsNewReport($report);

        return response()->json([
            'message' => 'Aspirasi berhasil diajukan.',
            'report' => $report->load('aspirationDetail'),
        ], 201);
    }

    public function upvote(Request $request, Report $report)
    {
        if ($report->type !== 'aspiration') {
            return response()->json(['message' => 'Laporan ini bukan aspirasi.'], 422);
        }

        $userId = $request->user()->id;
        $existingVote = $report->votes()->where('user_id', $userId)->first();

        if ($existingVote) {
            $existingVote->delete();
            $report->aspirationDetail()->decrement('upvotes_count');
            $isLiked = false;
        } else {
            $report->votes()->create(['user_id' => $userId]);
            $report->aspirationDetail()->increment('upvotes_count');
            $isLiked = true;
        }

        return response()->json([
            'message' => $isLiked ? 'Dukungan ditambahkan.' : 'Dukungan dibatalkan.',
            'is_liked' => $isLiked,
            'upvotes_count' => $report->aspirationDetail->fresh()->upvotes_count,
        ]);
    }
}
