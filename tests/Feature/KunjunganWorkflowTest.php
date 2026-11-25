<?php

use App\Models\Tamu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function adminSession(User $user): array
{
    return [
        'admin_authenticated' => true,
        'admin_user_id' => $user->id,
        'admin_user_name' => $user->name,
        'admin_role' => $user->role,
    ];
}

function createTamuForWorkflow(array $override = []): Tamu
{
    return Tamu::create(array_merge([
        'nama' => 'Budi Santoso',
        'nomor_surat' => '001/ST-BJ/2026',
        'instansi' => 'PT Maju Jaya',
        'instansi_provinsi' => 'Jawa Barat',
        'instansi_kabupaten_kota' => 'Bandung',
        'jabatan' => 'PIC Kunjungan',
        'telepon' => '081234567890',
        'tujuan' => 'Audiensi',
        'tanggal_usulan' => '2026-05-10',
        'jumlah_peserta' => 7,
        'keperluan' => 'Pembahasan kerja sama',
        'status' => 'menunggu',
        'workflow_step' => 'data_entry',
    ], $override));
}

test('guest dapat mengirim permohonan kunjungan dengan field alur baru', function () {
    $response = $this->post(route('guest.form.submit'), [
        'nama' => 'Sari Lestari',
        'nomor_surat' => '123/SC-KT/2026',
        'instansi' => 'Kementerian Contoh',
        'instansi_provinsi' => 'DKI Jakarta',
        'instansi_kabupaten_kota' => 'Jakarta Pusat',
        'jabatan' => 'Koordinator',
        'telepon' => '081122334455',
        'tujuan' => 'Studi tiru',
        'tanggal_usulan' => '2026-05-12',
        'jumlah_peserta' => 12,
        'keperluan' => 'Diskusi proses bisnis',
        'lampiran' => UploadedFile::fake()->create('surat-permohonan.pdf', 128, 'application/pdf'),
    ]);

    $response->assertRedirect(route('guest.form'));
    $response->assertSessionHas('guest_success');

    $this->assertDatabaseHas('data_kunjungan', [
        'nama' => 'Sari Lestari',
        'nomor_surat' => '123/SC-KT/2026',
        'instansi_provinsi' => 'DKI Jakarta',
        'instansi_kabupaten_kota' => 'Jakarta Pusat',
        'status' => 'menunggu',
        'workflow_step' => 'data_entry',
        'jumlah_peserta' => 12,
    ]);

    $saved = Tamu::query()->where('nama', 'Sari Lestari')->first();
    expect($saved)->not->toBeNull();
    expect(optional($saved->tanggal_usulan)->format('Y-m-d'))->toBe('2026-05-12');
});

