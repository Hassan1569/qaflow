import React, { useState } from 'react';
import { Link } from 'react-router-dom';

export function SuiteTree({ suites, onEdit, onDelete }) {
  // Build a hierarchical tree from the flat list
  const buildTree = (items, parentId = null) => {
    return items
      .filter(item => {
        const itemParent = item.parent_id === 0 ? null : item.parent_id;
        return itemParent === parentId;
      })
      .map(item => ({
        ...item,
        children: buildTree(items, item.id)
      }));
  };

  const tree = buildTree(suites);

  const renderNode = (node, depth = 0) => {
    return (
      <div key={node.id} className="select-none">
        <div 
          className="flex items-center justify-between py-2 hover:bg-bg px-2 rounded group"
          style={{ paddingLeft: `${depth * 24 + 8}px` }}
        >
          <div className="flex items-center gap-2">
            <svg className="w-4 h-4 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
            </svg>
            <Link to={`/test-cases?suite_id=${node.id}`} className="text-sm font-medium text-text hover:text-primary">
              {node.name}
            </Link>
            <span className="text-xs text-text-muted">({node.test_case_count || 0})</span>
          </div>
          
          <div className="opacity-0 group-hover:opacity-100 flex items-center gap-2 transition-opacity">
            <button onClick={() => onEdit(node)} className="text-xs text-primary hover:underline">Edit</button>
            <button onClick={() => onDelete(node)} className="text-xs text-fail hover:underline">Delete</button>
          </div>
        </div>
        
        {node.children.length > 0 && (
          <div>
            {node.children.map(child => renderNode(child, depth + 1))}
          </div>
        )}
      </div>
    );
  };

  if (!suites || suites.length === 0) {
    return <div className="text-sm text-text-muted text-center py-8">No suites created yet.</div>;
  }

  return (
    <div className="border border-border rounded-md bg-surface">
      {tree.map(node => renderNode(node))}
    </div>
  );
}