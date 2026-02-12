import Badge from '@/Components/UI/Badge';

const toneFor = (status) => {
    const value = String(status || '').toUpperCase();
    if (value === 'EXPIRED') return 'danger';
    if (value === 'EXPIRING') return 'amber';
    return 'neutral';
};

export default function ExpiryStatusBadge({ status }) {
    const value = String(status || 'OK').toUpperCase();

    return <Badge tone={toneFor(value)}>{value}</Badge>;
}
