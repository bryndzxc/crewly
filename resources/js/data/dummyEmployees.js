export const statusOptions = ['All', 'Active', 'Inactive', 'On Leave', 'Terminated'];

export const departmentOptions = ['All', 'Engineering', 'HR', 'Finance', 'Operations', 'Sales'];

export const dummyEmployees = [
    {
        id: 1,
        employeeId: 'CRW-0001',
        firstName: 'Amina',
        lastName: 'Khan',
        fullName: 'Amina Khan',
        status: 'Active',
        department: 'Engineering',
        position: 'Frontend Engineer',
        email: 'amina.khan@crewly.test',
        phone: '+1 (555) 010-1234',
        hireDate: '2024-06-12',
        documents: [
            {
                id: 'doc-1',
                name: 'Employment Contract.pdf',
                type: 'PDF',
                uploadedAt: '2024-06-12',
                size: '228 KB',
            },
            {
                id: 'doc-2',
                name: 'Government ID.png',
                type: 'Image',
                uploadedAt: '2024-06-15',
                size: '1.2 MB',
            },
        ],
        employmentHistory: [
            {
                id: 'hist-1',
                date: '2024-06-12',
                title: 'Joined Crewly',
                detail: 'Hired as Frontend Engineer (Engineering).',
            },
            {
                id: 'hist-2',
                date: '2025-01-10',
                title: 'Role update',
                detail: 'Assigned to HR Portal revamp squad.',
            },
        ],
        notes: [
            {
                id: 'note-1',
                date: '2025-05-02',
                author: 'HR Manager',
                body: 'Great collaboration in cross-functional projects. Frontend-only demo note.',
            },
        ],
    },
    {
        id: 2,
        employeeId: 'CRW-0002',
        firstName: 'Noah',
        lastName: 'Adams',
        fullName: 'Noah Adams',
        status: 'On Leave',
        department: 'Operations',
        position: 'Operations Specialist',
        email: 'noah.adams@crewly.test',
        phone: '+1 (555) 010-2345',
        hireDate: '2023-11-03',
        documents: [
            {
                id: 'doc-3',
                name: 'NDA.pdf',
                type: 'PDF',
                uploadedAt: '2023-11-03',
                size: '94 KB',
            },
        ],
        employmentHistory: [
            {
                id: 'hist-3',
                date: '2023-11-03',
                title: 'Joined Crewly',
                detail: 'Hired as Operations Specialist (Operations).',
            },
        ],
        notes: [
            {
                id: 'note-2',
                date: '2024-10-18',
                author: 'Team Lead',
                body: 'Requested time off for personal reasons (dummy).',
            },
        ],
    },
    {
        id: 3,
        employeeId: 'CRW-0003',
        firstName: 'Sophia',
        lastName: 'Chen',
        fullName: 'Sophia Chen',
        status: 'Inactive',
        department: 'Finance',
        position: 'Accountant',
        email: 'sophia.chen@crewly.test',
        phone: '+1 (555) 010-3456',
        hireDate: '2022-03-21',
        documents: [],
        employmentHistory: [
            {
                id: 'hist-4',
                date: '2022-03-21',
                title: 'Joined Crewly',
                detail: 'Hired as Accountant (Finance).',
            },
        ],
        notes: [],
    },
    {
        id: 4,
        employeeId: 'CRW-0004',
        firstName: 'Mateo',
        lastName: 'Silva',
        fullName: 'Mateo Silva',
        status: 'Active',
        department: 'HR',
        position: 'HR Generalist',
        email: 'mateo.silva@crewly.test',
        phone: '+1 (555) 010-4567',
        hireDate: '2021-09-08',
        documents: [
            {
                id: 'doc-4',
                name: 'Onboarding Checklist.docx',
                type: 'DOCX',
                uploadedAt: '2021-09-08',
                size: '41 KB',
            },
        ],
        employmentHistory: [
            {
                id: 'hist-5',
                date: '2021-09-08',
                title: 'Joined Crewly',
                detail: 'Hired as HR Generalist (HR).',
            },
        ],
        notes: [
            {
                id: 'note-3',
                date: '2025-02-14',
                author: 'HR Director',
                body: 'Supports onboarding initiatives. Frontend-only demo note.',
            },
        ],
    },
    {
        id: 5,
        employeeId: 'CRW-0005',
        firstName: 'Layla',
        lastName: 'Hassan',
        fullName: 'Layla Hassan',
        status: 'Terminated',
        department: 'Sales',
        position: 'Account Executive',
        email: 'layla.hassan@crewly.test',
        phone: '+1 (555) 010-5678',
        hireDate: '2020-01-15',
        documents: [
            {
                id: 'doc-5',
                name: 'Exit Interview Notes.pdf',
                type: 'PDF',
                uploadedAt: '2025-08-01',
                size: '180 KB',
            },
        ],
        employmentHistory: [
            {
                id: 'hist-6',
                date: '2020-01-15',
                title: 'Joined Crewly',
                detail: 'Hired as Account Executive (Sales).',
            },
            {
                id: 'hist-7',
                date: '2025-08-01',
                title: 'Employment ended',
                detail: 'Marked as Terminated (dummy).',
            },
        ],
        notes: [
            {
                id: 'note-4',
                date: '2025-07-20',
                author: 'Sales Manager',
                body: 'Transitioned accounts before leaving (dummy).',
            },
        ],
    },
];
