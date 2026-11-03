import React, { useEffect } from 'react';

export function Toast({ toast, onClose }) {
  useEffect(() => {
    const timer = setTimeout(() => {
      onClose();
    }, toast.duration || 4000);
    return () => clearTimeout(timer);
  }, [toast, onClose]);

  const getStyles = () => {
    switch (toast.type) {
      case 'success': return 'bg-pass text-white border-pass';
      case 'error': return 'bg-fail text-white border-fail';
      case 'warning': return 'bg-blocked text-white border-blocked';
      default: return 'bg-surface text-text border-border shadow-modal';
    }
  };

  return (
    <div className={`pointer-events-auto flex items-center p-4 rounded-md border transition-all animate-fade-in-up ${getStyles()}`}>
      <div className="flex-1 text-sm font-medium">
        {toast.message}
      </div>
      <button onClick={onClose} className="ml-4 inline-flex text-current hover:opacity-75 focus:outline-none">
        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  );
}