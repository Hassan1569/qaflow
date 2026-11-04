import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ExecutionPanel } from '../components/execution/ExecutionPanel';
import { ExecutionSidebar } from '../components/execution/ExecutionSidebar';
import { LoadingState } from '../components/common/LoadingState';
import { ErrorState } from '../components/common/ErrorState';
import { Button } from '../components/common/Button';
import { executionService } from '../services/executionService';
import { testRunService } from '../services/testRunService';
import { useToast } from '../hooks/useToast';

export default function ExecutionPage() {
  const { id } = useParams(); // This is the run_case_id
  const navigate = useNavigate();
  const [execution, setExecution] = useState(null);
  const [available, setAvailable] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const toast = useToast();

  const loadData = async () => {
    setLoading(true);
    try {
      const execData = await executionService.get(id);
      setExecution(execData);
      
      const availData = await executionService.getAvailable(id);
      setAvailable(availData);
      
      // Auto-start the run if it's not started yet
      if (execData.run.status === 'not_started') {
        await testRunService.start(execData.run.id);
        // Update local state to avoid re-triggering
        setExecution(prev => ({ ...prev, run: { ...prev.run, status: 'in_progress' } }));
      }
    } catch (err) {
      setError(err.message || 'Failed to load execution data.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id]);

  if (loading) return <LoadingState fullScreen />;
  if (error) return <ErrorState message={error} onRetry={loadData} />;
  if (!execution) return null;

  // Find the next unexecuted test case
  const nextNotRun = available.find(c => (c.result_status === 'not_run' || !c.result_status) && c.run_case_id !== parseInt(id));
  const nextCaseId = nextNotRun ? nextNotRun.run_case_id : null;

  return (
    <div className="h-[calc(100vh-8rem)] flex flex-col">
      <div className="flex justify-between items-center mb-4">
        <div>
          <h1 className="text-xl font-bold text-text">Test Execution</h1>
          <p className="text-sm text-text-muted">{execution.run.name}</p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" onClick={() => navigate(`/test-runs/${execution.run.id}`)}>
            Back to Run
          </Button>
          {nextCaseId && (
            <Button variant="primary" onClick={() => navigate(`/execution/${nextCaseId}`)}>
              Next Test Case
            </Button>
          )}
        </div>
      </div>

      <div className="flex-1 flex gap-6 overflow-hidden">
        <div className="w-80 flex-shrink-0 h-full">
          <ExecutionSidebar 
            available={available} 
            currentId={parseInt(id)} 
            basePath="/execution" 
          />
        </div>
        
        <div className="flex-1 h-full overflow-hidden">
          <ExecutionPanel 
            execution={execution} 
            onUpdate={loadData} 
          />
        </div>
      </div>
    </div>
  );
}