<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @phpstan-type NotificationRecord array{
 *   farmer_id: int|string,
 *   maintenance_id: int|string,
 *   seen_at?: string|null
 * }&array<string, mixed>
 */
final class MaintenanceDateChangeNotificationStore
{
    private string $storagePath;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->storagePath = rtrim($projectDir, '\\/') . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'maintenance_date_change_notifications.json';
    }

    /** @param NotificationRecord $notification */
    public function addChangeNotification(array $notification): void
    {
        $records = $this->readAll();
        $records[] = $notification;
        $this->writeAll($records);
    }

    /** @return list<NotificationRecord> */
    public function getUnreadForFarmer(int $farmerId): array
    {
        return array_values(array_filter($this->readAll(), static function (array $notification) use ($farmerId): bool {
            return (int) $notification['farmer_id'] === $farmerId && empty($notification['seen_at']);
        }));
    }

    public function markMaintenanceSeen(int $farmerId, int $maintenanceId): void
    {
        $records = $this->readAll();
        $now = (new \DateTimeImmutable())->format(DATE_ATOM);

        foreach ($records as &$record) {
            if ((int) $record['farmer_id'] === $farmerId && (int) $record['maintenance_id'] === $maintenanceId) {
                $record['seen_at'] = $now;
            }
        }

        unset($record);
        $this->writeAll($records);
    }

    /** @return list<NotificationRecord> */
    private function readAll(): array
    {
        if (!is_file($this->storagePath)) {
            return [];
        }

        $content = file_get_contents($this->storagePath);
        if ($content === false || $content === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    /** @param list<NotificationRecord> $records */
    private function writeAll(array $records): void
    {
        $directory = dirname($this->storagePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents(
            $this->storagePath,
            json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }
}