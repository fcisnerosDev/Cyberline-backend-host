<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Helpdesk\HelpdeskAttachment;
use Webklex\PHPIMAP\ClientManager;
use Exception;
use Carbon\Carbon;
use App\Models\Helpdesk\HelpdeskMessage;
use App\Models\Helpdesk\HelpdeskRecipient;
use Illuminate\Support\Facades\Storage;

class HelpdeskMailController extends Controller
{
    public function readInbox()
    {
        try {

            // PASO 1
            // dd('ENTRO AL METODO');

            $cm = new ClientManager();

            $client = $cm->make([
                'host' => env('IMAP_HOST'),
                'port' => env('IMAP_PORT'),
                'encryption' => env('IMAP_ENCRYPTION'),
                'validate_cert' => env('IMAP_VALIDATE_CERT', true),
                'username' => env('IMAP_USERNAME'),
                'password' => env('IMAP_PASSWORD'),
                'protocol' => env('IMAP_PROTOCOL', 'imap'),
            ]);

            // PASO 2
            // dd([
            //     'host' => env('IMAP_HOST'),
            //     'port' => env('IMAP_PORT'),
            //     'user' => env('IMAP_USERNAME'),
            //     'protocol' => env('IMAP_PROTOCOL'),
            //     'encryption' => env('IMAP_ENCRYPTION'),
            // ]);

            $client->connect();

            // PASO 3
            // dd('CONECTADO IMAP');

            $folders = $client->getFolders();

            // PASO 4
            $folderList = [];

            foreach ($folders as $folder) {
                $folderList[] = [
                    'name' => $folder->name ?? null,
                    'path' => $folder->path ?? null,
                ];
            }

            // dd($folderList);

            $inbox = $client->getFolder('INBOX');

            // PASO 5
            // dd([
            //     'name' => $inbox->name ?? null,
            //     'path' => $inbox->path ?? null,
            // ]);

            $messages = $inbox->messages()
                ->all()
                ->limit(100)
                ->get();

            // PASO 6
            // dd([
            //     'cantidad_mensajes' => $messages->count()
            // ]);

            $data = [];

            foreach ($messages as $message) {

                // PASO 7
                // dd([
                //     'subject' => $message->getSubject(),
                //     'message_id' => $message->getMessageId(),
                //     'from' => $message->getFrom(),
                // ]);

                $from = $message->getFrom();

                $fromEmail = strtolower(
                    $from[0]->mail ?? ''
                );

                $fromName = $from[0]->personal ?? null;

                // SOLO OCI
                if (
                    $fromEmail !== 'noreply@notification.sa-santiago-1.oci.oraclecloud.com'
                ) {
                    continue;
                }

                $messageId = $this->normalize(
                    $message->getMessageId()
                );

                if (
                    HelpdeskMessage::where(
                        'message_id',
                        $messageId
                    )->exists()
                ) {
                    continue;
                }

                $localDate = Carbon::parse(
                    (string) $message->getDate()
                )
                    ->setTimezone('America/Lima')
                    ->format('Y-m-d H:i:s');

                $htmlBody = $message->getHTMLBody();
                $textBody = $message->getTextBody();

                $body = $htmlBody ?: $textBody ?: '';

                $helpdeskMessage = HelpdeskMessage::create([
                    'message_id' => $messageId,
                    'subject' => $this->normalize(
                        $message->getSubject()
                    ),
                    'from_name' => $fromName,
                    'from_email' => $fromEmail,
                    'body' => $body,
                    'seen' => $message->hasFlag('Seen'),
                    'date' => $localDate,
                ]);

                $tos = $this->parseAddresses(
                    $message->getTo()
                );

                foreach ($tos as $to) {
                    HelpdeskRecipient::create([
                        'message_id' => $helpdeskMessage->id,
                        'type' => 'to',
                        'name' => $to['name'],
                        'email' => $to['email'],
                        'full' => $to['full'],
                    ]);
                }

                $ccs = $this->parseAddresses(
                    $message->getCc()
                );

                foreach ($ccs as $cc) {
                    HelpdeskRecipient::create([
                        'message_id' => $helpdeskMessage->id,
                        'type' => 'cc',
                        'name' => $cc['name'],
                        'email' => $cc['email'],
                        'full' => $cc['full'],
                    ]);
                }

                foreach ($message->getAttachments() as $attachment) {

                    $content = $attachment->getContent();

                    if (empty($content)) {
                        continue;
                    }

                    $originalName = $attachment->getName() ?: 'attachment';

                    $safeName = time() . '_' .
                        preg_replace(
                            '/[^a-zA-Z0-9_\.-]/',
                            '_',
                            $originalName
                        );

                    $path = 'helpdesk_attachments/' . $safeName;

                    Storage::disk('public')->put(
                        $path,
                        $content
                    );

                    $rawCid = $attachment->getContentId();

                    $cid = $rawCid
                        ? trim($rawCid, '<>')
                        : null;

                    if ($cid && $body) {

                        $publicUrl = asset(
                            'storage/' . $path
                        );

                        $body = str_replace(
                            [
                                'cid:' . $cid,
                                'cid:<' . $cid . '>',
                                'cid:&lt;' . $cid . '&gt;',
                            ],
                            $publicUrl,
                            $body
                        );
                    }

                    HelpdeskAttachment::create([
                        'message_id' => $helpdeskMessage->id,
                        'filename' => $safeName,
                        'mime_type' => $attachment->getContentType(),
                        'path' => $path,
                        'content_id' => $cid,
                    ]);
                }

                $helpdeskMessage->update([
                    'body' => $body
                ]);

                $data[] = [
                    'message_id' => $helpdeskMessage->message_id,
                    'subject' => $helpdeskMessage->subject,
                    'from' => [
                        'mail' => $helpdeskMessage->from_email,
                        'name' => $helpdeskMessage->from_name,
                    ],
                    'date' => $localDate,
                ];
            }

            return response()->json([
                'status' => 'ok',
                'count' => count($data),
                'messages' => $data,
            ]);

        } catch (Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }




    // /**
    //  * Normaliza los destinatarios del mensaje
    //  */
    private function parseAddresses($addresses): array
    {
        if (!$addresses) {
            return [];
        }

        $results = [];

        // Convertir todo a array de strings
        $list = is_array($addresses) ? $addresses : [$addresses];

        foreach ($list as $addr) {
            $full = trim($this->normalize($addr));

            // Ignorar placeholders "to" o "cc"
            if (in_array(strtolower($full), ['to', 'cc'])) {
                continue;
            }

            // Separar por comas, manejando nombres con <correo>
            $parts = preg_split('/,\s*(?=[^,]+<)/', $full);

            foreach ($parts as $part) {
                $part = trim($part);
                if (empty($part))
                    continue;

                $name = null;
                $email = null;

                if (preg_match('/(.*)<(.+)>/', $part, $matches)) {
                    $name = trim($matches[1]);
                    $email = trim($matches[2]);
                } else {
                    $email = $part;
                }

                $results[] = [
                    'full' => $part,
                    'name' => $name,
                    'email' => $email,
                ];
            }
        }

        return $results;
    }




    // /**
    //  * Normaliza cualquier valor (string, array, objeto)
    //  */
    private function normalize($value)
    {
        if (is_array($value)) {
            return implode(', ', array_map('strval', $value));
        }

        if (is_object($value)) {
            return method_exists($value, '__toString')
                ? (string) $value
                : json_encode($value);
        }

        return $value;
    }




    public function BandejaLectura()
    {
        $storageUrl = env('STORAGE_URL'); // toma la URL desde .env
        $messages = HelpdeskMessage::with(['toRecipients', 'ccRecipients', 'attachments'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $messages->getCollection()->transform(function ($message) use ($storageUrl) {
            return [
                'message_id' => $message->message_id,
                'subject' => $message->subject,
                'from' => [
                    'mail' => $message->from_email,
                    'name' => $message->from_name,
                ],
                'to' => $message->toRecipients->map(function ($r) {
                    return [
                        'full' => $r->full,
                        'name' => $r->name,
                        'email' => $r->email,
                    ];
                }),
                'cc' => $message->ccRecipients->map(function ($r) {
                    return [
                        'full' => $r->full,
                        'name' => $r->name,
                        'email' => $r->email,
                    ];
                }),
                'attachments' => $message->attachments->map(function ($a) use ($storageUrl) {
                    return [
                        'filename' => $a->filename,
                        'mime_type' => $a->mime_type,
                        'content_id' => $a->content_id,
                        'url' => $storageUrl . '/storage/' . $a->path,
                    ];
                }),
                'date' => $message->date->format('Y-m-d H:i:s'),
                'seen' => $message->seen,
                'body' => $message->body,
            ];
        });

        return response()->json($messages);
    }

}
