import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const frequencyOptions = [
    { value: 'monthly', label: 'Monthly' },
    { value: 'per_payroll', label: 'Per Payroll' },
];

function formatCurrency(value) {
    const amount = Number(value ?? 0);

    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(Number.isFinite(amount) ? amount : 0);
}

export default function AllowancePanel({ employeeId, allowances = [] }) {
    const [editingAllowanceId, setEditingAllowanceId] = useState(null);

    const form = useForm({
        allowance_name: '',
        amount: '',
        frequency: 'monthly',
        taxable: false,
    });

    function resetForm() {
        setEditingAllowanceId(null);
        form.setData({
            allowance_name: '',
            amount: '',
            frequency: 'monthly',
            taxable: false,
        });
        form.clearErrors();
    }

    function startEdit(allowance) {
        setEditingAllowanceId(allowance.id);
        form.setData({
            allowance_name: allowance.allowance_name ?? '',
            amount: allowance.amount ? String(allowance.amount) : '',
            frequency: allowance.frequency ?? 'monthly',
            taxable: Boolean(allowance.taxable),
        });
    }

    function submit(e) {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        };

        if (editingAllowanceId) {
            form.patch(route('employees.allowances.update', [employeeId, editingAllowanceId]), options);
            return;
        }

        form.post(route('employees.allowances.store', employeeId), options);
    }

    function destroyAllowance(allowanceId) {
        if (!confirm('Delete this allowance? This cannot be undone.')) return;

        router.delete(route('employees.allowances.destroy', [employeeId, allowanceId]), {
            preserveScroll: true,
            onSuccess: () => {
                if (editingAllowanceId === allowanceId) {
                    resetForm();
                }
            },
        });
    }

    return (
        <div className="space-y-6">
            <div className="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,380px),minmax(0,1fr)]">
                <div className="rounded-lg border border-amber-200 bg-amber-50 p-5">
                    <div className="text-sm font-semibold text-gray-900">
                        {editingAllowanceId ? 'Edit Allowance' : 'Add Allowance'}
                    </div>
                    <p className="mt-1 text-sm text-gray-700">
                        Store recurring benefits that should be added to future gross pay calculations.
                    </p>

                    <form className="mt-4 space-y-4" onSubmit={submit}>
                        <div>
                            <InputLabel value="Allowance Name" />
                            <TextInput
                                className="mt-1 block w-full"
                                value={form.data.allowance_name}
                                onChange={(e) => form.setData('allowance_name', e.target.value)}
                                placeholder="Rice Subsidy"
                            />
                            <InputError className="mt-2" message={form.errors.allowance_name} />
                        </div>

                        <div>
                            <InputLabel value="Amount" />
                            <TextInput
                                type="number"
                                min="0"
                                step="0.01"
                                className="mt-1 block w-full"
                                value={form.data.amount}
                                onChange={(e) => form.setData('amount', e.target.value)}
                                placeholder="0.00"
                            />
                            <InputError className="mt-2" message={form.errors.amount} />
                        </div>

                        <div>
                            <InputLabel value="Frequency" />
                            <select
                                className="mt-1 block w-full rounded-md border-slate-300 bg-white/90 px-3 py-2 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30"
                                value={form.data.frequency}
                                onChange={(e) => form.setData('frequency', e.target.value)}
                            >
                                {frequencyOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                            <InputError className="mt-2" message={form.errors.frequency} />
                        </div>

                        <label className="flex items-center gap-3 rounded-md border border-amber-100 bg-white/70 px-3 py-2 text-sm text-gray-700">
                            <input
                                type="checkbox"
                                checked={form.data.taxable}
                                onChange={(e) => form.setData('taxable', e.target.checked)}
                                className="rounded border-slate-300 text-amber-600 shadow-sm focus:ring-amber-500"
                            />
                            Taxable benefit
                        </label>
                        <InputError className="mt-2" message={form.errors.taxable} />

                        <div className="flex items-center justify-end gap-3">
                            {editingAllowanceId && (
                                <SecondaryButton type="button" onClick={resetForm}>
                                    Cancel
                                </SecondaryButton>
                            )}

                            <PrimaryButton type="submit" disabled={form.processing}>
                                {form.processing ? 'Saving...' : editingAllowanceId ? 'Update Allowance' : 'Add Allowance'}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white">
                    <div className="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                        <div className="text-sm font-semibold text-gray-900">Active Allowances</div>
                        <div className="text-sm text-gray-600">{allowances.length} total</div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Allowance</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Amount</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Frequency</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Taxable</th>
                                    <th className="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {allowances.length === 0 && (
                                    <tr>
                                        <td className="px-4 py-6 text-sm text-gray-600" colSpan={5}>
                                            No allowances added yet.
                                        </td>
                                    </tr>
                                )}

                                {allowances.map((allowance) => (
                                    <tr key={allowance.id} className="hover:bg-amber-50/40">
                                        <td className="px-4 py-3 text-sm font-medium text-gray-900">{allowance.allowance_name}</td>
                                        <td className="px-4 py-3 text-sm text-gray-700">{formatCurrency(allowance.amount)}</td>
                                        <td className="px-4 py-3 text-sm text-gray-700">{allowance.frequency === 'per_payroll' ? 'Per Payroll' : 'Monthly'}</td>
                                        <td className="px-4 py-3 text-sm text-gray-700">{allowance.taxable ? 'Yes' : 'No'}</td>
                                        <td className="px-4 py-3 text-right text-sm whitespace-nowrap">
                                            <button
                                                type="button"
                                                className="font-medium text-amber-700 hover:text-amber-900"
                                                onClick={() => startEdit(allowance)}
                                            >
                                                Edit
                                            </button>
                                            <button
                                                type="button"
                                                className="ml-4 font-medium text-red-600 hover:text-red-800"
                                                onClick={() => destroyAllowance(allowance.id)}
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    );
}