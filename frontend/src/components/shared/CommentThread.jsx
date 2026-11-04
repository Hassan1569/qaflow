import React, { useState } from 'react';
import { Button } from '../common/Button';
import { Textarea } from '../common/Textarea';
import { formatDate, getInitials } from '../../utils/format';
import { commentService } from '../../services/commentService';
import { useToast } from '../../hooks/useToast';

export function CommentThread({ entityType, entityId, comments = [], onRefresh }) {
  const [content, setContent] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const toast = useToast();

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!content.trim()) return;

    setIsSubmitting(true);
    try {
      await commentService.create({
        entity_type: entityType,
        entity_id: entityId,
        content: content.trim(),
      });
      setContent('');
      toast.success('Comment added.');
      if (onRefresh) onRefresh();
    } catch (err) {
      toast.error(err.message || 'Failed to add comment.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-4">
        {comments.length === 0 ? (
          <p className="text-sm text-text-muted italic">No comments yet.</p>
        ) : (
          comments.map((comment) => (
            <div key={comment.id} className="flex gap-3">
              <div className="flex-shrink-0 w-8 h-8 rounded-full bg-primary-light text-primary flex items-center justify-center font-bold text-xs uppercase mt-1">
                {getInitials(comment.author_name)}
              </div>
              <div className="flex-1 bg-surface border border-border rounded-md p-3">
                <div className="flex justify-between items-baseline mb-1">
                  <span className="text-sm font-semibold text-text">{comment.author_name}</span>
                  <span className="text-xs text-text-muted">{formatDate(comment.created_at, true)}</span>
                </div>
                <p className="text-sm text-text whitespace-pre-wrap">{comment.content}</p>
              </div>
            </div>
          ))
        )}
      </div>

      <form onSubmit={handleSubmit} className="flex gap-3 pt-4 border-t border-border">
        <div className="flex-1">
          <Textarea
            value={content}
            onChange={(e) => setContent(e.target.value)}
            placeholder="Add a comment..."
            rows={2}
            className="!text-sm"
          />
        </div>
        <div>
          <Button type="submit" variant="primary" isLoading={isSubmitting} disabled={!content.trim()}>
            Post
          </Button>
        </div>
      </form>
    </div>
  );
}