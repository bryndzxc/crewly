import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Create() {
  const { props } = usePage();
  const auth = props.auth ?? {};
  const { data, setData, post, processing, errors } = useForm({
    company: {
      name: '',
      slug: '',
      timezone: '',
      is_active: true,
    },
    user: {
      name: '',
      email: '',
      password: '',
      role: 'manager',
    },
  });

  const submit = (e) => {
    e.preventDefault();
    post(route('developer.companies.store'));
  };

  return (
    <AuthenticatedLayout user={auth.user} header="Developer">
      <Head title="Developer / Create Company" />

      <div className="max-w-3xl mx-auto p-4 sm:p-6 lg:p-8">
        <div className="flex items-center justify-between">
          <h1 className="text-xl font-semibold">Create Company</h1>
          <Link href={route('developer.companies.index')} className="text-sm underline">
            Back
          </Link>
        </div>

        <form onSubmit={submit} className="mt-6 space-y-6">
          <div className="rounded-lg border p-4">
            <div className="font-medium">Company</div>

            <div className="mt-3 grid gap-4">
              <div>
                <label className="block text-sm">Name</label>
                <input
                  className="mt-1 w-full rounded-md border px-3 py-2"
                  value={data.company.name}
                  onChange={(e) => setData('company', { ...data.company, name: e.target.value })}
                />
                {errors['company.name'] && <div className="text-sm text-red-600 mt-1">{errors['company.name']}</div>}
              </div>

              <div>
                <label className="block text-sm">Slug (optional)</label>
                <input
                  className="mt-1 w-full rounded-md border px-3 py-2"
                  value={data.company.slug}
                  onChange={(e) => setData('company', { ...data.company, slug: e.target.value })}
                />
                {errors['company.slug'] && <div className="text-sm text-red-600 mt-1">{errors['company.slug']}</div>}
              </div>

              <div>
                <label className="block text-sm">Timezone (optional)</label>
                <input
                  className="mt-1 w-full rounded-md border px-3 py-2"
                  value={data.company.timezone}
                  onChange={(e) => setData('company', { ...data.company, timezone: e.target.value })}
                />
                {errors['company.timezone'] && <div className="text-sm text-red-600 mt-1">{errors['company.timezone']}</div>}
              </div>

              <div className="flex items-center gap-2">
                <input
                  type="checkbox"
                  checked={!!data.company.is_active}
                  onChange={(e) => setData('company', { ...data.company, is_active: e.target.checked })}
                />
                <span className="text-sm">Active</span>
              </div>
            </div>
          </div>

          <div className="rounded-lg border p-4">
            <div className="font-medium">Initial User</div>

            <div className="mt-3 grid gap-4">
              <div>
                <label className="block text-sm">Name</label>
                <input
                  className="mt-1 w-full rounded-md border px-3 py-2"
                  value={data.user.name}
                  onChange={(e) => setData('user', { ...data.user, name: e.target.value })}
                />
                {errors['user.name'] && <div className="text-sm text-red-600 mt-1">{errors['user.name']}</div>}
              </div>

              <div>
                <label className="block text-sm">Email</label>
                <input
                  className="mt-1 w-full rounded-md border px-3 py-2"
                  value={data.user.email}
                  onChange={(e) => setData('user', { ...data.user, email: e.target.value })}
                />
                {errors['user.email'] && <div className="text-sm text-red-600 mt-1">{errors['user.email']}</div>}
              </div>

              <div>
                <label className="block text-sm">Password</label>
                <input
                  type="password"
                  className="mt-1 w-full rounded-md border px-3 py-2"
                  value={data.user.password}
                  onChange={(e) => setData('user', { ...data.user, password: e.target.value })}
                />
                {errors['user.password'] && <div className="text-sm text-red-600 mt-1">{errors['user.password']}</div>}
              </div>

              <div>
                <label className="block text-sm">Role</label>
                <select
                  className="mt-1 w-full rounded-md border px-3 py-2"
                  value={data.user.role}
                  onChange={(e) => setData('user', { ...data.user, role: e.target.value })}
                >
                  <option value="admin">admin</option>
                  <option value="hr">hr</option>
                  <option value="manager">manager</option>
                  <option value="employee">employee</option>
                </select>
                {errors['user.role'] && <div className="text-sm text-red-600 mt-1">{errors['user.role']}</div>}
              </div>
            </div>
          </div>

          <div className="flex items-center justify-end gap-3">
            <Link href={route('developer.companies.index')} className="px-4 py-2 rounded-md border text-sm">
              Cancel
            </Link>
            <button
              type="submit"
              disabled={processing}
              className="px-4 py-2 rounded-md border text-sm font-medium"
            >
              {processing ? 'Creating…' : 'Create'}
            </button>
          </div>
        </form>
      </div>
    </AuthenticatedLayout>
  );
}