test('data entry hanya dapat melakukan verifikasi awal dan meneruskan ke sekretaris pimpinan', function () {
    $dataEntry = User::factory()->create([
        'role' => 'data_entry',
        'name' => 'Admin Data Entry',
    ]);
    $tamu = createTamuForWorkflow();

    $response = $this
        ->withSession(adminSession($dataEntry))
        ->post(route('admin.kunjungan.verify', $tamu), [
            'action' => 'initial_review',
            'note' => 'Data sudah lengkap',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('admin_notice');

    $tamu->refresh();
    expect($tamu->status)->toBe('diproses');
    expect($tamu->workflow_step)->toBe('sekretaris_pimpinan');
    expect($tamu->entry_verified_by)->toBe($dataEntry->id);
    expect($tamu->entry_verified_at)->not->toBeNull();
});

test('sekretaris pimpinan dapat menerima dan meneruskan ke operator bidang wilayah', function () {
    $sekretaris = User::factory()->create([
        'role' => 'sekretaris_pimpinan',
        'name' => 'Sekretaris Pimpinan',
    ]);
    $operator = User::factory()->create([
        'role' => 'bidang_tujuan',
        'name' => 'Operator Wilayah 1',
    ]);
    $tamu = createTamuForWorkflow([
        'status' => 'diproses',
        'workflow_step' => 'sekretaris_pimpinan',
    ]);

    $response = $this
        ->withSession(adminSession($sekretaris))
        ->post(route('admin.kunjungan.verify', $tamu), [
            'action' => 'approve',
            'note' => 'Silakan lanjutkan ke operator',
            'operator_user_id' => $operator->id,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('admin_notice');

    $tamu->refresh();
    expect($tamu->status)->toBe('diterima');
    expect($tamu->workflow_step)->toBe('bidang_tujuan');
    expect($tamu->assigned_operator_id)->toBe($operator->id);
    expect($tamu->assistant_verified_by)->toBe($sekretaris->id);
    expect($tamu->assistant_verified_at)->not->toBeNull();
});

test('sekretaris pimpinan dapat menunda dengan keterangan penundaan', function () {
    $sekretaris = User::factory()->create([
        'role' => 'sekretaris_pimpinan',
        'name' => 'Sekretaris Pimpinan',
    ]);
    $tamu = createTamuForWorkflow([
        'status' => 'diproses',
        'workflow_step' => 'sekretaris_pimpinan',
        'reschedule_at' => '2026-05-20',
    ]);

    $response = $this
        ->withSession(adminSession($sekretaris))
        ->post(route('admin.kunjungan.verify', $tamu), [
            'action' => 'reschedule',
            'note' => 'Pimpinan sedang dinas luar, mohon ajukan ulang jadwal.',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('admin_notice');

    $tamu->refresh();
    expect($tamu->status)->toBe('ditunda');
    expect($tamu->workflow_step)->toBe('subbag_umum');
    expect($tamu->verification_note)->toBe('Pimpinan sedang dinas luar, mohon ajukan ulang jadwal.');
    expect($tamu->reschedule_at)->toBeNull();
});

test('operator bidang wilayah dapat mengonfirmasi kunjungan dan meneruskan ke subbagian umum', function () {
    $operator = User::factory()->create([
        'role' => 'bidang_tujuan',
        'name' => 'Operator Bidang',
    ]);
    $tamu = createTamuForWorkflow([
        'status' => 'diterima',
        'workflow_step' => 'bidang_tujuan',
        'assigned_operator_id' => $operator->id,
    ]);

    $response = $this
        ->withSession(adminSession($operator))
        ->post(route('admin.kunjungan.verify', $tamu), [
            'action' => 'confirm_visit',
            'note' => 'Kunjungan siap dilaksanakan',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('admin_notice');

    $tamu->refresh();
    expect($tamu->status)->toBe('terkonfirmasi');
    expect($tamu->workflow_step)->toBe('subbag_umum');
    expect($tamu->bidang_verified_by)->toBe($operator->id);
    expect($tamu->bidang_verified_at)->not->toBeNull();
});

test('operator bidang wilayah tidak dapat mengonfirmasi kunjungan jika belum dialokasikan sekretaris pimpinan', function () {
    $operator = User::factory()->create([
        'role' => 'bidang_tujuan',
        'name' => 'Operator Bidang',
    ]);
    $tamu = createTamuForWorkflow([
        'status' => 'diterima',
        'workflow_step' => 'bidang_tujuan',
        'assigned_operator_id' => null,
    ]);

    $response = $this
        ->withSession(adminSession($operator))
        ->post(route('admin.kunjungan.verify', $tamu), [
            'action' => 'confirm_visit',
            'note' => 'Coba konfirmasi',
        ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('verify');

    $tamu->refresh();
    expect($tamu->status)->toBe('diterima');
    expect($tamu->workflow_step)->toBe('bidang_tujuan');
    expect($tamu->bidang_verified_by)->toBeNull();
    expect($tamu->bidang_verified_at)->toBeNull();
});

test('subbagian umum dapat upload dokumen balasan setelah disetujui operator bidang', function () {
    Storage::fake('public');

    $subbagUmum = User::factory()->create([
        'role' => 'subbag_umum',
        'name' => 'Subbagian Umum',
    ]);
    $operator = User::factory()->create([
        'role' => 'bidang_tujuan',
        'name' => 'Operator Bidang',
    ]);
    $tamu = createTamuForWorkflow([
        'status' => 'terkonfirmasi',
        'workflow_step' => 'subbag_umum',
        'bidang_verified_by' => $operator->id,
        'bidang_verified_at' => now(),
    ]);

    $response = $this
        ->withSession(adminSession($subbagUmum))
        ->post(route('admin.kunjungan.upload_balasan', $tamu), [
            'balasan_dokumen' => UploadedFile::fake()->create('balasan-kunjungan.pdf', 120, 'application/pdf'),
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('admin_notice');

    $tamu->refresh();
    expect($tamu->balasan_path)->not->toBeNull();
    expect($tamu->balasan_nama)->toBe('balasan-kunjungan.pdf');
    expect($tamu->balasan_mime)->toContain('pdf');
    expect($tamu->balasan_uploaded_by)->toBe($subbagUmum->id);
    expect($tamu->balasan_uploaded_at)->not->toBeNull();
    expect($tamu->status)->toBe('selesai');
    expect($tamu->workflow_step)->toBe('selesai');
});

test('subbagian umum dapat upload dokumen balasan untuk data lama berstatus terkonfirmasi', function () {
    Storage::fake('public');

    $subbagUmum = User::factory()->create([
        'role' => 'subbag_umum',
        'name' => 'Subbagian Umum',
    ]);
    $operator = User::factory()->create([
        'role' => 'bidang_tujuan',
        'name' => 'Operator Bidang',
    ]);
    $tamu = createTamuForWorkflow([
        'status' => 'terkonfirmasi',
        'workflow_step' => 'selesai',
        'bidang_verified_by' => $operator->id,
        'bidang_verified_at' => now(),
    ]);

    $response = $this
        ->withSession(adminSession($subbagUmum))
        ->post(route('admin.kunjungan.upload_balasan', $tamu), [
            'balasan_dokumen' => UploadedFile::fake()->create('balasan-legacy.pdf', 120, 'application/pdf'),
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('admin_notice');

    $tamu->refresh();
    expect($tamu->balasan_nama)->toBe('balasan-legacy.pdf');
    expect($tamu->balasan_uploaded_by)->toBe($subbagUmum->id);
    expect($tamu->balasan_uploaded_at)->not->toBeNull();
    expect($tamu->status)->toBe('selesai');
    expect($tamu->workflow_step)->toBe('selesai');
});

test('subbagian umum dapat upload dokumen balasan untuk permohonan berstatus ditunda', function () {
    Storage::fake('public');

    $subbagUmum = User::factory()->create([
        'role' => 'subbag_umum',
        'name' => 'Subbagian Umum',
    ]);
    $tamu = createTamuForWorkflow([
        'status' => 'ditunda',
        'workflow_step' => 'subbag_umum',
        'verification_note' => 'Pimpinan sedang dinas luar, mohon jadwal ulang.',
        'bidang_verified_by' => null,
        'bidang_verified_at' => null,
    ]);

    $response = $this
        ->withSession(adminSession($subbagUmum))
        ->post(route('admin.kunjungan.upload_balasan', $tamu), [
            'balasan_dokumen' => UploadedFile::fake()->create('balasan-penundaan.pdf', 120, 'application/pdf'),
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('admin_notice');

    $tamu->refresh();
    expect($tamu->balasan_nama)->toBe('balasan-penundaan.pdf');
    expect($tamu->balasan_uploaded_by)->toBe($subbagUmum->id);
    expect($tamu->balasan_uploaded_at)->not->toBeNull();
    expect($tamu->status)->toBe('selesai');
    expect($tamu->workflow_step)->toBe('selesai');
});

test('subbagian umum dapat unggah ulang dokumen balasan yang sudah ada', function () {
    Storage::fake('public');

    $subbagUmum = User::factory()->create([
        'role' => 'subbag_umum',
        'name' => 'Subbagian Umum',
    ]);
    $operator = User::factory()->create([
        'role' => 'bidang_tujuan',
        'name' => 'Operator Bidang',
    ]);
    $tamu = createTamuForWorkflow([
        'status' => 'selesai',
        'workflow_step' => 'selesai',
        'bidang_verified_by' => $operator->id,
        'bidang_verified_at' => now(),
        'balasan_nama' => 'balasan-lama.pdf',
        'balasan_mime' => 'application/pdf',
        'balasan_data' => base64_encode('dokumen-lama'),
        'balasan_uploaded_by' => $subbagUmum->id,
        'balasan_uploaded_at' => now()->subDay(),
    ]);

    $response = $this
        ->withSession(adminSession($subbagUmum))
        ->post(route('admin.kunjungan.upload_balasan', $tamu), [
            'balasan_dokumen' => UploadedFile::fake()->create('balasan-baru.pdf', 120, 'application/pdf'),
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('admin_notice');

    $tamu->refresh();
    expect($tamu->balasan_nama)->toBe('balasan-baru.pdf');
    expect($tamu->balasan_uploaded_by)->toBe($subbagUmum->id);
    expect($tamu->balasan_uploaded_at)->not->toBeNull();
    expect($tamu->status)->toBe('selesai');
    expect($tamu->workflow_step)->toBe('selesai');
});

test('subbagian umum dapat hapus dokumen balasan dan mengembalikan ke antrean upload balasan', function () {
    Storage::fake('public');

    $subbagUmum = User::factory()->create([
        'role' => 'subbag_umum',
        'name' => 'Subbagian Umum',
    ]);
    $operator = User::factory()->create([
        'role' => 'bidang_tujuan',
        'name' => 'Operator Bidang',
    ]);
    $tamu = createTamuForWorkflow([
        'status' => 'selesai',
        'workflow_step' => 'selesai',
        'bidang_verified_by' => $operator->id,
        'bidang_verified_at' => now(),
        'balasan_path' => 'uploads_balasan/balasan-lama.pdf',
        'balasan_nama' => 'balasan-lama.pdf',
        'balasan_mime' => 'application/pdf',
        'balasan_data' => base64_encode('dokumen-lama'),
        'balasan_uploaded_by' => $subbagUmum->id,
        'balasan_uploaded_at' => now()->subDay(),
    ]);

    Storage::disk('public')->put('uploads_balasan/balasan-lama.pdf', 'dokumen-lama-file');

    $response = $this
        ->withSession(adminSession($subbagUmum))
        ->post(route('admin.kunjungan.delete_balasan', $tamu));

    $response->assertRedirect();
    $response->assertSessionHas('admin_notice');

    $tamu->refresh();
    expect($tamu->balasan_path)->toBeNull();
    expect($tamu->balasan_nama)->toBeNull();
    expect($tamu->balasan_mime)->toBeNull();
    expect($tamu->balasan_data)->toBeNull();
    expect($tamu->balasan_uploaded_by)->toBeNull();
    expect($tamu->balasan_uploaded_at)->toBeNull();
    expect($tamu->status)->toBe('terkonfirmasi');
    expect($tamu->workflow_step)->toBe('subbag_umum');
    Storage::disk('public')->assertMissing('uploads_balasan/balasan-lama.pdf');
});

test('subbagian umum tidak dapat upload dokumen balasan sebelum disetujui operator bidang', function () {
    Storage::fake('public');

    $subbagUmum = User::factory()->create([
        'role' => 'subbag_umum',
        'name' => 'Subbagian Umum',
    ]);
    $tamu = createTamuForWorkflow([
        'status' => 'diterima',
        'workflow_step' => 'subbag_umum',
        'bidang_verified_by' => null,
        'bidang_verified_at' => null,
    ]);

    $response = $this
        ->from(route('admin.dashboard.subbag_umum'))
        ->withSession(adminSession($subbagUmum))
        ->post(route('admin.kunjungan.upload_balasan', $tamu), [
            'balasan_dokumen' => UploadedFile::fake()->create('balasan-kunjungan.pdf', 120, 'application/pdf'),
        ]);

    $response->assertRedirect(route('admin.dashboard.subbag_umum'));
    $response->assertSessionHasErrors('verify');

    $tamu->refresh();
    expect($tamu->balasan_path)->toBeNull();
    expect($tamu->balasan_data)->toBeNull();
    expect($tamu->balasan_uploaded_by)->toBeNull();
    expect($tamu->balasan_uploaded_at)->toBeNull();
});

test('sekretaris pimpinan wajib mengisi keterangan saat memilih reschedule', function () {
    $sekretaris = User::factory()->create([
        'role' => 'sekretaris_pimpinan',
        'name' => 'Sekretaris Pimpinan',
    ]);
    $tamu = createTamuForWorkflow([
        'status' => 'diproses',
        'workflow_step' => 'sekretaris_pimpinan',
    ]);

    $response = $this
        ->from(route('admin.dashboard'))
        ->withSession(adminSession($sekretaris))
        ->post(route('admin.kunjungan.verify', $tamu), [
            'action' => 'reschedule',
            'note' => '   ',
        ]);

    $response->assertRedirect(route('admin.dashboard'));
    $response->assertSessionHasErrors('verify');

    $tamu->refresh();
    expect($tamu->status)->toBe('diproses');
    expect($tamu->workflow_step)->toBe('sekretaris_pimpinan');
});

test('akun diarahkan ke dashboard sesuai role saat login', function () {
    $user = User::factory()->create([
        'role' => 'data_entry',
        'username' => 'data_entry_test',
        'email' => 'data.entry.test@localhost',
        'password' => bcrypt('rahasia123'),
    ]);

    $response = $this->post(route('admin.login.submit'), [
        'username' => $user->username,
        'password' => 'rahasia123',
    ]);

    $response->assertRedirect(route('admin.dashboard.data_entry'));
});

test('alias username role tetap dapat login ke dashboard yang benar', function () {
    $dataEntry = User::factory()->create([
        'role' => 'data_entry',
        'username' => 'data_entry_alias_test',
        'password' => bcrypt('rahasia123'),
    ]);
    $sekretaris = User::factory()->create([
        'role' => 'sekretaris_pimpinan',
        'username' => 'sekretaris_pimpinan_alias_test',
        'password' => bcrypt('rahasia123'),
    ]);
    $subbag = User::factory()->create([
        'role' => 'subbag_umum',
        'username' => 'subbag_umum_alias_test',
        'password' => bcrypt('rahasia123'),
    ]);
    $operator = User::factory()->create([
        'role' => 'bidang_tujuan',
        'username' => 'operator_bidang_alias_test',
        'password' => bcrypt('rahasia123'),
    ]);

    $this->post(route('admin.login.submit'), [
        'username' => 'data entry',
        'password' => 'rahasia123',
    ])->assertRedirect(route('admin.dashboard.data_entry'));

    $this->post(route('admin.login.submit'), [
        'username' => 'sekretaris pimpinan',
        'password' => 'rahasia123',
    ])->assertRedirect(route('admin.dashboard.sekretaris_pimpinan'));

    $this->post(route('admin.login.submit'), [
        'username' => 'subbagian umum',
        'password' => 'rahasia123',
    ])->assertRedirect(route('admin.dashboard.subbag_umum'));

    $this->post(route('admin.login.submit'), [
        'username' => 'operator bidang',
        'password' => 'rahasia123',
    ])->assertRedirect(route('admin.dashboard.bidang_tujuan'));

    expect($dataEntry->username)->toBe('data_entry_alias_test');
    expect($sekretaris->username)->toBe('sekretaris_pimpinan_alias_test');
    expect($subbag->username)->toBe('subbag_umum_alias_test');
    expect($operator->username)->toBe('operator_bidang_alias_test');
});

test('username legacy asisten pimpinan tidak dapat digunakan untuk login', function () {
    User::factory()->create([
        'role' => 'sekretaris_pimpinan',
        'username' => 'sekretaris_pimpinan_active',
        'password' => bcrypt('rahasia123'),
    ]);

    $response = $this
        ->from(route('admin.login'))
        ->post(route('admin.login.submit'), [
            'username' => 'asisten_pimpinan',
            'password' => 'rahasia123',
        ]);

    $response->assertRedirect(route('admin.login'));
    $response->assertSessionHasErrors('username');
});

test('role tidak bisa mengakses dashboard role lain', function () {
    $dataEntry = User::factory()->create([
        'role' => 'data_entry',
        'name' => 'Admin Data Entry',
    ]);

    $response = $this
        ->withSession(adminSession($dataEntry))
        ->get(route('admin.dashboard.sekretaris_pimpinan'));

    $response->assertStatus(403);
});

test('notifikasi live role hanya memuat antrean role tersebut', function () {
    $sekretaris = User::factory()->create([
        'role' => 'sekretaris_pimpinan',
        'name' => 'Sekretaris Pimpinan',
    ]);

    createTamuForWorkflow([
        'nama' => 'Permohonan Sekretaris',
        'workflow_step' => 'sekretaris_pimpinan',
        'status' => 'diproses',
    ]);

    createTamuForWorkflow([
        'nama' => 'Permohonan Operator',
        'workflow_step' => 'bidang_tujuan',
        'status' => 'diterima',
    ]);

    $response = $this
        ->withSession(adminSession($sekretaris))
        ->getJson(route('admin.notifications.live'));

    $response->assertOk();
    $response->assertJsonPath('role', 'sekretaris_pimpinan');
    $response->assertJsonPath('pending_count', 1);
    $response->assertJsonPath('pending_items.0.nama', 'Permohonan Sekretaris');
    $response->assertJsonPath('role_queue_counts.0.role', 'data_entry');
    $response->assertJsonPath('role_queue_counts.0.pending_count', 0);
    $response->assertJsonPath('role_queue_counts.1.role', 'sekretaris_pimpinan');
    $response->assertJsonPath('role_queue_counts.1.pending_count', 1);
    $response->assertJsonPath('role_queue_counts.2.role', 'bidang_tujuan');
    $response->assertJsonPath('role_queue_counts.2.pending_count', 1);
    $response->assertJsonPath('role_queue_counts.3.role', 'subbag_umum');
    $response->assertJsonPath('role_queue_counts.3.pending_count', 0);
});
