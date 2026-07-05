<?php

namespace App\Service\ForumActif;

final class ForumActifDateParser
{
    /** @var array<string, string> */
    private const MONTHS = [
        'jan' => '01', 'janv' => '01', 'janvier' => '01',
        'fev' => '02', 'fév' => '02', 'fevr' => '02', 'févr' => '02', 'fevrier' => '02', 'février' => '02',
        'mar' => '03', 'mars' => '03',
        'avr' => '04', 'avril' => '04',
        'mai' => '05',
        'jun' => '06', 'juin' => '06',
        'jul' => '07', 'juil' => '07', 'juillet' => '07',
        'aou' => '08', 'aoû' => '08', 'aout' => '08', 'août' => '08',
        'sep' => '09', 'sept' => '09', 'septembre' => '09',
        'oct' => '10', 'octobre' => '10',
        'nov' => '11', 'novembre' => '11',
        'dec' => '12', 'déc' => '12', 'decembre' => '12', 'décembre' => '12',
    ];

    public function parse(?string $raw, ?\DateTimeImmutable $reference = null): ?\DateTimeImmutable
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $reference ??= new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $text = html_entity_decode(trim(preg_replace('/\s+/u', ' ', $raw) ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (preg_match("/Aujourd'hui à (\d{1,2}):(\d{2})/ui", $text, $m)) {
            return $reference->setTime((int) $m[1], (int) $m[2], 0);
        }

        if (preg_match('/Hier à (\d{1,2}):(\d{2})/ui', $text, $m)) {
            return $reference->modify('-1 day')->setTime((int) $m[1], (int) $m[2], 0);
        }

        if (preg_match(
            '/(?:Lun|Mar|Mer|Jeu|Ven|Sam|Dim)\s+(\d{1,2})\s+([A-Za-zéûô\.]+)\.?\s+(\d{4})\s*-\s*(\d{1,2}):(\d{2})/u',
            $text,
            $m
        )) {
            $month = $this->normalizeMonth($m[2]);
            if ($month === null) {
                return null;
            }

            return \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i',
                sprintf('%s-%s-%02d %02d:%02d', $m[3], $month, (int) $m[1], (int) $m[4], (int) $m[5]),
                new \DateTimeZone('Europe/Paris')
            ) ?: null;
        }

        return null;
    }

    public function getYear(?string $raw, ?\DateTimeImmutable $reference = null): ?int
    {
        $date = $this->parse($raw, $reference);

        return $date?->format('Y') !== null ? (int) $date->format('Y') : null;
    }

    private function normalizeMonth(string $month): ?string
    {
        $key = strtolower(rtrim($month, '.'));
        $key = str_replace(['é', 'û', 'ô'], ['e', 'u', 'o'], $key);

        foreach (self::MONTHS as $label => $value) {
            $normalizedLabel = str_replace(['é', 'û', 'ô'], ['e', 'u', 'o'], $label);
            if ($key === $normalizedLabel || str_starts_with($key, $normalizedLabel)) {
                return $value;
            }
        }

        return null;
    }
}
