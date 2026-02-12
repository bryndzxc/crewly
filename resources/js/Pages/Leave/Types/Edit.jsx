import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import Checkbox from '@/Components/Checkbox';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Edit({ auth, type }) {
    const { data, setData, patch, processing, errors } = useForm({
        name: type?.name ?? '',
        code: type?.code ?? '',
        requires_approval: Boolean(type?.requires_approval),
        paid: Boolean(type?.paid),
        allow_half_day: Boolean(type?.allow_half_day),
        default_annual_credits: type?.default_annual_credits ?? '',
        is_active: Boolean(type?.is_active),
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route('leave.types.update', type.id));
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Edit Leave Type">
            <Head title="Edit Leave Type" />

            <div className="max-w-2xl mx-auto bg-white border border-gray-200 rounded-lg p-6">
                <form onSubmit={submit} className="space-y-5">
                    <div>
                        <InputLabel htmlFor="name" value="Name" />
                        <TextInput
                            id="name"
                            value={data.name}
                            className="mt-1 block w-full"
                            isFocused={true}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        <InputError message={errors.name} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="code" value="Code" />
                        <TextInput
                            id="code"
                            value={data.code}
                            className="mt-1 block w-full"
                            placeholder="e.g. VL"
                            onChange={(e) => setData('code', e.target.value)}
                        />
                        <InputError message={errors.code} className="mt-2" />
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label className="flex items-center gap-2 text-sm text-slate-700">
                            <Checkbox checked={Boolean(data.requires_approval)} onChange={(e) => setData('requires_approval', e.target.checked)} />
                            Requires approval
                        </label>
                        <label className="flex items-center gap-2 text-sm text-slate-700">
                            <Checkbox checked={Boolean(data.paid)} onChange={(e) => setData('paid', e.target.checked)} />
                            Paid
                        </label>
                        <label className="flex items-center gap-2 text-sm text-slate-700">
                            <Checkbox checked={Boolean(data.allow_half_day)} onChange={(e) => setData('allow_half_day', e.target.checked)} />
                            Allow half-day
                        </label>
                        <label className="flex items-center gap-2 text-sm text-slate-700">
                            <Checkbox checked={Boolean(data.is_active)} onChange={(e) => setData('is_active', e.target.checked)} />
                            Active
                        </label>
                    </div>

                    <div>
                        <InputLabel htmlFor="default_annual_credits" value="Default annual credits (optional)" />
                        <TextInput
                            id="default_annual_credits"
                            value={data.default_annual_credits}
                            className="mt-1 block w-full"
                            placeholder="e.g. 15"
                            onChange={(e) => setData('default_annual_credits', e.target.value)}
                        />
                        <InputError message={errors.default_annual_credits} className="mt-2" />
                    </div>

                    <div className="flex items-center justify-end gap-3">
                        <Link href={route('leave.types.index')} className="text-sm text-gray-600 hover:text-gray-900">
                            Cancel
                        </Link>
                        <PrimaryButton disabled={processing}>Save</PrimaryButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
