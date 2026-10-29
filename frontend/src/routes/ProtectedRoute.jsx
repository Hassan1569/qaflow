import React from 'react';
import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { LoadingState } from '../components/common/LoadingState';

export function ProtectedRoute({ allowedRoles }) {
  const { user, isLoading } = useAuth();
  const location = useLocation();

  if (isLoading) {
    return (
      <div className="h-screen w-screen flex items-center justify-center bg-bg">
        <LoadingState />
      </div>
    );
  }

  if (!user) {
    // Redirect to login but save the current location so we can redirect back
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  if (allowedRoles && allowedRoles.length > 0 && !allowedRoles.includes(user.role)) {
    // If the user is logged in but doesn't have the required role, send to dashboard
    return <Navigate to="/dashboard" replace />;
  }

  return <Outlet />;
}