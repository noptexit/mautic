<?php

declare(strict_types=1);

namespace Mautic\Composer;

use Composer\Downloader\TransportException;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Composer\Util\HttpDownloader;
use Mautic\Composer\Exception\MessageOfTheDayException;
use Symfony\Component\Console\Helper\Helper;

final class MessageOfTheDay
{
    private const CHANNEL = 'cli';

    public static function display(Event $event): void
    {
        try {
            $config = self::readConfig($event);

            $io             = $event->getIO();
            $composerConfig = $event->getComposer()->getConfig();
            $downloader     = new HttpDownloader($io, $composerConfig);

            $json = self::fetchMotdJson($config, $downloader, $io);

            $messages = self::getMessages($json);

            $selectedMessage = self::selectMessage($messages);

            if (null === $selectedMessage) {
                return;
            }

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
     * @return array{
     *     timed: list<array{category: array{label: string}, lines: list<string>}>,
     *     timeless: list<array{category: array{label: string}, lines: list<string>}>
     * }
     *
     * @throws MessageOfTheDayException
     */
    private static function getMessages(string $json): array
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new MessageOfTheDayException('Could not decode MOTD JSON');
        }

        $messages = ['timed' => [], 'timeless' => []];

        if (empty($data['messages']) || !is_array($data['messages'])) {
            return $messages;
        }

        if (empty($data['categories']) || !is_array($data['categories'])) {
            return $messages;
        }

        $utc = new \DateTimeZone('UTC');
        $now = new \DateTimeImmutable('now', $utc);

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
                $start = !empty($message['start']) ? new \DateTimeImmutable($message['start'], $utc) : null;
                $end   = !empty($message['end']) ? new \DateTimeImmutable($message['end'], $utc) : null;
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

            $pool = (null !== $start || null !== $end) ? 'timed' : 'timeless';

            $messages[$pool][] = [
                'category' => $data['categories'][$message['category']],
                'lines'    => $message['content'][self::CHANNEL],
            ];
        }

        return $messages;
    }

    /**
     * @param array{
     *     timed: list<array{category: array{label: string}, lines: list<string>}>,
     *     timeless: list<array{category: array{label: string}, lines: list<string>}>
     * } $messages
     *
     * @return array{category: array{label: string}, lines: list<string>}|null
     */
    private static function selectMessage(array $messages): ?array
    {
        ['timed' => $timed, 'timeless' => $timeless] = $messages;

        if ([] === $timed && [] === $timeless) {
            return null;
        }

        if ([] === $timed) {
            return $timeless[array_rand($timeless)];
        }

        if ([] === $timeless) {
            return $timed[array_rand($timed)];
        }

        // When both sets are non-empty, pick timed message 75% of the time
        $pool = (random_int(1, 100) <= 75) ? $timed : $timeless;

        return $pool[array_rand($pool)];
    }

    /**
     * @param array{url: string, cache-path: string, cache-ttl: int} $config
     */
    private static function fetchMotdJson(array $config, HttpDownloader $downloader, IOInterface $io): string
    {
        $cachePath = $config['cache-path'];

        if (file_exists($cachePath) && time() - filemtime($cachePath) < $config['cache-ttl']) {
            $cached = file_get_contents($cachePath);

            if (false !== $cached) {
                return $cached;
            }
        }

        try {
            $response= $downloader->get($config['url']);
            $json    = $response->getBody();
        } catch (TransportException) {
            throw new MessageOfTheDayException('Could not fetch motd.json');
        }

        $written = file_put_contents($cachePath, $json);

        if (false === $written) {
            $io->write('<warning>Could not write MOTD cache to '.$cachePath.'</warning>', true, IOInterface::VERBOSE);
        }

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
