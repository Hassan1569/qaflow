<?php
declare(strict_types=1);

/**
 * QAFlow — Activity log model.
 *
 * Wraps a row from the activity_logs table. Provides a human-readable
 * description built from the action and entity metadata, used by the
 * activity timeline in the UI.
 */

namespace QAFlow\Models;

final class ActivityLog extends BaseModel
{
    /** @var array<string,string> */
    protected array $casts = [
        'id'         => 'int',
        'user_id'    => 'int',
        'project_id' => 'int',
        'entity_id'  => 'int',
        'metadata'   => 'json',
    ];

    public function action(): string
    {
        return (string) $this->get('action', '');
    }

    public function entityType(): string
    {
        return (string) $this->get('entity_type', '');
    }

    public function entityId(): ?int
    {
        $id = $this->get('entity_id');
        if ($id === null || $id === '' || $id === 0) {
            return null;
        }
        return (int) $id;
    }

    public function userName(): string
    {
        return (string) $this->get('user_name', 'System');
    }

    /**
     * @return array<string,mixed>
     */
    public function metadata(): array
    {
        $meta = $this->get('metadata', []);
        return is_array($meta) ? $meta : [];
    }

    /**
     * A short human-readable description of the action.
     */
    public function description(): string
    {
        $actor  = $this->userName();
        $action = $this->action();
        $meta   = $this->metadata();

        $target = $meta['code'] ?? $meta['name'] ?? $meta['title'] ?? null;

        return match ($action) {
            'project.created'      => "$actor created project" . ($target ? " $target" : ''),
            'project.updated'      => "$actor updated project" . ($target ? " $target" : ''),
            'project.archived'     => "$actor archived project" . ($target ? " $target" : ''),
            'project.member.added' => "$actor added a project member",
            'project.member.removed' => "$actor removed a project member",

            'requirement.created'  => "$actor created requirement" . ($target ? " $target" : ''),
            'requirement.updated'  => "$actor updated requirement" . ($target ? " $target" : ''),
            'requirement.deleted'  => "$actor deleted requirement" . ($target ? " $target" : ''),

            'test_case.created'    => "$actor created test case" . ($target ? " $target" : ''),
            'test_case.updated'    => "$actor updated test case" . ($target ? " $target" : ''),
            'test_case.deleted'    => "$actor deleted test case" . ($target ? " $target" : ''),
            'test_case.executed'   => "$actor executed a test case",

            'test_plan.created'    => "$actor created test plan" . ($target ? " $target" : ''),
            'test_plan.updated'    => "$actor updated test plan" . ($target ? " $target" : ''),

            'test_run.created'     => "$actor created test run" . ($target ? " $target" : ''),
            'test_run.started'     => "$actor started a test run",
            'test_run.completed'   => "$actor completed a test run",

            'test_result.recorded' => "$actor recorded a test result",
            'test_result.updated'  => "$actor updated a test result",

            'defect.created'       => "$actor created defect" . ($target ? " $target" : ''),
            'defect.updated'       => "$actor updated defect" . ($target ? " $target" : ''),
            'defect.status_changed'=> "$actor changed defect status",

            'attachment.uploaded'  => "$actor uploaded an attachment",
            'attachment.deleted'   => "$actor deleted an attachment",

            'comment.created'      => "$actor commented",
            'comment.updated'      => "$actor edited a comment",
            'comment.deleted'      => "$actor deleted a comment",

            'auth.login'           => "$actor logged in",
            'auth.logout'          => "$actor logged out",

            default                => $actor . ' ' . str_replace(['.', '_'], ' ', $action),
        };
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $out = parent::toArray();
        $out['description'] = $this->description();
        return $out;
    }
}