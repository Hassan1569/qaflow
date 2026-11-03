import React from 'react';
import { Outlet, Navigate } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';

export function AuthLayout() {
  const { user, isLoading } = useAuth();

  if (isLoading) {
    return null; // Or a full-page loader
  }

  // If user is already logged in, redirect to dashboard
  if (user) {
    return <Navigate to="/dashboard" replace />;
  }

  return (
    <div className="min-h-screen bg-bg flex flex-col justify-center py-12 sm:px-6 lg:px-8">
      <div className="sm:mx-auto sm:w-full sm:max-w-md">
        <div className="flex justify-center mb-6">
          <div className="w-12 h-12 rounded-lg bg-primary flex items-center justify-center text-white font-bold text-2xl shadow-card">
            Q
          </div>
        </div>
        <h2 className="mt-6 text-center text-3xl font-extrabold text-text">
          Sign in to QAFlow
        </h2>
        <p className="mt-2 text-center text-sm text-text-muted">
          SQA / Test Management Platform
        </p>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div className="bg-surface py-8 px-4 shadow-card border border-border sm:rounded-lg sm:px-10">
          <Outlet />
        </div>
      </div>
    </div>
  );
}