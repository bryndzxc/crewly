import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

export default function GovernmentInfoPanel({ employeeId, employee = null }) {
    const form = useForm({
        sss_number: employee?.sss_number ?? '',
        philhealth_number: employee?.philhealth_number ?? '',
        pagibig_number: employee?.pagibig_number ?? '',
        tin_number: employee?.tin_number ?? '',
    });

    useEffect(() => {
        form.setData({
            sss_number: employee?.sss_number ?? '',
            philhealth_number: employee?.philhealth_number ?? '',
            pagibig_number: employee?.pagibig_number ?? '',
            tin_number: employee?.tin_number ?? '',
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [employeeId]);

    function submit(e) {
        e.preventDefault();

        form.patch(route('employees.government-info.update', employeeId), {
            preserveScroll: true,
        });
    }

    return (
        <div className="space-y-6">
            <div className="rounded-lg border border-amber-200 bg-amber-50 p-4">
                <div className="text-sm font-semibold text-gray-900">Government Information</div>
                <p className="mt-1 text-sm text-gray-700">Optional fields (future-ready for payroll computation).</p>
            </div>

            <form className="grid grid-cols-1 gap-5 lg:grid-cols-2" onSubmit={submit}>
                <div>
                    <InputLabel value="SSS Number" />
                    <TextInput className="mt-1 block w-full" value={form.data.sss_number} onChange={(e) => form.setData('sss_number', e.target.value)} />
                    <InputError className="mt-2" message={form.errors.sss_number} />
                </div>

                <div>
                    <InputLabel value="PhilHealth Number" />
                    <TextInput
                        className="mt-1 block w-full"
                        value={form.data.philhealth_number}
                        onChange={(e) => form.setData('philhealth_number', e.target.value)}
                    />
                    <InputError className="mt-2" message={form.errors.philhealth_number} />
                </div>

                <div>
                    <InputLabel value="Pag-IBIG Number" />
                    <TextInput
                        className="mt-1 block w-full"
                        value={form.data.pagibig_number}
                        onChange={(e) => form.setData('pagibig_number', e.target.value)}
                    />
                    <InputError className="mt-2" message={form.errors.pagibig_number} />
                </div>

                <div>
                    <InputLabel value="TIN" />
                    <TextInput className="mt-1 block w-full" value={form.data.tin_number} onChange={(e) => form.setData('tin_number', e.target.value)} />
                    <InputError className="mt-2" message={form.errors.tin_number} />
                </div>

                <div className="lg:col-span-2 flex items-center justify-end">
                    <PrimaryButton type="submit" disabled={form.processing}>
                        {form.processing ? 'Saving...' : 'Save Government Info'}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    );
}
