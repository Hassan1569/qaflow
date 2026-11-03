import React from 'react';
import { Link } from 'react-router-dom';
import { StatusBadge } from '../common/StatusBadge';
import { formatDate } from '../../utils/format';

export function ProjectCard({ project }) {
  return (
    <div className="bg-surface border border-border rounded-md shadow-card hover:shadow-modal transition-shadow duration-200 flex flex-col">
      <div className="p-5 flex-1">
        <div className="flex justify-between items-start mb-2">
          <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-light text-primary border border-primary/20">
            {project.key_code}
          </span>
          <StatusBadge status={project.status} />
        </div>
        
        <Link to={`/projects/${project.id}`}>
          <h3 className="text-lg font-semibold text-text hover:text-primary transition-colors mb-1 truncate">
            {project.name}
          </h3>
        </Link>
        
        <p className="text-sm text-text-muted line-clamp-2 mb-4 h-10">
          {project.description || 'No description provided.'}
        </p>

        <div className="grid grid-cols-3 gap-2 text-center mt-auto pt-4 border-t border-border">
          <div>
            <p className="text-lg font-semibold text-text">{project.requirements_count || 0}</p>
            <p className="text-xs text-text-muted uppercase tracking-wide">Reqs</p>
          </div>
          <div>
            <p className="text-lg font-semibold text-text">{project.test_cases_count || 0}</p>
            <p className="text-xs text-text-muted uppercase tracking-wide">Cases</p>
          </div>
          <div>
            <p className="text-lg font-semibold text-text text-fail">{project.open_defects_count || 0}</p>
            <p className="text-xs text-text-muted uppercase tracking-wide">Defects</p>
          </div>
        </div>
      </div>
      <div className="bg-bg px-5 py-3 border-t border-border text-xs text-text-muted flex justify-between items-center rounded-b-md">
        <span>Owner: <span className="font-medium text-text">{project.owner_name}</span></span>
        <span>{formatDate(project.updated_at)}</span>
      </div>
    </div>
  );
}