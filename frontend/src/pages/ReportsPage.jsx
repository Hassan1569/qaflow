import React, { useState, useEffect } from 'react';
import { PageHeader } from '../components/common/PageHeader';
import { Select } from '../components/common/Select';
import { Button } from '../components/common/Button';
import { LoadingState } from '../components/common/LoadingState';
import { ErrorState } from '../components/common/ErrorState';
import { Tabs } from '../components/common/Tabs';
import { ChartCard } from '../components/common/ChartCard';
import { exportToCsv } from '../utils/csv';
import { reportService } from '../services/reportService';
import { projectService } from '../services/projectService';
import { useToast } from '../hooks/useToast';

export default function ReportsPage() {
  const [projects, setProjects] = useState([]);
  const [selectedProject, setSelectedProject] = useState('');
  const [activeTab, setActiveTab] = useState('execution');
  
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  
  const toast = useToast();

  useEffect(() => {
    const loadProjects = async () => {
      try {
        const res = await projectService.list({ per_page: 100 });
        setProjects(res.data);
        if (res.data.length > 0) {
          setSelectedProject(res.data[0].id.toString());
        }
      } catch (err) {
        console.error(err);
      }
    };
    loadProjects();
  }, []);

  useEffect(() => {
    if (!selectedProject) return;
    
    const fetchData = async () => {
      setLoading(true);
      setError(null);
      setData(null);
      try {
        let result;
        if (activeTab === 'execution') result = await reportService.getExecution(selectedProject);
        else if (activeTab === 'defects') result = await reportService.getDefects(selectedProject);
        else if (activeTab === 'coverage') result = await reportService.getCoverage(selectedProject);
        else if (activeTab === 'summary') result = await reportService.getProjectSummary(selectedProject);
        setData(result);
      } catch (err) {
        setError(err.message || 'Failed to load report data.');
      } finally {
        setLoading(false);
      }
    };
    
    fetchData();
  }, [selectedProject, activeTab]);

  const handleExport = () => {
    if (!data) return;
    
    // Define columns based on active tab
    let columns = [];
    let filename = `qaflow-${activeTab}-report.csv`;
    
    if (activeTab === 'execution') {
      columns = [
        { key: 'metric', label: 'Metric' },
        { key: 'value', label: 'Value' }
      ];
      // Flatten object for CSV
      const rows = [
        { metric: 'Total Tests', value: data.total },
        { metric: 'Executed', value: data.executed },
        { metric: 'Passed', value: data.passed },
        { metric: 'Failed', value: data.failed },
        { metric: 'Blocked', value: data.blocked },
        { metric: 'Not Run', value: data.not_run },
        { metric: 'Pass Rate', value: `${data.pass_rate}%` },
      ];
      exportToCsv(rows, columns, filename);
      return;
    } 
    
    if (activeTab === 'defects') {
      columns = [
        { key: 'metric', label: 'Metric' },
        { key: 'value', label: 'Count' }
      ];
      const rows = [
        { metric: 'Total Defects', value: data.total },
        { metric: 'Open', value: data.open },
        { metric: 'Critical Open', value: data.critical_open },
        ...Object.entries(data.by_severity).map(([k, v]) => ({ metric: `Severity: ${k}`, value: v }))
      ];
      exportToCsv(rows, columns, filename);
      return;
    }
    
    toast.info('CSV Export not fully implemented for this view yet.');
  };

  const tabs = [
    { id: 'execution', label: 'Test Execution' },
    { id: 'defects', label: 'Defects' },
    { id: 'coverage', label: 'Coverage' },
    { id: 'summary', label: 'Project Summary' },
  ];

  const renderContent = () => {
    if (loading) return <LoadingState />;
    if (error) return <ErrorState message={error} />;
    if (!data) return null;

    if (activeTab === 'execution') {
      return (
        <div className="space-y-6">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="bg-surface p-4 rounded-md border border-border">
              <p className="text-sm text-text-muted mb-1">Total Tests</p>
              <p className="text-2xl font-bold text-text">{data.total}</p>
            </div>
            <div className="bg-surface p-4 rounded-md border border-border">
              <p className="text-sm text-text-muted mb-1">Pass Rate</p>
              <p className="text-2xl font-bold text-pass">{data.pass_rate}%</p>
            </div>
            <div className="bg-surface p-4 rounded-md border border-border">
              <p className="text-sm text-text-muted mb-1">Execution Rate</p>
              <p className="text-2xl font-bold text-primary">{data.execution_rate}%</p>
            </div>
            <div className="bg-surface p-4 rounded-md border border-border">
              <p className="text-sm text-text-muted mb-1">Failed Tests</p>
              <p className="text-2xl font-bold text-fail">{data.failed}</p>
            </div>
          </div>
          <ChartCard title="Execution Breakdown">
             <ul className="divide-y divide-border">
               <li className="py-2 flex justify-between"><span>Executed</span> <span className="font-bold">{data.executed}</span></li>
               <li className="py-2 flex justify-between"><span>Passed</span> <span className="font-bold text-pass">{data.passed}</span></li>
               <li className="py-2 flex justify-between"><span>Failed</span> <span className="font-bold text-fail">{data.failed}</span></li>
               <li className="py-2 flex justify-between"><span>Blocked</span> <span className="font-bold text-blocked">{data.blocked}</span></li>
               <li className="py-2 flex justify-between"><span>Not Run</span> <span className="font-bold text-not-run">{data.not_run}</span></li>
             </ul>
          </ChartCard>
        </div>
      );
    }

    if (activeTab === 'defects') {
      return (
        <div className="space-y-6">
          <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div className="bg-surface p-4 rounded-md border border-border">
              <p className="text-sm text-text-muted mb-1">Total Defects</p>
              <p className="text-2xl font-bold text-text">{data.total}</p>
            </div>
            <div className="bg-surface p-4 rounded-md border border-border">
              <p className="text-sm text-text-muted mb-1">Open Defects</p>
              <p className="text-2xl font-bold text-fail">{data.open}</p>
            </div>
            <div className="bg-surface p-4 rounded-md border border-border">
              <p className="text-sm text-text-muted mb-1">Critical Open</p>
              <p className="text-2xl font-bold text-critical">{data.critical_open}</p>
            </div>
          </div>
          <ChartCard title="Defects by Severity">
             <ul className="divide-y divide-border">
               {Object.entries(data.by_severity).map(([sev, count]) => (
                 <li key={sev} className="py-2 flex justify-between capitalize">
                   <span>{sev}</span> <span className="font-bold">{count}</span>
                 </li>
               ))}
             </ul>
          </ChartCard>
        </div>
      );
    }

    if (activeTab === 'coverage') {
      return (
        <div className="space-y-6">
          <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div className="bg-surface p-4 rounded-md border border-border">
              <p className="text-sm text-text-muted mb-1">Total Requirements</p>
              <p className="text-2xl font-bold text-text">{data.total}</p>
            </div>
            <div className="bg-surface p-4 rounded-md border border-border">
              <p className="text-sm text-text-muted mb-1">Covered</p>
              <p className="text-2xl font-bold text-pass">{data.covered}</p>
            </div>
            <div className="bg-surface p-4 rounded-md border border-border">
              <p className="text-sm text-text-muted mb-1">Coverage %</p>
              <p className="text-2xl font-bold text-primary">{data.coverage}%</p>
            </div>
          </div>
        </div>
      );
    }

    if (activeTab === 'summary') {
      return (
        <div className="bg-surface border border-border rounded-md p-6">
          <h3 className="text-lg font-semibold mb-4">Project: {data.project.name}</h3>
          <pre className="bg-bg p-4 rounded border border-border text-xs overflow-auto">
            {JSON.stringify(data, null, 2)}
          </pre>
        </div>
      );
    }

    return null;
  };

  return (
    <div>
      <PageHeader 
        title="Reports" 
        description="Generate and export project QA metrics."
        action={
          <Button variant="secondary" onClick={handleExport} disabled={!data}>Export CSV</Button>
        }
      />

      <div className="mb-6 flex items-center gap-2 bg-surface p-3 rounded-md border border-border w-fit">
        <span className="text-sm font-medium text-text-muted">Project:</span>
        <Select value={selectedProject} onChange={(e) => setSelectedProject(e.target.value)} className="!py-1.5 !text-sm min-w-[200px]">
          {projects.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
        </Select>
      </div>

      <Tabs tabs={tabs} activeTab={activeTab} onChange={setActiveTab} />

      {renderContent()}
    </div>
  );
}