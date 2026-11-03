import React from 'react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Cell } from 'recharts';
import { ChartCard } from '../common/ChartCard';
import { getThemeColors } from '../../styles/tokens';

export function DefectSeverityChart({ data }) {
  if (!data || data.length === 0) return null;

  const colors = getThemeColors();
  const COLORS = {
    Critical: colors.critical,
    High: colors.high,
    Medium: colors.medium,
    Low: colors.low,
  };

  return (
    <ChartCard title="Defects by Severity" subtitle="Current open defects">
      <ResponsiveContainer width="100%" height={280}>
        <BarChart data={data} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
          <CartesianGrid strokeDasharray="3 3" stroke={colors.border} vertical={false} />
          <XAxis dataKey="name" stroke={colors.textMuted} fontSize={12} tickLine={false} axisLine={false} />
          <YAxis stroke={colors.textMuted} fontSize={12} tickLine={false} axisLine={false} />
          <Tooltip 
            cursor={{ fill: colors.bg }}
            contentStyle={{ 
              backgroundColor: colors.surface, 
              borderColor: colors.border,
              color: colors.text,
              borderRadius: '6px',
            }} 
          />
          <Bar dataKey="value" radius={[4, 4, 0, 0]}>
            {data.map((entry, index) => (
              <Cell key={`cell-${index}`} fill={COLORS[entry.name] || colors.primary} />
            ))}
          </Bar>
        </BarChart>
      </ResponsiveContainer>
    </ChartCard>
  );
}