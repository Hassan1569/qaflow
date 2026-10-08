<?php
declare(strict_types=1);

/**
 * QAFlow — Test case model.
 *
 * Wraps a row from the test_cases table. Provides label helpers for the
 * enum-like fields and a display code used by lists and cross-references.
 */

namespace QAFlow\Models;

final class TestCase extends BaseModel
{
    /** @var array<string,string> */
    protected array $casts = [
        'id'                => 'int',
        'project_id'        => 'int',
        'suite_id'          => 'int',
        'author_id'         => 'int',
        'current_version'   => 'int',
        'requirement_count' => 'int',
    ];

    public function code(): string
    {
        return (string) $this->get('code', '');
    }

    public function title(): string
    {
        return (string) $this->get('title', '');
    }

    public function priority(): string
    {
        return (string) $this->get('priority', 'medium');
    }

    public function severity(): string
    {
        return (string) $this->get('severity', 'medium');
    }

    public function testType(): string
    {
        return (string) $this->get('test_type', 'functional');
    }

    public function automationStatus(): string
    {
        return (string) $this->get('automation_status', 'manual');
    }

    public function suiteId(): ?int
    {
        $sid = $this->get('suite_id');
        if ($sid === null || $sid === 0 || $sid === '') {
            return null;
        }
        return (int) $sid;
    }

    public function priorityLabel(): string
    {
        return ucfirst($this->priority());
    }

    public function severityLabel(): string
    {
        return ucfirst($this->severity());
    }

    public function testTypeLabel(): string
    {
        return match ($this->testType()) {
            'functional'  => 'Functional',
            'regression'  => 'Regression',
            'smoke'       => 'Smoke',
            'sanity'      => 'Sanity',
            'integration' => 'Integration',
            'ui'          => 'UI',
            'api'         => 'API',
            'security'    => 'Security',
            'performance' => 'Performance',
            'compatibility' => 'Compatibility',
            default       => ucfirst($this->testType()),
        };
    }

    public function automationLabel(): string
    {
        return match ($this->automationStatus()) {
            'manual'    => 'Manual',
            'automated' => 'Automated',
            'planned'   => 'Planned',
            default     => ucfirst($this->automationStatus()),
        };
    }

    public function displayCode(): string
    {
        return $this->code() . ' — ' . $this->title();
    }

    /**
     * @return array<int,string>
     */
    public function tagList(): array
    {
        $raw = (string) $this->get('tags', '');
        if ($raw === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $out = parent::toArray();
        $out['priority_label']   = $this->priorityLabel();
        $out['severity_label']   = $this->severityLabel();
        $out['test_type_label']  = $this->testTypeLabel();
        $out['automation_label'] = $this->automationLabel();
        $out['display_code']     = $this->displayCode();
        $out['tag_list']         = $this->tagList();
        return $out;
    }
}