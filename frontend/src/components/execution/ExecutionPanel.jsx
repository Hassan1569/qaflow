import React, { useState, useEffect } from 'react';
import { Button } from '../common/Button';
import { Textarea } from '../common/Textarea';
import { StepResultRow } from './StepResultRow';
import { StatusBadge } from '../common/StatusBadge';
import { executionService } from '../../services/executionService';
import { useToast } from '../../hooks/useToast';

export function ExecutionPanel({ execution, onUpdate }) {
  const [steps, setSteps] = useState(execution.steps || []);
  const [overallStatus, setOverallStatus] = useState(execution.result.status);
  const [actualResult, setActualResult] = useState(execution.result.actual_result || '');
  const [notes, setNotes] = useState(execution.result.notes || '');
  const [isSaving, setIsSaving] = useState(false);
  const toast = useToast();

  // Sync when a new execution is loaded
  useEffect(() => {
    setSteps(execution.steps || []);
    setOverallStatus(execution.result.status);
    setActualResult(execution.result.actual_result || '');
    setNotes(execution.result.notes || '');
  }, [execution]);

  const handleStepUpdate = async (stepOrder, data) => {
    try {
      // Optimistic UI update
      setSteps(prev => prev.map(s => 
        s.step_order === stepOrder ? { ...s, ...data } : s
      ));
      
      const res = await executionService.saveStepResult(execution.run_case.id, stepOrder, data);
      
      // Update overall status if computed
      if (res.overall) {
        setOverallStatus(res.overall);
      }
      if (onUpdate) onUpdate(); // trigger parent refresh
    } catch (err) {
      toast.error(err.message || 'Failed to save step result.');
    }
  };

  const handleSaveOverall = async (status) => {
    setIsSaving(true);
    try {
      await executionService.saveResult(execution.run_case.id, {
        status,
        actual_result: actualResult,
        notes: notes
      });
      setOverallStatus(status);
      toast.success('Overall result saved.');
      if (onUpdate) onUpdate();
    } catch (err) {
      toast.error(err.message || 'Failed to save result.');
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <div className="bg-surface border border-border rounded-md shadow-card flex flex-col h-full">
      <div className="p-5 border-b border-border flex justify-between items-center bg-bg">
        <div>
          <h2 className="text-lg font-bold text-text">{execution.test_case.code}</h2>
          <p className="text-sm text-text-muted">{execution.test_case.title}</p>
        </div>
        <div>
          <span className="text-xs text-text-muted uppercase mr-2">Current Result:</span>
          <StatusBadge status={overallStatus} />
        </div>
      </div>

      <div className="p-5 overflow-y-auto flex-1 space-y-6">
        {execution.test_case.description && (
          <div>
            <h4 className="text-xs font-semibold text-text-muted uppercase mb-1">Description</h4>
            <p className="text-sm text-text">{execution.test_case.description}</p>
          </div>
        )}

        {execution.test_case.preconditions && (
          <div>
            <h4 className="text-xs font-semibold text-text-muted uppercase mb-1">Preconditions</h4>
            <p className="text-sm text-text bg-bg p-3 rounded border border-border">{execution.test_case.preconditions}</p>
          </div>
        )}

        <div>
          <h4 className="text-xs font-semibold text-text-muted uppercase mb-3">Steps</h4>
          <div className="space-y-3">
            {steps.map((step, idx) => (
              <StepResultRow 
                key={idx} 
                step={step} 
                onChange={(data) => handleStepUpdate(step.step_order, data)} 
              />
            ))}
          </div>
        </div>

        <div className="border-t border-border pt-6">
          <h4 className="text-sm font-semibold text-text mb-3">Overall Result</h4>
          <div className="space-y-4">
            <div>
              <label className="block text-xs font-medium text-text-muted mb-1">Actual Result / Observations</label>
              <Textarea 
                value={actualResult} 
                onChange={(e) => setActualResult(e.target.value)}
                rows={3}
                placeholder="Enter the overall actual result..."
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-text-muted mb-1">Notes</label>
              <Textarea 
                value={notes} 
                onChange={(e) => setNotes(e.target.value)}
                rows={2}
                placeholder="Any additional notes..."
              />
            </div>
            <div className="flex gap-2 pt-2">
              <Button variant="secondary" className="flex-1 !border-pass !text-pass hover:!bg-pass/10" onClick={() => handleSaveOverall('pass')} isLoading={isSaving}>Pass</Button>
              <Button variant="secondary" className="flex-1 !border-fail !text-fail hover:!bg-fail/10" onClick={() => handleSaveOverall('fail')} isLoading={isSaving}>Fail</Button>
              <Button variant="secondary" className="flex-1 !border-blocked !text-blocked hover:!bg-blocked/10" onClick={() => handleSaveOverall('blocked')} isLoading={isSaving}>Blocked</Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}