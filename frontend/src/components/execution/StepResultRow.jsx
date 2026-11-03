import React, { useState } from 'react';
import { Textarea } from '../common/Textarea';

export function StepResultRow({ step, onChange }) {
  const [actualResult, setActualResult] = useState(step.actual_result || '');
  const [notes, setNotes] = useState(step.notes || '');
  const [isExpanded, setIsExpanded] = useState(step.status === 'fail' || step.status === 'blocked');

  const handleStatusClick = (status) => {
    onChange({ status, actual_result: actualResult, notes });
    if (status === 'fail' || status === 'blocked') {
      setIsExpanded(true);
    }
  };

  const handleBlur = () => {
    // Only save text changes if a status is already selected
    if (step.status !== 'not_run') {
      onChange({ status: step.status, actual_result: actualResult, notes });
    }
  };

  const getStatusButtonClass = (status) => {
    const base = "px-3 py-1 text-xs font-medium rounded-md border transition-colors focus-ring ";
    if (step.status === status) {
      switch (status) {
        case 'pass': return base + "bg-pass text-white border-pass";
        case 'fail': return base + "bg-fail text-white border-fail";
        case 'blocked': return base + "bg-blocked text-white border-blocked";
        default: return base + "bg-bg text-text border-border";
      }
    }
    return base + "bg-surface text-text-muted border-border hover:bg-bg";
  };

  return (
    <div className={`border rounded-md p-3 transition-colors ${step.status === 'fail' ? 'border-fail/50 bg-fail/5' : 'border-border bg-surface'}`}>
      <div className="flex gap-4">
        <div className="flex-shrink-0 w-6 h-6 rounded-full bg-bg border border-border flex items-center justify-center text-xs font-bold text-text-muted mt-0.5">
          {step.step_order}
        </div>
        
        <div className="flex-1 space-y-2">
          <div>
            <p className="text-sm font-medium text-text">{step.action}</p>
            {step.expected_result && (
              <p className="text-xs text-text-muted mt-1"><span className="font-semibold">Expected:</span> {step.expected_result}</p>
            )}
          </div>

          <div className="flex gap-2">
            <button type="button" className={getStatusButtonClass('pass')} onClick={() => handleStatusClick('pass')}>Pass</button>
            <button type="button" className={getStatusButtonClass('fail')} onClick={() => handleStatusClick('fail')}>Fail</button>
            <button type="button" className={getStatusButtonClass('blocked')} onClick={() => handleStatusClick('blocked')}>Blocked</button>
            <button type="button" className={getStatusButtonClass('not_run')} onClick={() => handleStatusClick('not_run')}>Skip</button>
          </div>

          {isExpanded && (
            <div className="pt-2 space-y-2">
              <div>
                <label className="text-xs text-text-muted mb-1 block">Actual Result</label>
                <Textarea 
                  value={actualResult} 
                  onChange={(e) => setActualResult(e.target.value)}
                  onBlur={handleBlur}
                  placeholder="What actually happened?"
                  rows={2}
                  className="!text-xs !py-1.5"
                />
              </div>
              <div>
                <label className="text-xs text-text-muted mb-1 block">Notes</label>
                <Textarea 
                  value={notes} 
                  onChange={(e) => setNotes(e.target.value)}
                  onBlur={handleBlur}
                  placeholder="Any additional context..."
                  rows={1}
                  className="!text-xs !py-1.5"
                />
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}