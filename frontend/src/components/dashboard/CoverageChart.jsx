import React from 'react';
import { PieChart, Pie, Cell, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import { ChartCard } from '../common/ChartCard';
import { getThemeColors } from '../../styles/tokens';

export function CoverageChart({ data }) {
  if (!data || data.length === 0) return null;

  const colors = getThemeColors();
  const COLORS = {
    Covered: colors.pass,
    Uncovered: colors.notRun,
  };

  return (
    <ChartCard title="Requirement Coverage" subtitle="Percentage of covered requirements">
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
            }} 
          />
          <Legend verticalAlign="bottom" height={36} iconType="circle" />
        </PieChart>
      </ResponsiveContainer>
    </ChartCard>
  );
}