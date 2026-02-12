import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import DatePicker from '@/Components/DatePicker';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const statusChoices = ['Active', 'Inactive', 'On Leave', 'Terminated', 'Resigned'];
const employmentTypeChoices = ['Full-Time', 'Part-Time', 'Contractor', 'Intern'];

export default function Edit({ auth, employee, departments = [], inModal = false, onCancel, onSuccess }) {
    const [successMessage, setSuccessMessage] = useState('');

    const pageErrors = usePage().props?.errors ?? {};
    const flash = usePage().props?.flash;
    const { data, setData, patch, processing, errors: formErrors } = useForm({
        department_id: employee?.department_id ?? (departments?.[0]?.department_id ?? ''),
        employee_code: employee?.employee_code ?? '',
        first_name: employee?.first_name ?? '',
        middle_name: employee?.middle_name ?? '',
        last_name: employee?.last_name ?? '',
        suffix: employee?.suffix ?? '',
        email: employee?.email ?? '',
        mobile_number: employee?.mobile_number ?? '',
        status: employee?.status ?? 'Active',
        position_title: employee?.position_title ?? '',
        date_hired: employee?.date_hired ?? '',
        regularization_date: employee?.regularization_date ?? '',
        employment_type: employee?.employment_type ?? 'Full-Time',
        notes: employee?.notes ?? '',
    });

    const errors = Object.keys(formErrors ?? {}).length > 0 ? formErrors : pageErrors;
    const errorMessages = Object.values(errors ?? {}).filter(Boolean);

    const submit = (e) => {
        e.preventDefault();
        patch(route('employees.update', employee.employee_id), {
            preserveScroll: true,
            preserveState: inModal,
            onSuccess: () => {
                if (inModal) {
                    setSuccessMessage('Employee updated successfully.');
                    if (typeof onSuccess === 'function') {
                        setTimeout(() => onSuccess(), 900);
                    }
                    return;
                }

                if (typeof onSuccess === 'function') onSuccess();
            },
        });
    };

    const form = (
        <div className={inModal ? '' : 'w-full'}>
            <div className={inModal ? '' : 'bg-white border border-gray-200 rounded-lg p-6'}>
                <form onSubmit={submit} className="space-y-5">
                    {!inModal && !!flash?.success && (
                        <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                            {flash.success}
                        </div>
                    )}
                    {!inModal && !!flash?.error && (
                        <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                            {flash.error}
                        </div>
                    )}

                    {errorMessages.length > 0 && (
                        <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                            <div className="font-medium">Update failed</div>
                            <ul className="mt-1 list-disc pl-5">
                                {errorMessages.slice(0, 6).map((msg, idx) => (
                                    <li key={idx}>{msg}</li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {inModal && !!successMessage && (
                        <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                            {successMessage}
                        </div>
                    )}

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="employee_code" value="Employee Code" />
                            <TextInput
                                id="employee_code"
                                name="employee_code"
                                value={data.employee_code}
                                className="mt-1 block w-full"
                                isFocused={true}
                                onChange={(e) => setData('employee_code', e.target.value)}
                            />
                            <InputError message={errors.employee_code} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="department_id" value="Department" />
                            <select
                                id="department_id"
                                name="department_id"
                                value={data.department_id}
                                onChange={(e) => setData('department_id', e.target.value)}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                {(departments ?? []).map((d) => (
                                    <option key={d.department_id} value={d.department_id}>
                                        {d.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.department_id} className="mt-2" />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="first_name" value="First Name" />
                            <TextInput
                                id="first_name"
                                name="first_name"
                                value={data.first_name}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('first_name', e.target.value)}
                            />
                            <InputError message={errors.first_name} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="middle_name" value="Middle Name (optional)" />
                            <TextInput
                                id="middle_name"
                                name="middle_name"
                                value={data.middle_name}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('middle_name', e.target.value)}
                            />
                            <InputError message={errors.middle_name} className="mt-2" />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="last_name" value="Last Name" />
                            <TextInput
                                id="last_name"
                                name="last_name"
                                value={data.last_name}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('last_name', e.target.value)}
                            />
                            <InputError message={errors.last_name} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="suffix" value="Suffix (optional)" />
                            <TextInput
                                id="suffix"
                                name="suffix"
                                value={data.suffix}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('suffix', e.target.value)}
                            />
                            <InputError message={errors.suffix} className="mt-2" />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="email" value="Email" />
                            <TextInput
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                className="mt-1 block w-full"
                                autoComplete="username"
                                onChange={(e) => setData('email', e.target.value)}
                            />
                            <InputError message={errors.email} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="mobile_number" value="Mobile Number (optional)" />
                            <TextInput
                                id="mobile_number"
                                name="mobile_number"
                                value={data.mobile_number}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('mobile_number', e.target.value)}
                            />
                            <InputError message={errors.mobile_number} className="mt-2" />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="status" value="Status" />
                            <select
                                id="status"
                                name="status"
                                value={data.status}
                                onChange={(e) => setData('status', e.target.value)}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                {statusChoices.map((s) => (
                                    <option key={s} value={s}>
                                        {s}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.status} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="employment_type" value="Employment Type" />
                            <select
                                id="employment_type"
                                name="employment_type"
                                value={data.employment_type}
                                onChange={(e) => setData('employment_type', e.target.value)}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                {employmentTypeChoices.map((t) => (
                                    <option key={t} value={t}>
                                        {t}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.employment_type} className="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel htmlFor="position_title" value="Position Title (optional)" />
                        <TextInput
                            id="position_title"
                            name="position_title"
                            value={data.position_title}
                            className="mt-1 block w-full"
                            onChange={(e) => setData('position_title', e.target.value)}
                        />
                        <InputError message={errors.position_title} className="mt-2" />
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="date_hired" value="Date Hired (optional)" />
                            <DatePicker
                                id="date_hired"
                                name="date_hired"
                                value={data.date_hired}
                                onChange={(v) => setData('date_hired', v)}
                            />
                            <InputError message={errors.date_hired} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="regularization_date" value="Regularization Date (optional)" />
                            <DatePicker
                                id="regularization_date"
                                name="regularization_date"
                                value={data.regularization_date}
                                onChange={(v) => setData('regularization_date', v)}
                            />
                            <InputError message={errors.regularization_date} className="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel htmlFor="notes" value="Notes (optional)" />
                        <textarea
                            id="notes"
                            name="notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            rows={4}
                        />
                        <InputError message={errors.notes} className="mt-2" />
                    </div>

                    <div className="flex items-center justify-end gap-3">
                        {inModal ? (
                            <SecondaryButton type="button" onClick={onCancel} disabled={processing}>
                                Cancel
                            </SecondaryButton>
                        ) : (
                            <Link href={route('employees.index')} className="text-sm text-gray-600 hover:text-gray-900">
                                Cancel
                            </Link>
                        )}
                        <PrimaryButton disabled={processing}>Save</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    );

    if (inModal) return form;

    return (
        <AuthenticatedLayout user={auth.user} header="Edit Employee" contentClassName="max-w-none">
            <Head title="Edit Employee" />
            {form}
        </AuthenticatedLayout>
    );
}
