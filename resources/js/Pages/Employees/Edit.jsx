import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/UI/PageHeader';
import Card from '@/Components/UI/Card';
import PrimaryButton from '@/Components/PrimaryButton';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { departmentOptions, dummyEmployees, statusOptions } from '@/data/dummyEmployees';
import { Head, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

function parseEmployeeIdFromUrl(url) {
    const parts = String(url || '')
        .split('?')[0]
        .split('#')[0]
        .split('/')
        .filter(Boolean);

    const idx = parts.findIndex((p) => p === 'employees');
    if (idx === -1) return null;

    const id = parts[idx + 1];
    if (!id) return null;
    if (!/^\d+$/.test(id)) return null;
    return Number(id);
}

export default function Edit({ auth }) {
    const { url } = usePage();
    const employeeId = parseEmployeeIdFromUrl(url) ?? 1;

    const employee = useMemo(() => {
        return dummyEmployees.find((e) => e.id === employeeId) ?? dummyEmployees[0];
    }, [employeeId]);

    const [form, setForm] = useState(() => ({
        firstName: employee.firstName ?? '',
        lastName: employee.lastName ?? '',
        email: employee.email ?? '',
        phone: employee.phone ?? '',
        employeeId: employee.employeeId ?? '',
        status: employee.status ?? 'Active',
        department: employee.department ?? 'Engineering',
        position: employee.position ?? '',
        hireDate: employee.hireDate ?? '',
    }));

    const statusChoices = useMemo(() => statusOptions.filter((s) => s !== 'All'), []);
    const departmentChoices = useMemo(() => departmentOptions.filter((d) => d !== 'All'), []);

    const setField = (key, value) => setForm((prev) => ({ ...prev, [key]: value }));

    const onSubmit = (e) => {
        e.preventDefault();
        alert('Frontend only');
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Employees" contentClassName="max-w-none">
            <Head title={`Edit Employee - ${employee.fullName}`} />

            <div className="w-full space-y-4">
                <PageHeader title="Edit Employee" subtitle={employee.fullName} />

                <form onSubmit={onSubmit} className="space-y-4">
                    <Card className="p-5">
                        <div className="text-sm font-semibold text-slate-900">Personal Info</div>

                        <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel value="First name" />
                                <TextInput
                                    value={form.firstName}
                                    onChange={(e) => setField('firstName', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                            </div>

                            <div>
                                <InputLabel value="Last name" />
                                <TextInput
                                    value={form.lastName}
                                    onChange={(e) => setField('lastName', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                            </div>

                            <div>
                                <InputLabel value="Email" />
                                <TextInput
                                    type="email"
                                    value={form.email}
                                    onChange={(e) => setField('email', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                            </div>

                            <div>
                                <InputLabel value="Phone" />
                                <TextInput
                                    value={form.phone}
                                    onChange={(e) => setField('phone', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                            </div>
                        </div>
                    </Card>

                    <Card className="p-5">
                        <div className="text-sm font-semibold text-slate-900">Employment Info</div>

                        <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <InputLabel value="Employee ID" />
                                <TextInput
                                    value={form.employeeId}
                                    onChange={(e) => setField('employeeId', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                            </div>

                            <div>
                                <InputLabel value="Status" />
                                <select
                                    value={form.status}
                                    onChange={(e) => setField('status', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-slate-300 bg-white/90 px-3 py-2 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30"
                                >
                                    {statusChoices.map((s) => (
                                        <option key={s} value={s}>
                                            {s}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <InputLabel value="Department" />
                                <select
                                    value={form.department}
                                    onChange={(e) => setField('department', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-slate-300 bg-white/90 px-3 py-2 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30"
                                >
                                    {departmentChoices.map((d) => (
                                        <option key={d} value={d}>
                                            {d}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="sm:col-span-2 lg:col-span-2">
                                <InputLabel value="Position" />
                                <TextInput
                                    value={form.position}
                                    onChange={(e) => setField('position', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                            </div>

                            <div>
                                <InputLabel value="Hire date" />
                                <TextInput
                                    type="date"
                                    value={form.hireDate}
                                    onChange={(e) => setField('hireDate', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                            </div>
                        </div>

                        <div className="mt-6 flex items-center justify-end">
                            <PrimaryButton type="submit">Save Changes</PrimaryButton>
                        </div>
                    </Card>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
