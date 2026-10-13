'use client';

import { forwardRef, useEffect, useState } from 'react';
import {
  LineChart, Line, BarChart, Bar, AreaChart, Area, PieChart, Pie, Cell,
  XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
} from 'recharts';
import type { ScrollySectionData } from '../ScrollyArticle';

interface Props {
  section: ScrollySectionData;
  isVisible: boolean;
}

interface ChartDataPoint {
  label: string;
  value: number;
  [key: string]: string | number;
}

const COLORS = ['#dc2626', '#ea580c', '#ca8a04', '#16a34a', '#0891b2', '#7c3aed', '#db2777'];

export const ChartSection = forwardRef<HTMLElement, Props>(function ChartSection(
  { section, isVisible },
  ref
) {
  const rawData = section.data?.data ?? section.data?.values ?? [];
  const chartType = (section.data?.chart_type as string) || 'line';
  const xKey = (section.data?.x_key as string) || 'label';
  const yKey = (section.data?.y_key as string) || 'value';
  const color = (section.data?.color as string) || '#dc2626';

  const data: ChartDataPoint[] = Array.isArray(rawData) ? (rawData as ChartDataPoint[]) : [];

  // Delay di rendering per dare tempo al container di avere dimensioni
  const [mounted, setMounted] = useState(false);
  useEffect(() => {
    if (isVisible) {
      const t = setTimeout(() => setMounted(true), 100);
      return () => clearTimeout(t);
    }
  }, [isVisible]);

  const renderChart = () => {
    switch (chartType) {
      case 'bar':
        return (
          <BarChart data={data}>
            <CartesianGrid strokeDasharray="3 3" opacity={0.2} />
            <XAxis dataKey={xKey} />
            <YAxis />
            <Tooltip
              contentStyle={{ background: '#111', color: '#fff', border: 'none', borderRadius: 8 }}
              labelStyle={{ color: '#fff' }}
            />
            <Bar dataKey={yKey} fill={color} radius={[4, 4, 0, 0]} />
          </BarChart>
        );
      case 'area':
        return (
          <AreaChart data={data}>
            <defs>
              <linearGradient id="areaGradient" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stopColor={color} stopOpacity={0.6} />
                <stop offset="100%" stopColor={color} stopOpacity={0.05} />
              </linearGradient>
            </defs>
            <CartesianGrid strokeDasharray="3 3" opacity={0.2} />
            <XAxis dataKey={xKey} />
            <YAxis />
            <Tooltip
              contentStyle={{ background: '#111', color: '#fff', border: 'none', borderRadius: 8 }}
              labelStyle={{ color: '#fff' }}
            />
            <Area type="monotone" dataKey={yKey} stroke={color} fill="url(#areaGradient)" strokeWidth={2} />
          </AreaChart>
        );
      case 'pie':
        return (
          <PieChart>
            <Pie data={data} dataKey={yKey} nameKey={xKey} cx="50%" cy="50%" outerRadius="80%" label>
              {data.map((_, index) => (
                <Cell key={index} fill={COLORS[index % COLORS.length]} />
              ))}
            </Pie>
            <Tooltip contentStyle={{ background: '#111', color: '#fff', border: 'none', borderRadius: 8 }} />
          </PieChart>
        );
      case 'line':
      default:
        return (
          <LineChart data={data}>
            <CartesianGrid strokeDasharray="3 3" opacity={0.2} />
            <XAxis dataKey={xKey} />
            <YAxis />
            <Tooltip
              contentStyle={{ background: '#111', color: '#fff', border: 'none', borderRadius: 8 }}
              labelStyle={{ color: '#fff' }}
            />
            <Line
              type="monotone"
              dataKey={yKey}
              stroke={color}
              strokeWidth={3}
              dot={{ r: 4, fill: color }}
              activeDot={{ r: 6 }}
            />
          </LineChart>
        );
    }
  };

  return (
    <section ref={ref} className="py-24 sm:py-32 bg-gray-50 dark:bg-gray-900">
      <div className="max-w-6xl mx-auto px-4">
        {section.content && (
          <h2
            className={`text-2xl sm:text-3xl font-bold text-center mb-10 text-gray-900 dark:text-gray-100 transition-all duration-1000 ${
              isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'
            }`}
            dangerouslySetInnerHTML={{ __html: section.content }}
          />
        )}

        <div
          className={`w-full transition-all duration-1000 ${
            isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'
          }`}
          style={{ height: 400 }}
        >
          {data.length > 0 && mounted ? (
            <ResponsiveContainer width="100%" height={400}>
              {renderChart()}
            </ResponsiveContainer>
          ) : (
            <div className="flex items-center justify-center h-full text-gray-500">
              {data.length === 0 ? 'Nessun dato disponibile' : ''}
            </div>
          )}
        </div>
      </div>
    </section>
  );
});
