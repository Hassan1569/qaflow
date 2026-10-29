import { useCallback, useMemo } from 'react';
import { useAuth } from './useAuth';

/**
 * Provides helper functions to check the current user's permissions.
 * These are for UI visibility only. The backend always enforces authorization.
 */
export function usePermissions() {
  const { user } = useAuth();

  const role = user?.role || 'viewer';

  const isAdmin = role === 'admin';
  const isQaLead = role === 'qa_lead';
  const isQaEngineer = role === 'qa_engineer';
  const isDeveloper = role === 'developer';
  const isViewer = role === 'viewer';

  // Capability checks based on the roles defined in prd.md
  const canManageUsers = useCallback(() => isAdmin, [isAdmin]);

  const canManageProjects = useCallback(() => {
    return isAdmin || isQaLead;
  }, [isAdmin, isQaLead]);

  const canManageRequirements = useCallback(() => {
    return isAdmin || isQaLead;
  }, [isAdmin, isQaLead]);

  const canManageTestCases = useCallback(() => {
    return isAdmin || isQaLead;
  }, [isAdmin, isQaLead]);

  const canExecuteTests = useCallback(() => {
    return isAdmin || isQaLead || isQaEngineer;
  }, [isAdmin, isQaLead, isQaEngineer]);

  const canCreateDefects = useCallback(() => {
    return isAdmin || isQaLead || isQaEngineer;
  }, [isAdmin, isQaLead, isQaEngineer]);

  const canUpdateDefects = useCallback(() => {
    return isAdmin || isQaLead || isQaEngineer || isDeveloper;
  }, [isAdmin, isQaLead, isQaEngineer, isDeveloper]);

  const canViewReports = useCallback(() => {
    return true; // Everyone can view reports
  }, []);

  return useMemo(() => ({
    role,
    isAdmin,
    isQaLead,
    isQaEngineer,
    isDeveloper,
    isViewer,
    canManageUsers,
    canManageProjects,
    canManageRequirements,
    canManageTestCases,
    canExecuteTests,
    canCreateDefects,
    canUpdateDefects,
    canViewReports,
  }), [
    role, isAdmin, isQaLead, isQaEngineer, isDeveloper, isViewer,
    canManageUsers, canManageProjects, canManageRequirements, canManageTestCases,
    canExecuteTests, canCreateDefects, canUpdateDefects, canViewReports
  ]);
}