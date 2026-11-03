import React, { useEffect, useRef } from 'react';

export function Modal({ isOpen, onClose, title, children, footer, maxWidth = 'max-w-lg' }) {
  const modalRef = useRef(null);

  // Close on Escape key
  useEffect(() => {
    const handleKeyDown = (e) => {
      if (e.key === 'Escape' && isOpen) onClose();
    };
    if (isOpen) {
      document.addEventListener('keydown', handleKeyDown);
      document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }
    return () => {
      document.removeEventListener('keydown', handleKeyDown);
      document.body.style.overflow = 'unset';
    };
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        
        {/* Background overlay */}
        <div 
          className="fixed inset-0 bg-text/50 transition-opacity" 
          aria-hidden="true"
          onClick={onClose}
        />

        {/* Trick to center the modal contents */}
        <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        {/* Modal panel */}
        <div 
          ref={modalRef}
          className={`relative z-10 inline-block align-bottom bg-surface rounded-lg text-left overflow-hidden shadow-modal transform transition-all sm:my-8 sm:align-middle w-full ${maxWidth}`}
        >
          <div className="bg-surface px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-border">
            <div className="flex items-start justify-between">
              <h3 className="text-lg leading-6 font-medium text-text" id="modal-title">
                {title}
              </h3>
              <button onClick={onClose} className="text-text-muted hover:text-text focus:outline-none">
                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
          
          <div className="px-4 py-5 sm:p-6 max-h-[70vh] overflow-y-auto">
            {children}
          </div>
          
          {footer && (
            <div className="bg-bg px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-border">
              {footer}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}