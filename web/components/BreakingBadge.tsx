interface Props {
  size?: 'sm' | 'md' | 'lg';
  animated?: boolean;
}

export default function BreakingBadge({ size = 'md', animated = true }: Props) {
  const sizeClasses = {
    sm: 'text-[10px] px-1.5 py-0.5',
    md: 'text-xs px-2 py-1',
    lg: 'text-sm px-3 py-1.5',
  };

  return (
    <span
      className={`inline-flex items-center gap-1.5 bg-red-700 text-white font-bold uppercase tracking-widest ${sizeClasses[size]} ${animated ? 'animate-pulse' : ''}`}
    >
      <span className="w-1.5 h-1.5 bg-white rounded-full" />
      ULTIM'ORA
    </span>
  );
}
