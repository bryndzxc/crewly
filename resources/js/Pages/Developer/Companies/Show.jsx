import React, { useMemo, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import Table from '@/Components/Table';
import SecondaryButton from '@/Components/SecondaryButton';

export default function Show() {
  const { props } = usePage();
  const auth = props.auth ?? {};
  const company = props.company ?? {};
  const filters = props.filters ?? {};

  const usersPaginator = props.users ?? null;
  const users = usersPaginator?.data ?? (Array.isArray(usersPaginator) ? usersPaginator : []);

  const [perPage, setPerPage] = useState(filters.per_page ?? 10);

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

        <SecondaryButton type="button" onClick={() => router.get(route('developer.companies.index'))}>
          Back
        </SecondaryButton>
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
    </AuthenticatedLayout>
  );
}
