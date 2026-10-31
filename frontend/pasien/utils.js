/** Label status pendaftaran untuk tampilan Indonesia */
export function statusLabel(status) {
    const map = {
        pending: 'Menunggu verifikasi',
        confirmed: 'Dikonfirmasi',
        checked_in: 'Check-in',
        completed: 'Selesai',
        cancelled: 'Dibatalkan',
    };
    return map[status] || status || '—';
}

export function formatDate(iso) {
    if (!iso) return '—';
    try {
        const s = String(iso).slice(0, 10);
        if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
            const [y, m, d] = s.split('-').map(Number);
            return new Date(y, m - 1, d).toLocaleDateString('id-ID', {
                weekday: 'short',
                day: 'numeric',
                month: 'short',
                year: 'numeric',
            });
        }
        return new Date(iso).toLocaleDateString('id-ID', {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    } catch {
        return iso;
    }
}
