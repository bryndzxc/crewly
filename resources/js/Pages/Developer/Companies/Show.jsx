import React, { useMemo, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import Table from '@/Components/Table';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function Show() {
  const { props } = usePage();
  const auth = props.auth ?? {};
  const company = props.company ?? {};
  const filters = props.filters ?? {};

  const usersPaginator = props.users ?? null;
  const users = usersPaginator?.data ?? (Array.isArray(usersPaginator) ? usersPaginator : []);

  const [perPage, setPerPage] = useState(filters.per_page ?? 10);

  const [showAddUser, setShowAddUser] = useState(false);
  const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
    user: {
      name: '',
      email: '',
      password: '',
      role: 'employee',
    },
  });

  const closeAddUser = () => {
    setShowAddUser(false);
    clearErrors();
    reset();
  };

  const openAddUser = () => setShowAddUser(true);

  const submitAddUser = (e) => {
    e.preventDefault();
    post(route('developer.companies.users.store', company.id), {
      preserveScroll: true,
      onSuccess: () => closeAddUser(),
    });
  };

  const onPerPageChange = (nextPerPage) => {
    setPerPage(nextPerPage);
    router.get(
      route('developer.companies.show', company.id),
      { per_page: nextPerPage, page: 1 },
      {
        preserveState: true,
        preserveScroll: true,
        replace: true,
      }
    );
  };

  const columns = useMemo(
    () => [
      { key: 'name', label: 'Name', className: 'px-4 py-3' },
      { key: 'email', label: 'Email', className: 'px-4 py-3' },
      { key: 'role', label: 'Role', className: 'px-4 py-3' },
    ],
    []
  );

  return (
    <AuthenticatedLayout user={auth.user} header="Developer Settings" contentClassName="max-w-none">
      <Head title={`Developer Settings / Companies / ${company?.name ?? 'Company'}`} />

      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-slate-900">{company?.name ?? 'Company'}</h1>
          <div className="mt-1 text-sm text-slate-600">Users</div>
        </div>

        <div className="flex items-center gap-2">
          <PrimaryButton type="button" onClick={openAddUser}>
            Add User
          </PrimaryButton>
          <SecondaryButton type="button" onClick={() => router.get(route('developer.companies.index'))}>
            Back
          </SecondaryButton>
        </div>
      </div>

      <div className="mt-6">
        <Table
          columns={columns}
          items={users}
          rowKey={(u) => u.id}
          emptyState={<div className="text-sm">No users found.</div>}
          pagination={
            usersPaginator
              ? {
                  meta: usersPaginator?.meta ?? usersPaginator,
                  links: usersPaginator?.links ?? usersPaginator?.meta?.links ?? [],
                  perPage,
                  onPerPageChange,
                }
              : null
          }
          renderRow={(u) => (
            <tr className="align-top">
              <td className="px-4 py-3">
                <div className="font-medium text-slate-900">{u.name || '—'}</div>
              </td>
              <td className="px-4 py-3">
                <div className="text-sm text-slate-700">{u.email || '—'}</div>
              </td>
              <td className="px-4 py-3">
                <div className="text-sm text-slate-700">{u.role || '—'}</div>
              </td>
            </tr>
          )}
        />
      </div>

      <Modal show={showAddUser} onClose={closeAddUser} maxWidth="2xl">
        <div className="p-6">
          <h2 className="text-lg font-semibold text-slate-900">Add User</h2>
          <p className="mt-1 text-sm text-slate-600">Creates a new user under {company?.name ?? 'this company'}.</p>

          <form onSubmit={submitAddUser} className="mt-5 space-y-4">
            <div>
              <InputLabel value="Name" />
              <TextInput
                className="mt-1 block w-full"
                value={data.user.name}
                onChange={(e) => setData('user', { ...data.user, name: e.target.value })}
              />
              <InputError className="mt-1" message={errors['user.name']} />
            </div>

            <div>
              <InputLabel value="Email" />
              <TextInput
                className="mt-1 block w-full"
                value={data.user.email}
                onChange={(e) => setData('user', { ...data.user, email: e.target.value })}
              />
              <InputError className="mt-1" message={errors['user.email']} />
            </div>

            <div>
              <InputLabel value="Temporary Password" />
              <TextInput
                type="password"
                className="mt-1 block w-full"
                value={data.user.password}
                onChange={(e) => setData('user', { ...data.user, password: e.target.value })}
              />
              <InputError className="mt-1" message={errors['user.password']} />
              <div className="mt-1 text-xs text-slate-500">User will be required to change this password on first login.</div>
            </div>

            <div>
              <InputLabel value="Role" />
              <select
                className="mt-1 block w-full rounded-md border-slate-300 bg-white/90 px-3 py-2 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30"
                value={data.user.role}
                onChange={(e) => setData('user', { ...data.user, role: e.target.value })}
              >
                <option value="admin">admin</option>
                <option value="hr">hr</option>
                <option value="manager">manager</option>
                <option value="employee">employee</option>
              </select>
              <InputError className="mt-1" message={errors['user.role']} />
            </div>

            <div className="flex items-center justify-end gap-3 pt-2">
              <SecondaryButton type="button" onClick={closeAddUser} disabled={processing}>
                Cancel
              </SecondaryButton>
              <PrimaryButton type="submit" disabled={processing}>
                {processing ? 'Creating…' : 'Create User'}
              </PrimaryButton>
            </div>
          </form>
        </div>
      </Modal>
    </AuthenticatedLayout>
  );
}
