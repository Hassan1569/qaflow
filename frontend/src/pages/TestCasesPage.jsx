import React, { useState, useEffect, useCallback } from 'react';
import { useSearchParams } from 'react-router-dom';
import { PageHeader } from '../components/common/PageHeader';
import { SearchBar } from '../components/common/SearchBar';
import { FilterBar } from '../components/common/FilterBar';
import { Button } from '../components/common/Button';
import { Select } from '../components/common/Select';
import { DataTable } from '../components/common/DataTable';
import { StatusBadge } from '../components/common/StatusBadge';
import { PriorityBadge } from '../components/common/PriorityBadge';
import { TestCaseForm } from '../components/testCases/TestCaseForm';
import { Modal } from '../components/common/Modal';
import { testCaseService } from '../services/testCaseService';
import { projectService } from '../services/projectService';
import { testSuiteService } from '../services/testSuiteService';
import { requirementService } from '../services/requirementService';
import { usePermissions } from '../hooks/usePermissions';
import { useToast } from '../hooks/useToast';
import { TEST_TYPES, PRIORITIES } from '../utils/constants';
import { Link } from 'react-router-dom';

export default function TestCasesPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const projectIdParam = searchParams.get('project_id');
  const suiteIdParam = searchParams.get('suite_id');

  const [testCases, setTestCases] = useState([]);
  const [meta, setMeta] = useState({ page: 1, per_page: 25, total: 0 });
  
  const [projects, setProjects] = useState([]);
  const [suites, setSuites] = useState([]);
  const [requirements, setRequirements] = useState([]);
  
  const [isLoading, setIsLoading] = useState(true);
  
  // Filters
  const [search, setSearch] = useState('');
  const [priority, setPriority] = useState('');
  const [testType, setTestType] = useState('');
  const [selectedProject, setSelectedProject] = useState(projectIdParam || '');
  const [selectedSuite, setSelectedSuite] = useState(suiteIdParam || '');

  // Modal
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { canManageTestCases } = usePermissions();
  const toast = useToast();

  // Load initial data for dropdowns
  useEffect(() => {
    const initData = async () => {
      try {
        const projRes = await projectService.list({ per_page: 100 });
        setProjects(projRes.data);
        
        if (!projectIdParam && projRes.data.length > 0) {
          setSelectedProject(projRes.data[0].id.toString());
        }
      } catch (err) {
        console.error(err);
      }
    };
    initData();
  }, [projectIdParam]);

  // Load project-specific data (suites, requirements) when project changes
  useEffect(() => {
    if (!selectedProject) return;
    
    const loadProjectData = async () => {
      try {
        const suiteRes = await testSuiteService.getTree(selectedProject);
        setSuites(suiteRes);
        
        const reqRes = await requirementService.list({ project_id: selectedProject, per_page: 100 });
        setRequirements(reqRes.data);
      } catch (err) {
        console.error(err);
      }
    };
    loadProjectData();
  }, [selectedProject]);

  const fetchTestCases = useCallback(async (page = meta.page, perPage = meta.per_page) => {
    if (!selectedProject) return;
    
    setIsLoading(true);
    try {
      const res = await testCaseService.list({
        project_id: selectedProject,
        suite_id: selectedSuite,
        page,
        per_page: perPage,
        search,
        priority,
        test_type: testType,
      });
      setTestCases(res.data);
      setMeta(res.meta);
    } catch (err) {
      toast.error(err.message || 'Failed to load test cases.');
    } finally {
      setIsLoading(false);
    }
  }, [selectedProject, selectedSuite, search, priority, testType, meta.page, meta.per_page, toast]);

  useEffect(() => {
    if (selectedProject) {
      const params = { project_id: selectedProject };
      if (selectedSuite) params.suite_id = selectedSuite;
      setSearchParams(params);
      
      fetchTestCases(1);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selectedProject, selectedSuite, search, priority, testType]);

  const handleCreate = async (data) => {
    setIsSubmitting(true);
    try {
      await testCaseService.create({ ...data, project_id: selectedProject });
      toast.success('Test case created successfully.');
      setIsModalOpen(false);
      fetchTestCases(1);
    } catch (err) {
      toast.error(err.message || 'Failed to create test case.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const columns = [
    {
      key: 'code',
      label: 'Code',
      className: 'w-24',
      render: (row) => <span className="font-mono text-xs font-medium text-text-muted">{row.code}</span>
    },
    {
      key: 'title',
      label: 'Title',
      render: (row) => (
        <Link to={`/test-cases/${row.id}`} className="text-primary hover:underline font-medium">
          {row.title}
        </Link>
      )
    },
    {
      key: 'suite_name',
      label: 'Suite',
      className: 'w-40',
      render: (row) => <span className="text-sm text-text-muted">{row.suite_name || '—'}</span>
    },
    {
      key: 'priority',
      label: 'Priority',
      className: 'w-28',
      render: (row) => <PriorityBadge priority={row.priority} />
    },
    {
      key: 'test_type',
      label: 'Type',
      className: 'w-32',
      render: (row) => <StatusBadge status={row.test_type} />
    },
    {
      key: 'automation_status',
      label: 'Automation',
      className: 'w-32',
      render: (row) => <span className="text-sm capitalize text-text-muted">{row.automation_status}</span>
    }
  ];

  return (
    <div>
      <PageHeader 
        title="Test Cases" 
        description="Manage your test repository and author new tests."
        action={
          canManageTestCases() && selectedProject && (
            <Button onClick={() => setIsModalOpen(true)}>Create Test Case</Button>
          )
        }
      />

      <FilterBar>
        <div className="flex-1">
          <SearchBar onSearch={setSearch} placeholder="Search test cases..." />
        </div>
        <div className="flex items-center gap-2">
          <span className="text-xs text-text-muted">Project:</span>
          <Select value={selectedProject} onChange={(e) => { setSelectedProject(e.target.value); setSelectedSuite(''); }} className="!py-1.5 !text-sm">
            {projects.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
          </Select>
        </div>
        <Select value={selectedSuite} onChange={(e) => setSelectedSuite(e.target.value)} className="!py-1.5 !text-sm w-40">
          <option value="">All Suites</option>
          {suites.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
        </Select>
        <Select value={priority} onChange={(e) => setPriority(e.target.value)} className="!py-1.5 !text-sm w-32">
          <option value="">Priority</option>
          {PRIORITIES.map(p => <option key={p.value} value={p.value}>{p.label}</option>)}
        </Select>
        <Select value={testType} onChange={(e) => setTestType(e.target.value)} className="!py-1.5 !text-sm w-36">
          <option value="">All Types</option>
          {TEST_TYPES.map(t => <option key={t.value} value={t.value}>{t.label}</option>)}
        </Select>
      </FilterBar>

      <DataTable 
        columns={columns}
        data={testCases}
        isLoading={isLoading}
        emptyTitle="No test cases found"
        emptyDescription={search ? `No results for "${search}"` : "Create your first test case to begin building the test repository."}
        emptyAction={{ label: 'Create Test Case', onClick: () => setIsModalOpen(true) }}
        pagination={{
          page: meta.page,
          perPage: meta.per_page,
          total: meta.total,
          onPageChange: (p) => fetchTestCases(p),
          onPerPageChange: (pp) => fetchTestCases(1, pp)
        }}
      />

      <Modal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} title="Create Test Case" maxWidth="max-w-4xl">
        <TestCaseForm 
          projectId={selectedProject}
          suites={suites}
          requirements={requirements}
          onSubmit={handleCreate}
          onCancel={() => setIsModalOpen(false)}
          isLoading={isSubmitting}
        />
      </Modal>
    </div>
  );
}