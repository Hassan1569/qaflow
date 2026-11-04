import React, { useState, useEffect } from 'react';
import { PageHeader } from '../components/common/PageHeader';
import { MetricGrid } from '../components/dashboard/MetricGrid';
import { ExecutionStatusChart } from '../components/dashboard/ExecutionStatusChart';
import { DefectSeverityChart } from '../components/dashboard/DefectSeverityChart';
import { CoverageChart } from '../components/dashboard/CoverageChart';
import { TrendChart } from '../components/dashboard/TrendChart';
import { RecentActivity } from '../components/dashboard/RecentActivity';
import { LoadingState } from '../components/common/LoadingState';
import { ErrorState } from '../components/common/ErrorState';
import { Select } from '../components/common/Select';
import { dashboardService } from '../services/dashboardService';
import { projectService } from '../services/projectService';
import { activityService } from '../services/activityService';

export default function DashboardPage() {
  const [summary, setSummary] = useState(null);
  const [charts, setCharts] = useState(null);
  const [activities, setActivities] = useState([]);
  const [projects, setProjects] = useState([]);
  const [selectedProject, setSelectedProject] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Load projects for the filter
  useEffect(() => {
    const loadProjects = async () => {
      try {
        const res = await projectService.list({ per_page: 100 });
        setProjects(res.data);
        if (res.data.length > 0) {
          setSelectedProject(res.data[0].id);
        }
      } catch (err) {
        console.error(err);
      }
    };
    loadProjects();
  }, []);

  // Load dashboard data when project changes
  useEffect(() => {
    const loadData = async () => {
      setLoading(true);
      setError(null);
      try {
        // If no project selected yet, just get the global summary
        const summaryRes = await dashboardService.getSummary(selectedProject || null);
        setSummary(summaryRes);

        if (selectedProject) {
          const chartsRes = await dashboardService.getCharts(selectedProject);
          setCharts(chartsRes);

          const activityRes = await activityService.list({ project_id: selectedProject, per_page: 5 });
          setActivities(activityRes.data);
        }
      } catch (err) {
        setError(err.message || 'Failed to load dashboard data.');
      } finally {
        setLoading(false);
      }
    };
    
    // Only load if projects are loaded (to avoid double fetch on init)
    if (projects.length > 0 || selectedProject === '') {
      loadData();
    }
  }, [selectedProject, projects.length]);

  if (error) {
    return <ErrorState message={error} onRetry={() => window.location.reload()} />;
  }

  return (
    <div>
      <PageHeader 
        title="Dashboard" 
        description="Overview of your QA metrics and testing progress."
        action={
          <div className="flex items-center gap-2">
            <span className="text-sm text-text-muted">Project:</span>
            <Select 
              value={selectedProject} 
              onChange={(e) => setSelectedProject(e.target.value)}
              className="!py-1.5 !text-sm w-48"
            >
              <option value="">All Projects</option>
              {projects.map(p => (
                <option key={p.id} value={p.id}>{p.name}</option>
              ))}
            </Select>
          </div>
        }
      />

      {loading ? (
        <LoadingState message="Loading dashboard..." />
      ) : (
        <div className="space-y-6">
          {summary && <MetricGrid data={summary} />}

          {selectedProject && charts ? (
            <>
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <ExecutionStatusChart data={charts.execution_status} />
                <DefectSeverityChart data={charts.defect_severity} />
              </div>
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2">
                  <TrendChart />
                </div>
                <div>
                  <CoverageChart data={charts.coverage} />
                </div>
              </div>
              <div className="grid grid-cols-1 gap-6">
                <RecentActivity activities={activities} />
              </div>
            </>
          ) : (
            <div className="bg-surface border border-border rounded-md p-12 text-center shadow-card">
              <h3 className="text-lg font-medium text-text mb-2">All Projects Summary</h3>
              <p className="text-sm text-text-muted">
                Select a specific project from the dropdown above to view detailed charts and recent activity.
              </p>
            </div>
          )}
        </div>
      )}
    </div>
  );
}