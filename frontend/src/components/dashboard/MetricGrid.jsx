import React from 'react';
import { StatCard } from '../common/StatCard';
import { formatPercent } from '../../utils/format';

export function MetricGrid({ data }) {
  if (!data) return null;

  return (
    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
      <StatCard 
        title="Active Projects" 
        value={data.active_projects} 
        description="Currently active"
      />
      <StatCard 
        title="Requirement Coverage" 
        value={formatPercent(data.requirement_coverage, 1)} 
        description={`${data.total_requirements} total requirements`}
      />
      <StatCard 
        title="Test Pass Rate" 
        value={formatPercent(data.test_pass_rate, 1)} 
        description={`${data.executed_tests} executed tests`}
      />
      <StatCard 
        title="Open Defects" 
        value={data.open_defects} 
        description={`${data.critical_defects} critical`}
        trend={null}
      />
    </div>
  );
}