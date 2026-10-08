<?php
declare(strict_types=1);

/**
 * QAFlow — Environment model.
 *
 * Wraps a row from the environments table. Builds a compact display string
 * combining browser, OS, and device for use in tables and badges.
 */

namespace QAFlow\Models;

final class Environment extends BaseModel
{
    /** @var array<string,string> */
    protected array $casts = [
        'id'         => 'int',
        'project_id' => 'int',
    ];

    public function name(): string
    {
        return (string) $this->get('name', '');
    }

    public function browser(): string
    {
        return (string) $this->get('browser', '');
    }

    public function os(): string
    {
        return (string) $this->get('os', '');
    }

    public function device(): string
    {
        return (string) $this->get('device', '');
    }

    public function appVersion(): string
    {
        return (string) $this->get('app_version', '');
    }

    /**
     * Compact display, e.g. "Chrome 120 / Windows 11" or "Pixel 7 / Android 14".
     */
    public function displayLabel(): string
    {
        $parts = [];

        $browser = $this->browser();
        if ($browser !== '') {
            $bv = (string) $this->get('browser_version', '');
            $parts[] = $bv === '' ? $browser : $browser . ' ' . $bv;
        }

        $os = $this->os();
        if ($os !== '') {
            $osv = (string) $this->get('os_version', '');
            $parts[] = $osv === '' ? $os : $os . ' ' . $osv;
        }

        $device = $this->device();
        if ($device !== '') {
            $dv = (string) $this->get('device_version', '');
            $parts[] = $dv === '' ? $device : $device . ' ' . $dv;
        }

        if ($parts === []) {
            return $this->name();
        }

        return implode(' / ', $parts);
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $out = parent::toArray();
        $out['display_label'] = $this->displayLabel();
        return $out;
    }
}