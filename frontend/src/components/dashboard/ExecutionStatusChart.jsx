import React from 'react';
import { PieChart, Pie, Cell, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import { ChartCard } from '../common/ChartCard';
import { getThemeColors } from '../../styles/tokens';

export function ExecutionStatusChart({ data }) {
  if (!data || data.length === 0) return null;

  const colors = getThemeColors();
  const COLORS = {
    Pass: colors.pass,
    Fail: colors.fail,
    Blocked: colors.blocked,
    'Not Run': colors.notRun,
  };

  return (
    <ChartCard title="Execution Status" subtitle="Current test results by status">
      <ResponsiveContainer width="100%" height={280}>
        <PieChart>
          <Pie
            data={data}
            cx="50%"
            cy="50%"
            innerRadius={60}
            outerRadius={80}
            paddingAngle={2}
            dataKey="value"
            stroke="none"
          >
            {data.map((entry, index) => (
              <Cell key={`cell-${index}`} fill={COLORS[entry.name] || colors.primary} />
            ))}
          </Pie>
          <Tooltip 
            contentStyle={{ 
              backgroundColor: colors.surface, 
              borderColor: colors.border,
              color: colors.text,
              borderRadius: '6px',
              boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)'
            }} 
          />
          <Legend verticalAlign="bottom" height={36} iconType="circle" />
        </PieChart>
      </ResponsiveContainer>
    </ChartCard>
  );
}