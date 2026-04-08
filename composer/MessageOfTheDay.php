<?php

declare(strict_types=1);

namespace Mautic\Composer;

use Composer\Script\Event;
use Mautic\Composer\Exception\MessageOfTheDayException;
use Symfony\Component\Console\Helper\Helper;

final class MessageOfTheDay
{
    private const CHANNEL = 'cli';

    public static function display(Event $event): void
    {
        try {
            $config = self::readConfig($event);

            $messages = self::getMessages($config);

            if ([] === $messages) {
                return;
            }

            $selectedMessage = $messages[array_rand($messages)];

            self::renderMessage(
                $event,
                $selectedMessage
            );
        } catch (MessageOfTheDayException $e) {
            $event->getIO()->writeError('<error>Failed to load MOTD: '.$e->getMessage().'</error>');
        }
    }

    /**
     * @return array{url: string, cache-path: string, cache-ttl: int}
     */
    private static function readConfig(Event $event): array
    {
        $extra  = $event->getComposer()->getPackage()->getExtra();
        $config = $extra['motd'] ?? [];

        if (empty($config['url'])) {
            throw new MessageOfTheDayException('MOTD URL is not configured in composer.json extra.motd.url');
        }

        if (false === filter_var($config['url'], FILTER_VALIDATE_URL)) {
            throw new MessageOfTheDayException('MOTD URL is not valid');
        }

        $defaultCachePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'mautic-motd.json';

        return [
            'url'        => $config['url'],
            'cache-path' => $config['cache-path'] ?? $defaultCachePath,
            'cache-ttl'  => (int) ($config['cache-ttl'] ?? 3600), // by default cache for 1 hour
        ];
    }

    /**
     * @param array{url: string, cache-path: string, cache-ttl: int} $config
     *
     * @return list<array{category: array{label: string}, lines: list<string>}>
     */
    private static function getMessages(array $config): array
    {
        $json = self::fetchMotdJson($config);

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new MessageOfTheDayException('Could not decode MOTD JSON');
        }

        if (empty($data['messages']) || !is_array($data['messages'])) {
            return [];
        }

        if (empty($data['categories']) || !is_array($data['categories'])) {
            return [];
        }

        $now            = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $activeMessages = [];

        foreach ($data['messages'] as $message) {
            if (empty($message['content']) || !is_array($message['content'])) {
                continue;
            }

            // Skip messages that have no content for this channel
            if (empty($message['content'][self::CHANNEL]) || !is_array($message['content'][self::CHANNEL])) {
                continue;
            }

            if (empty($message['category'])) {
                continue;
            }

            if (!isset($data['categories'][$message['category']])) {
                continue;
            }

            try {
                $start = !empty($message['start']) ? new \DateTimeImmutable($message['start']) : null;
                $end   = !empty($message['end']) ? new \DateTimeImmutable($message['end']) : null;
            } catch (\Exception) {
                // Skip message if date parsing fails
                continue;
            }

            if (null !== $start && $now < $start) {
                continue;
            }

            if (null !== $end && $now > $end) {
                continue;
            }

            $activeMessages[] = [
                'category' => $data['categories'][$message['category']],
                'lines'    => $message['content'][self::CHANNEL],
            ];
        }

        return $activeMessages;
    }

    /**
     * @param array{url: string, cache-path: string, cache-ttl: int} $config
     */
    private static function fetchMotdJson(array $config): string
    {
        $cachePath = $config['cache-path'];

        if (file_exists($cachePath) && time() - filemtime($cachePath) < $config['cache-ttl']) {
            $cached = file_get_contents($cachePath);

            if (false !== $cached) {
                return $cached;
            }
        }

        $streamContext = stream_context_create(['http' => ['timeout' => 3]]);
        $json          = file_get_contents($config['url'], false, $streamContext);

        if (false === $json) {
            throw new MessageOfTheDayException('Could not fetch motd.json');
        }

        @file_put_contents($cachePath, $json);

        return $json;
    }

    /**
     * @param array{category: array{label: string}, lines: list<string>} $message
     */
    private static function renderMessage(Event $event, array $message): void
    {
        $label = $message['category']['label'];
        $lines = $message['lines'];

        $horizontalPadding = 2;
        $contentIndent     = 3;

        $labelWidth       = Helper::width($label);
        $longestLineWidth = max(array_map(
            static fn (string $line): int => Helper::width($line),
            $lines
        ));

        $longest = max(
            $longestLineWidth + $contentIndent,
            $labelWidth
        );

        $padding = str_repeat(' ', $longest + $horizontalPadding * 2);

        $io = $event->getIO();

        $io->write('');
        $io->write('<fg=white;bg=blue>'.$padding.'</>');
        $io->write('<fg=white;bg=blue>'.str_repeat(' ', $horizontalPadding).$label.str_repeat(' ', $longest - $labelWidth + $horizontalPadding).'</>');
        $io->write('<fg=white;bg=blue>'.$padding.'</>');

        foreach ($lines as $line) {
            $lineWidth = Helper::width($line);
            $io->write('<fg=white;bg=blue>'.str_repeat(' ', $horizontalPadding + $contentIndent).$line.str_repeat(' ', $longest - $lineWidth - $contentIndent + $horizontalPadding).'</>');
        }

        $io->write('<fg=white;bg=blue>'.$padding.'</>');
        $io->write('');
    }
}
