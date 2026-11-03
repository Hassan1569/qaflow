import React from 'react';
import { Button } from './Button';

export function ErrorState({ title = 'Something went wrong', message, onRetry }) {
  return (
    <div className="rounded-md bg-fail/10 p-4 border-l-4 border-fail my-4">
      <div className="flex">
        <div className="flex-shrink-0">
          <svg className="h-5 w-5 text-fail" viewBox="0 0 20 20" fill="currentColor">
            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
          </svg>
        </div>
        <div className="ml-3">
          <h3 className="text-sm font-medium text-fail">{title}</h3>
          {message && (
            <div className="mt-2 text-sm text-fail/90">
              <p>{message}</p>
            </div>
          )}
          {onRetry && (
            <div className="mt-4">
              <Button onClick={onRetry} variant="secondary" size="sm" className="!border-fail !text-fail hover:!bg-fail/10">
                Try again
              </Button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}