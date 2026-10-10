'use client';

import { useEffect, useState } from 'react';

export default function ScrollToTop() {
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    function toggleVisible() { setVisible(window.scrollY > 400); }
    window.addEventListener('scroll', toggleVisible);
    return () => window.removeEventListener('scroll', toggleVisible);
  }, []);

  function scrollToTop() { window.scrollTo({ top: 0, behavior: 'smooth' }); }

  if (!visible) return null;

  return (
    <button onClick={scrollToTop} aria-label="Torna su" className="fixed bottom-6 right-6 w-12 h-12 bg-black text-white shadow-lg hover:bg-red-700 transition-colors z-50 flex items-center justify-center">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2.5} stroke="currentColor" className="w-5 h-5">
        <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
      </svg>
    </button>
  );
}
