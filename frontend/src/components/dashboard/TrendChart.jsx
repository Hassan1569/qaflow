import React from 'react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import { ChartCard } from '../common/ChartCard';
import { getThemeColors } from '../../styles/tokens';

export function TrendChart({ data }) {
  const colors = getThemeColors();

  // Placeholder for when we have historical data.
  // For now, the API only returns current snapshots.
  const mockData = data || [
    { date: 'Week 1', passed: 12, failed: 4 },
    { date: 'Week 2', passed: 19, failed: 2 },
    { date: 'Week 3', passed: 25, failed: 5 },
    { date: 'Week 4', passed: 32, failed: 1 },
  ];

  return (
    <ChartCard title="Execution Trend" subtitle="Pass/Fail over time">
      <ResponsiveContainer width="100%" height={280}>
        <LineChart data={mockData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
          <CartesianGrid strokeDasharray="3 3" stroke={colors.border} vertical={false} />
          <XAxis dataKey="date" stroke={colors.textMuted} fontSize={12} tickLine={false} axisLine={false} />
          <YAxis stroke={colors.textMuted} fontSize={12} tickLine={false} axisLine={false} />
          <Tooltip 
            contentStyle={{ 
              backgroundColor: colors.surface, 
              borderColor: colors.border,
              color: colors.text,
              borderRadius: '6px',
            }} 
          />
          <Legend verticalAlign="top" height={36} iconType="plainline" />
          <Line type="monotone" dataKey="passed" stroke={colors.pass} strokeWidth={2} dot={{ r: 4 }} activeDot={{ r: 6 }} />
          <Line type="monotone" dataKey="failed" stroke={colors.fail} strokeWidth={2} dot={{ r: 4 }} activeDot={{ r: 6 }} />
        </LineChart>
      </ResponsiveContainer>
    </ChartCard>
  );
}