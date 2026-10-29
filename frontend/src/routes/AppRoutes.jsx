import React, { Suspense, lazy } from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { ProtectedRoute } from './ProtectedRoute';
import { AppLayout } from '../components/layout/AppLayout';
import { AuthLayout } from '../components/layout/AuthLayout';
import { LoadingState } from '../components/common/LoadingState';

// Lazy-loaded pages for route-level code splitting
const LoginPage = lazy(() => import('../pages/LoginPage'));
const DashboardPage = lazy(() => import('../pages/DashboardPage'));
const ProjectsPage = lazy(() => import('../pages/ProjectsPage'));
const ProjectDetailPage = lazy(() => import('../pages/ProjectDetailPage'));
const RequirementsPage = lazy(() => import('../pages/RequirementsPage'));
const RequirementDetailPage = lazy(() => import('../pages/RequirementDetailPage'));
const TestCasesPage = lazy(() => import('../pages/TestCasesPage'));
const TestCaseDetailPage = lazy(() => import('../pages/TestCaseDetailPage'));
const TestSuitesPage = lazy(() => import('../pages/TestSuitesPage'));
const TestPlansPage = lazy(() => import('../pages/TestPlansPage'));
const TestPlanDetailPage = lazy(() => import('../pages/TestPlanDetailPage'));
const TestRunsPage = lazy(() => import('../pages/TestRunsPage'));
const TestRunDetailPage = lazy(() => import('../pages/TestRunDetailPage'));
const ExecutionPage = lazy(() => import('../pages/ExecutionPage'));
const DefectsPage = lazy(() => import('../pages/DefectsPage'));
const DefectDetailPage = lazy(() => import('../pages/DefectDetailPage'));
const ReportsPage = lazy(() => import('../pages/ReportsPage'));
const TraceabilityPage = lazy(() => import('../pages/TraceabilityPage'));
const EnvironmentsPage = lazy(() => import('../pages/EnvironmentsPage'));
const TeamPage = lazy(() => import('../pages/TeamPage'));
const SettingsPage = lazy(() => import('../pages/SettingsPage'));
const NotFoundPage = lazy(() => import('../pages/NotFoundPage'));

export function AppRoutes() {
  return (
    <Suspense fallback={<div className="h-screen w-screen flex items-center justify-center bg-bg"><LoadingState /></div>}>
      <Routes>
        {/* Public Routes */}
        <Route element={<AuthLayout />}>
          <Route path="/login" element={<LoginPage />} />
        </Route>

        {/* Protected Routes */}
        <Route element={<ProtectedRoute />}>
          <Route element={<AppLayout />}>
            <Route path="/" element={<Navigate to="/dashboard" replace />} />
            <Route path="/dashboard" element={<DashboardPage />} />
            
            <Route path="/projects" element={<ProjectsPage />} />
            <Route path="/projects/:id" element={<ProjectDetailPage />} />
            
            <Route path="/requirements" element={<RequirementsPage />} />
            <Route path="/requirements/:id" element={<RequirementDetailPage />} />
            
            <Route path="/test-cases" element={<TestCasesPage />} />
            <Route path="/test-cases/:id" element={<TestCaseDetailPage />} />
            
            <Route path="/test-suites" element={<TestSuitesPage />} />
            
            <Route path="/test-plans" element={<TestPlansPage />} />
            <Route path="/test-plans/:id" element={<TestPlanDetailPage />} />
            
            <Route path="/test-runs" element={<TestRunsPage />} />
            <Route path="/test-runs/:id" element={<TestRunDetailPage />} />
            
            <Route path="/execution/:id" element={<ExecutionPage />} />
            
            <Route path="/defects" element={<DefectsPage />} />
            <Route path="/defects/:id" element={<DefectDetailPage />} />
            
            <Route path="/reports" element={<ReportsPage />} />
            <Route path="/traceability" element={<TraceabilityPage />} />
            
            <Route path="/environments" element={<EnvironmentsPage />} />
            <Route path="/team" element={<TeamPage />} />
            <Route path="/settings" element={<SettingsPage />} />
            
            <Route path="*" element={<NotFoundPage />} />
          </Route>
        </Route>
      </Routes>
    </Suspense>
  );
}