import React, { useState } from 'react';
import { attachmentService } from '../../services/attachmentService';
import { useToast } from '../../hooks/useToast';
import { formatDate } from '../../utils/format';
import { Icon } from '../common/Icon';

export function AttachmentList({ entityType, entityId, attachments = [], onRefresh, readOnly = false }) {
  const [isUploading, setIsUploading] = useState(false);
  const toast = useToast();

  const handleFileUpload = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    setIsUploading(true);
    try {
      await attachmentService.upload(file, entityType, entityId);
      toast.success('File uploaded successfully.');
      if (onRefresh) onRefresh();
    } catch (err) {
      toast.error(err.message || 'Failed to upload file.');
    } finally {
      setIsUploading(false);
      e.target.value = null; // reset input
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Delete this attachment?')) return;
    try {
      await attachmentService.delete(id);
      toast.success('Attachment deleted.');
      if (onRefresh) onRefresh();
    } catch (err) {
      toast.error(err.message || 'Failed to delete attachment.');
    }
  };

  return (
    <div className="space-y-4">
      {!readOnly && (
        <div className="flex items-center justify-center w-full">
          <label className="flex flex-col items-center justify-center w-full h-24 border-2 border-border border-dashed rounded-md cursor-pointer bg-bg hover:bg-surface transition-colors">
            <div className="flex flex-col items-center justify-center pt-5 pb-6">
              {isUploading ? (
                <span className="text-sm text-primary font-medium">Uploading...</span>
              ) : (
                <>
                  <svg className="w-6 h-6 mb-2 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                  <p className="text-sm text-text-muted"><span className="font-semibold text-primary">Click to upload</span> or drag and drop</p>
                  <p className="text-xs text-text-muted mt-1">PNG, JPG, PDF, TXT up to 10MB</p>
                </>
              )}
            </div>
            <input type="file" className="hidden" onChange={handleFileUpload} disabled={isUploading} />
          </label>
        </div>
      )}

      {attachments.length === 0 ? (
        <p className="text-sm text-text-muted italic text-center py-4">No attachments.</p>
      ) : (
        <ul className="space-y-2">
          {attachments.map((file) => (
            <li key={file.id} className="flex items-center justify-between p-2 bg-surface border border-border rounded-md hover:bg-bg transition-colors group">
              <div className="flex items-center gap-3 overflow-hidden">
                <div className="p-2 bg-bg rounded text-text-muted flex-shrink-0">
                  <Icon name={file.is_image ? 'document' : 'document'} className="w-5 h-5" />
                </div>
                <div className="min-w-0">
                  <a href={attachmentService.getDownloadUrl(file.id)} target="_blank" rel="noreferrer" className="text-sm font-medium text-text hover:text-primary hover:underline truncate block">
                    {file.original_name}
                  </a>
                  <p className="text-xs text-text-muted">{file.size_label} • {formatDate(file.created_at)}</p>
                </div>
              </div>
              {!readOnly && (
                <button onClick={() => handleDelete(file.id)} className="p-2 text-text-muted hover:text-fail opacity-0 group-hover:opacity-100 transition-opacity">
                  <Icon name="close" className="w-4 h-4" />
                </button>
              )}
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}