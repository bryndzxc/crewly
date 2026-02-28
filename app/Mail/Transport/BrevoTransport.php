<?php

namespace App\Mail\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class BrevoTransport extends AbstractTransport
{
    private const DEFAULT_ENDPOINT = 'https://api.brevo.com/v3/smtp/email';

    private ClientInterface $client;
    private string $apiKey;
    private string $endpoint;

    public function __construct(
        string $apiKey,
        ?ClientInterface $client = null,
        ?string $endpoint = null,
        float $timeout = 10.0,
        ?EventDispatcherInterface $dispatcher = null,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($dispatcher, $logger);

        $this->apiKey = $apiKey;
        $this->endpoint = $endpoint ?: self::DEFAULT_ENDPOINT;
        $this->client = $client ?? new Client([
            'timeout' => $timeout,
        ]);
    }

    public function __toString(): string
    {
        $host = parse_url($this->endpoint, \PHP_URL_HOST);

        return $host ? 'brevo+api://'.$host : 'brevo+api';
    }

    protected function doSend(SentMessage $message): void
    {
        $original = $message->getOriginalMessage();

        if (!$original instanceof Email) {
            throw new TransportException('Brevo transport only supports instances of '.Email::class.'.');
        }

        $payload = $this->buildPayload($original, $message);
        $logMeta = $this->buildSafeLogMeta($original, $message);

        try {
            $response = $this->client->request('POST', $this->endpoint, [
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'api-key' => $this->apiKey,
                ],
                'json' => $payload,
            ]);

            $status = (int) $response->getStatusCode();
            $body = (string) $response->getBody();
        } catch (GuzzleException $e) {
            $this->getLogger()->error('Brevo API request failed', [
                'exception' => $e,
                'transport' => (string) $this,
                ...$logMeta,
            ]);

            throw new TransportException('Brevo API request failed: '.$e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Brevo send failed (unexpected)', [
                'exception' => $e,
                'transport' => (string) $this,
                ...$logMeta,
            ]);

            throw new TransportException('Brevo send failed: '.$e->getMessage(), 0, $e);
        }

        $messageId = null;
        $decoded = json_decode($body, true);
        if (\is_array($decoded) && isset($decoded['messageId']) && \is_string($decoded['messageId'])) {
            $messageId = $decoded['messageId'];
            $message->setMessageId($messageId);
        }

        if ($status < 200 || $status >= 300) {
            $this->getLogger()->error('Brevo API responded with an error status', [
                'status' => $status,
                'message_id' => $messageId,
                'response' => $this->truncateForLog($body),
                'transport' => (string) $this,
                ...$logMeta,
            ]);

            throw new TransportException('Brevo API error: HTTP '.$status);
        }

        $this->getLogger()->info('Brevo email sent', [
            'status' => $status,
            'message_id' => $messageId,
            'transport' => (string) $this,
            ...$logMeta,
        ]);

        $message->appendDebug("Brevo API HTTP {$status}".(null !== $messageId ? " messageId={$messageId}" : '')."\n");
    }

    private function buildPayload(Email $email, SentMessage $sentMessage): array
    {
        $from = $email->getFrom()[0] ?? null;
        if (!$from instanceof Address) {
            throw new TransportException('Brevo requires a From address.');
        }

        $payload = [
            'sender' => array_filter([
                'email' => $from->getAddress(),
                'name' => $from->getName() ?: null,
            ], static fn ($value) => null !== $value && '' !== $value),
            'subject' => (string) $email->getSubject(),
        ];

        $to = $email->getTo();
        if (empty($to)) {
            $to = $sentMessage->getEnvelope()->getRecipients();
        }

        $payload['to'] = $this->mapAddresses($to);

        $cc = $this->mapAddresses($email->getCc());
        if (!empty($cc)) {
            $payload['cc'] = $cc;
        }

        $bcc = $this->mapAddresses($email->getBcc());
        if (!empty($bcc)) {
            $payload['bcc'] = $bcc;
        }

        $replyTo = $email->getReplyTo()[0] ?? null;
        if ($replyTo instanceof Address) {
            $payload['replyTo'] = array_filter([
                'email' => $replyTo->getAddress(),
                'name' => $replyTo->getName() ?: null,
            ], static fn ($value) => null !== $value && '' !== $value);
        }

        $html = $email->getHtmlBody();
        $text = $email->getTextBody();

        if (null !== $html && '' !== $html) {
            $payload['htmlContent'] = $html;
        }

        if (null !== $text && '' !== $text) {
            $payload['textContent'] = $text;
        }

        if (!isset($payload['htmlContent']) && !isset($payload['textContent'])) {
            throw new TransportException('Brevo requires either an HTML or text body.');
        }

        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            if (!$attachment instanceof DataPart) {
                continue;
            }

            $filename = $attachment->getFilename() ?? $attachment->getName() ?? 'attachment';

            $attachments[] = [
                'name' => $filename,
                'content' => base64_encode($attachment->getBody()),
            ];
        }

        if (!empty($attachments)) {
            $payload['attachment'] = $attachments;
        }

        return $payload;
    }

    /**
     * Build log metadata that helps debugging deliverability without leaking PII.
     */
    private function buildSafeLogMeta(Email $email, SentMessage $sentMessage): array
    {
        $from = $email->getFrom()[0] ?? null;
        $fromDomain = null;
        if ($from instanceof Address) {
            $fromDomain = $this->emailDomain($from->getAddress());
        }

        $to = $email->getTo();
        if (empty($to)) {
            $to = $sentMessage->getEnvelope()->getRecipients();
        }

        $domains = [];
        foreach ($to as $address) {
            if (!$address instanceof Address) {
                continue;
            }

            $domain = $this->emailDomain($address->getAddress());
            if (null !== $domain) {
                $domains[] = $domain;
            }
        }

        $domains = array_values(array_unique(array_filter($domains)));
        sort($domains);

        return array_filter([
            'from_domain' => $fromDomain,
            'to_count' => count($to),
            'to_domains' => $domains,
        ], static fn ($value) => null !== $value);
    }

    private function emailDomain(string $email): ?string
    {
        $pos = strrpos($email, '@');
        if (false === $pos) {
            return null;
        }

        $domain = strtolower(trim(substr($email, $pos + 1)));

        return $domain !== '' ? $domain : null;
    }

    /**
     * @param Address[] $addresses
     */
    private function mapAddresses(array $addresses): array
    {
        return array_map(
            static function (Address $address): array {
                return array_filter([
                    'email' => $address->getAddress(),
                    'name' => $address->getName() ?: null,
                ], static fn ($value) => null !== $value && '' !== $value);
            },
            $addresses
        );
    }

    private function truncateForLog(string $value, int $limit = 1000): string
    {
        if (strlen($value) <= $limit) {
            return $value;
        }

        return substr($value, 0, $limit).'...';
    }
}
