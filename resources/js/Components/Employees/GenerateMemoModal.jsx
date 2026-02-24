import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DatePicker from '@/Components/DatePicker';
import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

function today() {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

export default function GenerateMemoModal({
    show,
    onClose,
    employee,
    incident,
    templates = [],
    defaultSignatory = '',
}) {
    const templateItems = Array.isArray(templates) ? templates : [];

    const defaultTemplateId = templateItems?.[0]?.id ?? '';

    const [templateId, setTemplateId] = useState(defaultTemplateId);
    const [incidentSummary, setIncidentSummary] = useState('');
    const [memoDate, setMemoDate] = useState(today());
    const [hrSignatoryName, setHrSignatoryName] = useState(defaultSignatory || '');

    const [previewTitle, setPreviewTitle] = useState('');
    const [previewHtml, setPreviewHtml] = useState('');
    const [isPreviewing, setIsPreviewing] = useState(false);
    const [isGenerating, setIsGenerating] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        if (!show) return;

        setTemplateId(defaultTemplateId);
        setIncidentSummary(String(incident?.description || ''));
        setMemoDate(today());
        setHrSignatoryName(defaultSignatory || '');
        setPreviewTitle('');
        setPreviewHtml('');
        setError('');
        setIsPreviewing(false);
        setIsGenerating(false);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [show, incident?.id]);

    const canPreview = Boolean(templateId) && Boolean(employee?.employee_id) && Boolean(incident?.id);

    const preview = async () => {
        if (!canPreview) return;
        setIsPreviewing(true);
        setError('');

        try {
            const resp = await window.axios.post(
                route('employees.incidents.memos.preview', [employee.employee_id, incident.id]),
                {
                    memo_template_id: Number(templateId),
                    incident_summary: incidentSummary || null,
                    memo_date: memoDate || null,
                    hr_signatory_name: hrSignatoryName || null,
                },
                { headers: { Accept: 'application/json' } }
            );

            setPreviewTitle(resp?.data?.title || '');
            setPreviewHtml(resp?.data?.rendered_html || '');
        } catch (e) {
            setError(e?.response?.data?.message || 'Unable to preview memo.');
        } finally {
            setIsPreviewing(false);
        }
    };

    const generate = async () => {
        if (!canPreview) return;
        setIsGenerating(true);
        setError('');

        router.post(
            route('employees.incidents.memos.store', [employee.employee_id, incident.id]),
            {
                memo_template_id: Number(templateId),
                incident_summary: incidentSummary || null,
                memo_date: memoDate || null,
                hr_signatory_name: hrSignatoryName || null,
            },
            {
                preserveScroll: true,
                onError: () => {
                    setError('Unable to generate memo.');
                    setIsGenerating(false);
                },
                onFinish: () => {
                    setIsGenerating(false);
                },
                onSuccess: () => {
                    onClose?.();
                },
            }
        );
    };

    return (
        <Modal show={show} onClose={onClose} maxWidth="2xl">
            <div className="px-6 py-5 border-b border-slate-200">
                <div className="text-base font-semibold text-slate-900">Generate memo</div>
                <div className="mt-1 text-sm text-slate-600">
                    Incident: {incident?.category} • {incident?.incident_date || '—'}
                </div>
            </div>

            <div className="px-6 py-5 space-y-4">
                {!!error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                        {error}
                    </div>
                )}

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Template</label>
                        <select
                            className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                            value={templateId}
                            onChange={(e) => setTemplateId(e.target.value)}
                        >
                            {templateItems.map((t) => (
                                <option key={t.id} value={t.id}>
                                    {t.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Memo date</label>
                        <DatePicker value={memoDate} onChange={setMemoDate} />
                    </div>

                    <div className="md:col-span-2">
                        <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">HR signatory (optional)</label>
                        <input
                            className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                            value={hrSignatoryName}
                            onChange={(e) => setHrSignatoryName(e.target.value)}
                            placeholder="e.g. HR Manager"
                        />
                    </div>

                    <div className="md:col-span-2">
                        <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Incident summary</label>
                        <textarea
                            rows={5}
                            className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                            value={incidentSummary}
                            onChange={(e) => setIncidentSummary(e.target.value)}
                        />
                    </div>
                </div>

                <div className="flex items-center justify-end gap-2">
                    <SecondaryButton type="button" onClick={onClose}>
                        Cancel
                    </SecondaryButton>
                    <SecondaryButton type="button" onClick={preview} disabled={!canPreview || isPreviewing}>
                        {isPreviewing ? 'Previewing…' : 'Preview'}
                    </SecondaryButton>
                    <PrimaryButton type="button" onClick={generate} disabled={!canPreview || isGenerating}>
                        {isGenerating ? 'Generating…' : 'Generate PDF'}
                    </PrimaryButton>
                </div>

                {!!previewHtml && (
                    <div className="mt-2 rounded-lg border border-slate-200 overflow-hidden">
                        <div className="bg-slate-50 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-600">
                            Preview {previewTitle ? `— ${previewTitle}` : ''}
                        </div>
                        <div className="p-4 text-sm text-slate-900 max-h-[360px] overflow-auto">
                            <div dangerouslySetInnerHTML={{ __html: previewHtml }} />
                        </div>
                    </div>
                )}
            </div>
        </Modal>
    );
}
