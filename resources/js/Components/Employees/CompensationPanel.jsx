import DatePicker from '@/Components/DatePicker';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

const salaryTypeOptions = [
    { value: 'monthly', label: 'Monthly' },
    { value: 'daily', label: 'Daily' },
    { value: 'hourly', label: 'Hourly' },
];

const payFrequencyOptions = [
    { value: 'monthly', label: 'Monthly' },
    { value: 'semi-monthly', label: 'Semi-Monthly' },
    { value: 'weekly', label: 'Weekly' },
];

export default function CompensationPanel({ employeeId, compensation = null }) {
    const form = useForm({
        salary_type: compensation?.salary_type ?? 'monthly',
        base_salary: compensation?.base_salary ? String(compensation.base_salary) : '',
        pay_frequency: compensation?.pay_frequency ?? 'monthly',
        effective_date: compensation?.effective_date ?? '',
        notes: compensation?.notes ?? '',
        change_reason: '',
    });

    useEffect(() => {
        form.setData({
            salary_type: compensation?.salary_type ?? 'monthly',
            base_salary: compensation?.base_salary ? String(compensation.base_salary) : '',
            pay_frequency: compensation?.pay_frequency ?? 'monthly',
            effective_date: compensation?.effective_date ?? '',
            notes: compensation?.notes ?? '',
            change_reason: '',
        });
    }, [compensation]);

    function submit(e) {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                form.setData('change_reason', '');
            },
        };

        if (compensation?.id) {
            form.patch(route('employees.compensation.update', employeeId), options);
            return;
        }

        form.post(route('employees.compensation.store', employeeId), options);
    }

    return (
        <div className="space-y-6">
            <div className="rounded-lg border border-amber-200 bg-amber-50 p-4">
                <div className="text-sm font-semibold text-gray-900">Compensation Setup</div>
                <p className="mt-1 text-sm text-gray-700">
                    This record becomes the payroll-ready base for future gross pay calculations together with employee allowances.
                </p>
            </div>

            <form className="grid grid-cols-1 gap-5 lg:grid-cols-2" onSubmit={submit}>
                <div>
                    <InputLabel value="Salary Type" />
                    <select
                        className="mt-1 block w-full rounded-md border-slate-300 bg-white/90 px-3 py-2 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30"
                        value={form.data.salary_type}
                        onChange={(e) => form.setData('salary_type', e.target.value)}
                    >
                        {salaryTypeOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <InputError className="mt-2" message={form.errors.salary_type} />
                </div>

                <div>
                    <InputLabel value="Base Salary" />
                    <TextInput
                        type="number"
                        min="0"
                        step="0.01"
                        className="mt-1 block w-full"
                        value={form.data.base_salary}
                        onChange={(e) => form.setData('base_salary', e.target.value)}
                        placeholder="0.00"
                    />
                    <InputError className="mt-2" message={form.errors.base_salary} />
                </div>

                <div>
                    <InputLabel value="Pay Frequency" />
                    <select
                        className="mt-1 block w-full rounded-md border-slate-300 bg-white/90 px-3 py-2 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30"
                        value={form.data.pay_frequency}
                        onChange={(e) => form.setData('pay_frequency', e.target.value)}
                    >
                        {payFrequencyOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <InputError className="mt-2" message={form.errors.pay_frequency} />
                </div>

                <div>
                    <InputLabel value="Effective Date" />
                    <DatePicker value={form.data.effective_date} onChange={(value) => form.setData('effective_date', value)} />
                    <InputError className="mt-2" message={form.errors.effective_date} />
                </div>

                <div className="lg:col-span-2">
                    <InputLabel value="Notes" />
                    <textarea
                        rows={3}
                        className="mt-1 block w-full rounded-md border-slate-300 bg-white/90 px-3 py-2 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30"
                        value={form.data.notes}
                        onChange={(e) => form.setData('notes', e.target.value)}
                        placeholder="Optional compensation notes"
                    />
                    <InputError className="mt-2" message={form.errors.notes} />
                </div>

                <div className="lg:col-span-2">
                    <InputLabel value="Change Reason" />
                    <TextInput
                        className="mt-1 block w-full"
                        value={form.data.change_reason}
                        onChange={(e) => form.setData('change_reason', e.target.value)}
                        placeholder="Optional reason for the salary change"
                    />
                    <p className="mt-1 text-xs text-gray-500">Used only when the base salary changes and a salary history entry is created.</p>
                    <InputError className="mt-2" message={form.errors.change_reason} />
                </div>

                <div className="lg:col-span-2 flex items-center justify-end">
                    <PrimaryButton type="submit" disabled={form.processing}>
                        {form.processing ? 'Saving...' : compensation?.id ? 'Update Compensation' : 'Create Compensation'}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    );
}