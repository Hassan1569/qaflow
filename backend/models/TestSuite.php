<?php
declare(strict_types=1);

/**
 * QAFlow — Test suite model.
 *
 * Wraps a row from the test_suites table. Provides depth hints for tree
 * rendering and a display path used in list views.
 */

namespace QAFlow\Models;

final class TestSuite extends BaseModel
{
    /** @var array<string,string> */
    protected array $casts = [
        'id'              => 'int',
        'project_id'      => 'int',
        'parent_id'       => 'int',
        'test_case_count' => 'int',
    ];

    public function name(): string
    {
        return (string) $this->get('name', '');
    }

    public function parentId(): ?int
    {
        $pid = $this->get('parent_id');
        if ($pid === null || $pid === 0 || $pid === '') {
            return null;
        }
        return (int) $pid;
    }

    public function isRoot(): bool
    {
        return $this->parentId() === null;
    }

    public function testCaseCount(): int
    {
        return (int) $this->get('test_case_count', 0);
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $out = parent::toArray();
        $out['is_root'] = $this->isRoot();
        return $out;
    }
}