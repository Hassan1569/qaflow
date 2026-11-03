import React from 'react';
import { Button } from '../common/Button';
import { Input } from '../common/Input';
import { Textarea } from '../common/Textarea';
import { Icon } from '../common/Icon';

export function TestStepEditor({ steps = [], onChange }) {
  const handleAdd = () => {
    onChange([...steps, { action: '', expected_result: '' }]);
  };

  const handleRemove = (index) => {
    const newSteps = [...steps];
    newSteps.splice(index, 1);
    onChange(newSteps);
  };

  const handleChange = (index, field, value) => {
    const newSteps = [...steps];
    newSteps[index] = { ...newSteps[index], [field]: value };
    onChange(newSteps);
  };

  const handleMove = (index, direction) => {
    if (direction === 'up' && index > 0) {
      const newSteps = [...steps];
      [newSteps[index - 1], newSteps[index]] = [newSteps[index], newSteps[index - 1]];
      onChange(newSteps);
    } else if (direction === 'down' && index < steps.length - 1) {
      const newSteps = [...steps];
      [newSteps[index + 1], newSteps[index]] = [newSteps[index], newSteps[index + 1]];
      onChange(newSteps);
    }
  };

  return (
    <div className="space-y-3">
      {steps.length === 0 && (
        <p className="text-sm text-text-muted italic text-center py-4">No steps defined yet.</p>
      )}

      {steps.map((step, index) => (
        <div key={index} className="flex gap-3 items-start bg-surface p-3 rounded-md border border-border">
          <div className="flex flex-col items-center gap-1 mt-2">
            <button 
              type="button" 
              onClick={() => handleMove(index, 'up')} 
              disabled={index === 0}
              className="text-text-muted hover:text-text disabled:opacity-30"
            >
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 15l7-7 7 7" /></svg>
            </button>
            <span className="text-xs font-bold text-text-muted">{index + 1}</span>
            <button 
              type="button" 
              onClick={() => handleMove(index, 'down')} 
              disabled={index === steps.length - 1}
              className="text-text-muted hover:text-text disabled:opacity-30"
            >
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" /></svg>
            </button>
          </div>

          <div className="flex-1 space-y-2">
            <div>
              <label className="text-xs text-text-muted mb-1 block">Action</label>
              <Textarea 
                value={step.action} 
                onChange={(e) => handleChange(index, 'action', e.target.value)}
                placeholder="What action does the tester take?"
                rows={2}
                className="!py-1.5"
              />
            </div>
            <div>
              <label className="text-xs text-text-muted mb-1 block">Expected Result</label>
              <Textarea 
                value={step.expected_result} 
                onChange={(e) => handleChange(index, 'expected_result', e.target.value)}
                placeholder="What should happen?"
                rows={2}
                className="!py-1.5"
              />
            </div>
          </div>

          <button 
            type="button" 
            onClick={() => handleRemove(index)}
            className="mt-6 text-text-muted hover:text-fail p-1"
            title="Remove step"
          >
            <Icon name="close" className="w-4 h-4" />
          </button>
        </div>
      ))}

      <Button type="button" variant="secondary" size="sm" onClick={handleAdd} className="w-full border-dashed">
        + Add Step
      </Button>
    </div>
  );
}