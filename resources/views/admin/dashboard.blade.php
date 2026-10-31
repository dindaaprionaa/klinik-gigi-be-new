@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('subheading', 'Ringkasan statistik admin.')

@section('content')
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-slate-900"><p class="text-xs text-slate-500">Total Pasien</p><p id="kpi-pasien" class="mt-1 text-2xl font-semibold">0</p></div>
        <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-slate-900"><p class="text-xs text-slate-500">Total Dokter</p><p id="kpi-dokter" class="mt-1 text-2xl font-semibold">0</p></div>
        <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-slate-900"><p class="text-xs text-slate-500">Total Pendaftaran</p><p id="kpi-pendaftaran" class="mt-1 text-2xl font-semibold">0</p></div>
        <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-slate-900"><p class="text-xs text-slate-500">Pendaftaran Hari Ini</p><p id="kpi-hari-ini" class="mt-1 text-2xl font-semibold">0</p></div>
        <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-slate-900"><p class="text-xs text-slate-500">Pending Verifikasi</p><p id="kpi-pending" class="mt-1 text-2xl font-semibold">0</p></div>
    </div>
@endsection

@push('scripts')
<script>
    (async function () {
        try {
            const d = await window.__adminApi('/admin/dashboard');
            document.getElementById('kpi-pasien').textContent = d.totalPasien ?? 0;
            document.getElementById('kpi-dokter').textContent = d.totalDokter ?? 0;
            document.getElementById('kpi-pendaftaran').textContent = d.totalPendaftaran ?? 0;
            document.getElementById('kpi-hari-ini').textContent = d.pendaftaranHariIni ?? 0;
            document.getElementById('kpi-pending').textContent = d.pendaftaranPending ?? 0;
        } catch (e) {
            window.__adminAlert(e.message || 'Gagal memuat dashboard');
        }
    })();
</script>
@endpush
