<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tamu extends Model
{
    use HasFactory;

    protected $table = 'data_kunjungan';

    protected $fillable = [
        'nama',
        'nomor_surat',
        'instansi',
        'instansi_provinsi',
        'instansi_kabupaten_kota',
        'jabatan',
        'telepon',
        'tujuan',
        'tanggal_usulan',
        'jumlah_peserta',
        'keperluan',
        'lampiran_path',
        'lampiran_nama',
        'lampiran_mime',
        'lampiran_data',
        'balasan_path',
        'balasan_nama',
        'balasan_mime',
        'balasan_data',
        'balasan_uploaded_by',
        'balasan_uploaded_at',
        'status',
        'workflow_step',
        'entry_verified_by',
        'entry_verified_at',
        'assistant_verified_by',
        'assistant_verified_at',
        'assigned_operator_id',
        'bidang_verified_by',
        'bidang_verified_at',
        'verification_note',
        'reschedule_at',
    ];

    protected $casts = [
        'tanggal_usulan' => 'date',
        'reschedule_at' => 'date',
        'entry_verified_at' => 'datetime',
        'assistant_verified_at' => 'datetime',
        'bidang_verified_at' => 'datetime',
        'balasan_uploaded_at' => 'datetime',
    ];

    public function assignedOperator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_operator_id');
    }

    public function bidangVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bidang_verified_by');
    }
}
