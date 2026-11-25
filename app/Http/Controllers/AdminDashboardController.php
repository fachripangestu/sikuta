<?php

namespace App\Http\Controllers;

use App\Models\Tamu;
use App\Models\User;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly AdminNotificationService $notificationService
    ) {
    }

    public function index(Request $request): RedirectResponse
    {
        $adminRole = (string) $request->session()->get('admin_role');

        return redirect()->route($this->resolveDashboardRoute($adminRole));
    }

    public function dataEntry(Request $request): View
    {
        return $this->renderDashboard($request, 'data_entry');
    }

    public function sekretarisPimpinan(Request $request): View
    {
        return $this->renderDashboard($request, 'sekretaris_pimpinan');
    }

    public function bidangTujuan(Request $request): View
    {
        return $this->renderDashboard($request, 'bidang_tujuan');
    }

    public function subbagUmum(Request $request): View
    {
        return $this->renderDashboard($request, 'subbag_umum');
    }

    public function notificationsLive(Request $request): JsonResponse
    {
        $adminRole = (string) $request->session()->get('admin_role');
        $adminUserId = (int) $request->session()->get('admin_user_id');

        return response()->json($this->notificationService->buildPayload($adminRole, $adminUserId));
    }

    private function renderDashboard(Request $request, string $adminRole): View
    {
        $adminUserName = (string) $request->session()->get('admin_user_name');
        $adminUserId = (int) $request->session()->get('admin_user_id');
        $operatorUsers = collect();
        $adminUser = null;

        try {
            $totalKunjungan = Tamu::count();
            $adminUser = $adminUserId > 0 ? User::find($adminUserId) : null;
            $queueKunjunganQuery = Tamu::query()->where('workflow_step', $adminRole);
            if ($adminRole === 'bidang_tujuan' && $adminUserId > 0) {
                $queueKunjunganQuery->where('assigned_operator_id', $adminUserId);
            }

            $queueKunjungan = $queueKunjunganQuery->orderByDesc('updated_at')->limit(30)->get();
            $recentKunjungan = Tamu::orderByDesc('updated_at')->limit(20)->get();
            $notificationSnapshot = $this->notificationService->buildPayload($adminRole, $adminUserId);
            $operatorUsers = User::query()
                ->where('role', 'bidang_tujuan')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'username']);
            $dbDownAdmin = false;
        } catch (\Throwable $e) {
            Log::error('Gagal membuka dashboard admin karena koneksi database.', ['message' => $e->getMessage()]);
            $totalKunjungan = 0;
            $queueKunjungan = collect();
            $recentKunjungan = collect();
            $notificationSnapshot = $this->notificationService->buildPayload($adminRole, $adminUserId);
            $dbDownAdmin = true;
        }

        $roleLabels = $this->notificationService->roleLabels();
        $workflowLabels = $this->notificationService->workflowLabels();

        return view('kunjungan', compact(
            'totalKunjungan',
            'queueKunjungan',
            'recentKunjungan',
            'notificationSnapshot',
            'adminRole',
            'adminUserName',
            'adminUserId',
            'adminUser',
            'roleLabels',
            'workflowLabels',
            'operatorUsers',
            'dbDownAdmin'
        ));
    }

    private function resolveDashboardRoute(?string $role): string
    {
        return match ($role) {
            'admin' => 'admin.accounts',
            'data_entry' => 'admin.dashboard.data_entry',
            'sekretaris_pimpinan' => 'admin.dashboard.sekretaris_pimpinan',
            'bidang_tujuan' => 'admin.dashboard.bidang_tujuan',
            'subbag_umum' => 'admin.dashboard.subbag_umum',
            default => 'admin.dashboard',
        };
    }
}
