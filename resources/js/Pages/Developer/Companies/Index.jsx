import React, { useMemo, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import Modal from '@/Components/Modal';
import Table from '@/Components/Table';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function Index() {
  const { props } = usePage();
  const auth = props.auth ?? {};
  const filters = props.filters ?? {};
  const companiesPaginator = props.companies ?? null;
  const companies = companiesPaginator?.data ?? (Array.isArray(companiesPaginator) ? companiesPaginator : []);
  const [perPage, setPerPage] = useState(filters.per_page ?? 10);

  const [showCreate, setShowCreate] = useState(false);
  const [createTab, setCreateTab] = useState('company');
  const [showGeneratedPassword, setShowGeneratedPassword] = useState(false);
  const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
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

  const closeCreate = () => {
    setShowCreate(false);
    setCreateTab('company');
    setShowGeneratedPassword(false);
    clearErrors();
    reset();
  };

  const openCreate = () => {
    setShowCreate(true);
    setCreateTab('company');
    setShowGeneratedPassword(false);
  };

  const generatePassword = () => {
    // 16 chars, URL-safe-ish.
    const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    const bytes = new Uint32Array(16);
    if (typeof crypto !== 'undefined' && crypto.getRandomValues) {
      crypto.getRandomValues(bytes);
    } else {
      for (let i = 0; i < bytes.length; i++) bytes[i] = Math.floor(Math.random() * 1_000_000);
    }

    let out = '';
    for (let i = 0; i < bytes.length; i++) {
      out += alphabet[bytes[i] % alphabet.length];
    }

    setData('user', { ...data.user, password: out });
    setShowGeneratedPassword(true);
  };

  const submitCreate = (e) => {
    e.preventDefault();
    post(route('developer.companies.store'), {
      preserveScroll: true,
      onSuccess: () => closeCreate(),
    });
  };

  const onPerPageChange = (nextPerPage) => {
    setPerPage(nextPerPage);
    router.get(
      route('developer.companies.index'),
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
      { key: 'name', label: 'Company', className: 'px-4 py-3' },
      { key: 'slug', label: 'Slug', className: 'px-4 py-3' },
      { key: 'timezone', label: 'Timezone', className: 'px-4 py-3' },
      { key: 'demo', label: 'Demo', className: 'px-4 py-3' },
      { key: 'active', label: 'Active', className: 'px-4 py-3' },
      { key: 'actions', label: 'Actions', className: 'px-4 py-3 text-right whitespace-nowrap' },
    ],
    []
  );

  return (
    <AuthenticatedLayout user={auth.user} header="Developer Settings" contentClassName="max-w-none">
      <Head title="Developer Settings / Companies" />

      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold text-slate-900">Companies</h1>
        <PrimaryButton type="button" onClick={openCreate}>
          Add Company
        </PrimaryButton>
      </div>

      <div className="mt-6">
        <Table
          columns={columns}
          items={companies}
          rowKey={(c) => c.id}
          emptyState={<div className="text-sm">No companies found.</div>}
          pagination={
            companiesPaginator
              ? {
                  meta: companiesPaginator?.meta ?? companiesPaginator,
                  links: companiesPaginator?.links ?? companiesPaginator?.meta?.links ?? [],
                  perPage,
                  onPerPageChange,
                }
              : null
          }
          renderRow={(company) => (
            <tr className="align-top">
              <td className="px-4 py-3">
                <div className="font-semibold text-slate-900">{company.name}</div>
              </td>
              <td className="px-4 py-3">
                <div className="text-sm text-slate-700">{company.slug || '—'}</div>
              </td>
              <td className="px-4 py-3">
                <div className="text-sm text-slate-700">{company.timezone || '—'}</div>
              </td>
              <td className="px-4 py-3">
                <div className="text-sm text-slate-700">{company.is_demo ? 'Yes' : 'No'}</div>
              </td>
              <td className="px-4 py-3">
                <div className="text-sm text-slate-700">{company.is_active ? 'Yes' : 'No'}</div>
              </td>
              <td className="px-4 py-3 text-right whitespace-nowrap">
                <SecondaryButton type="button" onClick={() => router.get(route('developer.companies.show', company.id))}>
                  View
                </SecondaryButton>
              </td>
            </tr>
          )}
        />
      </div>

      <Modal show={showCreate} onClose={closeCreate} maxWidth="4xl">
        <div className="p-6">
          <h2 className="text-lg font-semibold text-slate-900">Add Company</h2>
          <p className="mt-1 text-sm text-slate-600">Creates the company and an initial user. Credentials are emailed to the initial user.</p>

              <div className="mt-5 flex items-center gap-2">
                <button
                  type="button"
                  onClick={() => setCreateTab('company')}
                  className={
                    'rounded-lg px-3 py-2 text-sm font-semibold ring-1 ' +
                    (createTab === 'company'
                      ? 'bg-amber-50 text-amber-900 ring-amber-200'
                      : 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50')
                  }
                >
                  Company Details
                </button>
                <button
                  type="button"
                  onClick={() => setCreateTab('user')}
                  className={
                    'rounded-lg px-3 py-2 text-sm font-semibold ring-1 ' +
                    (createTab === 'user'
                      ? 'bg-amber-50 text-amber-900 ring-amber-200'
                      : 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50')
                  }
                >
                  Initial User
                </button>
              </div>

          <form onSubmit={submitCreate} className="mt-5 space-y-6">
                {createTab === 'company' ? (
                  <div className="rounded-xl border border-slate-200 bg-white p-4">
                    <div className="text-sm font-semibold text-slate-900">Company</div>

                    <div className="mt-4 grid gap-4 sm:grid-cols-2">
                      <div className="sm:col-span-2">
                        <InputLabel value="Name" />
                        <TextInput
                          className="mt-1 block w-full"
                          value={data.company.name}
                          onChange={(e) => setData('company', { ...data.company, name: e.target.value })}
                        />
                        <InputError className="mt-1" message={errors['company.name']} />
                      </div>

                      <div>
                        <InputLabel value="Slug (optional)" />
                        <TextInput
                          className="mt-1 block w-full"
                          value={data.company.slug}
                          onChange={(e) => setData('company', { ...data.company, slug: e.target.value })}
                        />
                        <InputError className="mt-1" message={errors['company.slug']} />
                      </div>

                      <div>
                        <InputLabel value="Timezone (optional)" />
                        <TextInput
                          className="mt-1 block w-full"
                          value={data.company.timezone}
                          onChange={(e) => setData('company', { ...data.company, timezone: e.target.value })}
                        />
                        <InputError className="mt-1" message={errors['company.timezone']} />
                      </div>

                      <div className="sm:col-span-2">
                        <label className="inline-flex items-center gap-2 text-sm text-slate-700">
                          <input
                            type="checkbox"
                            checked={!!data.company.is_active}
                            onChange={(e) => setData('company', { ...data.company, is_active: e.target.checked })}
                          />
                          Active
                        </label>
                      </div>
                    </div>
                  </div>
                ) : (
                  <div className="rounded-xl border border-slate-200 bg-white p-4">
                    <div className="text-sm font-semibold text-slate-900">Initial User</div>

                    <div className="mt-4 grid gap-4 sm:grid-cols-2">
                      <div className="sm:col-span-2">
                        <InputLabel value="Name" />
                        <TextInput
                          className="mt-1 block w-full"
                          value={data.user.name}
                          onChange={(e) => setData('user', { ...data.user, name: e.target.value })}
                        />
                        <InputError className="mt-1" message={errors['user.name']} />
                      </div>

                      <div className="sm:col-span-2">
                        <InputLabel value="Email" />
                        <TextInput
                          className="mt-1 block w-full"
                          value={data.user.email}
                          onChange={(e) => setData('user', { ...data.user, email: e.target.value })}
                        />
                        <InputError className="mt-1" message={errors['user.email']} />
                      </div>

                      <div>
                        <InputLabel value="Password" />
                        <div className="mt-1 flex items-center gap-2">
                          <TextInput
                            type={showGeneratedPassword ? 'text' : 'password'}
                            className="block w-full"
                            value={data.user.password}
                            onChange={(e) => setData('user', { ...data.user, password: e.target.value })}
                          />
                          <SecondaryButton type="button" onClick={generatePassword}>
                            Generate
                          </SecondaryButton>
                        </div>
                        <InputError className="mt-1" message={errors['user.password']} />
                        <div className="mt-1 text-xs text-slate-500">User will be required to change this password on first login.</div>
                        <label className="mt-2 inline-flex items-center gap-2 text-xs text-slate-700">
                          <input
                            type="checkbox"
                            checked={showGeneratedPassword}
                            onChange={(e) => setShowGeneratedPassword(e.target.checked)}
                          />
                          Show password
                        </label>
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
                    </div>
                  </div>
                )}

            <div className="flex items-center justify-end gap-3">
              <SecondaryButton type="button" onClick={closeCreate} disabled={processing}>
                Cancel
              </SecondaryButton>
                  {createTab === 'company' ? (
                    <PrimaryButton type="button" onClick={() => setCreateTab('user')} disabled={processing}>
                      Next
                    </PrimaryButton>
                  ) : null}
              <PrimaryButton type="submit" disabled={processing}>
                {processing ? 'Creating…' : 'Create'}
              </PrimaryButton>
            </div>
          </form>
        </div>
      </Modal>
    </AuthenticatedLayout>
  );
}
