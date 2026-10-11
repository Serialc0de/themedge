'use client';

export function HighlightSkeleton() {
  return (
    <div className="space-y-4">
      {[1, 2, 3].map((i) => (
        <div
          key={i}
          className="animate-pulse rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900"
        >
          <div className="mb-4 h-3 w-1/3 rounded bg-gray-200 dark:bg-gray-800" />
          <div className="space-y-2">
            <div className="h-4 w-full rounded bg-gray-200 dark:bg-gray-800" />
            <div className="h-4 w-5/6 rounded bg-gray-200 dark:bg-gray-800" />
          </div>
          <div className="mt-4 flex gap-2">
            <div className="h-6 w-16 rounded bg-gray-200 dark:bg-gray-800" />
            <div className="h-6 w-12 rounded bg-gray-200 dark:bg-gray-800" />
          </div>
        </div>
      ))}
    </div>
  );
}
