import React from 'react';
import { formatPercent } from '../../utils/format';

export function TestRunProgress({ progress }) {
  if (!progress) return null;

  const { total, passed, failed, blocked, not_run } = progress;
  const executed = passed + failed + blocked;
  
  const passPct = total > 0 ? (passed / total) * 100 : 0;
  const failPct = total > 0 ? (failed / total) * 100 : 0;
  const blockPct = total > 0 ? (blocked / total) * 100 : 0;
  const notRunPct = total > 0 ? (not_run / total) * 100 : 0;

  return (
    <div className="bg-surface border border-border rounded-md p-5">
      <div className="flex justify-between items-end mb-2">
        <h4 className="text-sm font-medium text-text">Execution Progress</h4>
        <span className="text-sm font-semibold text-text">
          {executed} / {total} ({formatPercent(total > 0 ? (executed/total)*100 : 0)})
        </span>
      </div>
      
      {/* Multi-segment progress bar */}
      <div className="w-full h-3 rounded-full overflow-hidden flex bg-bg border border-border">
        <div style={{ width: `${passPct}%` }} className="bg-pass" title={`Pass: ${passed}`} />
        <div style={{ width: `${failPct}%` }} className="bg-fail" title={`Fail: ${failed}`} />
        <div style={{ width: `${blockPct}%` }} className="bg-blocked" title={`Blocked: ${blocked}`} />
        <div style={{ width: `${notRunPct}%` }} className="bg-not-run" title={`Not Run: ${not_run}`} />
      </div>

      <div className="grid grid-cols-4 gap-2 mt-4 text-center">
        <div className="bg-bg p-2 rounded border border-border">
          <p className="text-lg font-semibold text-pass">{passed}</p>
          <p className="text-xs text-text-muted uppercase tracking-wide">Pass</p>
        </div>
        <div className="bg-bg p-2 rounded border border-border">
          <p className="text-lg font-semibold text-fail">{failed}</p>
          <p className="text-xs text-text-muted uppercase tracking-wide">Fail</p>
        </div>
        <div className="bg-bg p-2 rounded border border-border">
          <p className="text-lg font-semibold text-blocked">{blocked}</p>
          <p className="text-xs text-text-muted uppercase tracking-wide">Blocked</p>
        </div>
        <div className="bg-bg p-2 rounded border border-border">
          <p className="text-lg font-semibold text-not-run">{not_run}</p>
          <p className="text-xs text-text-muted uppercase tracking-wide">Not Run</p>
        </div>
      </div>
    </div>
  );
}