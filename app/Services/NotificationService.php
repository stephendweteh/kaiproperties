<?php

namespace App\Services;

use App\Models\CostRequest;
use App\Models\Setting;
use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function sendTicketAssigned(Ticket $ticket): void
    {
        $ticket->loadMissing(['technician:id,name,email,phone']);

        $technician = $ticket->technician;

        if (! $technician) {
            return;
        }

        $subject = 'Ticket Assigned: '.$ticket->ticket_no;
        $message = "Hello {$technician->name},\n\n".
            "You have been assigned ticket {$ticket->ticket_no}: {$ticket->title}.\n".
            "Please review and begin work as soon as possible.";

        $this->sendEmail($technician->email, $subject, $message);
        $this->sendSms($technician->phone, "Assigned {$ticket->ticket_no}: {$ticket->title}");
    }

    public function sendTicketStatusChanged(Ticket $ticket, string $oldStatus): void
    {
        $ticket->loadMissing(['reporter:id,name,email,phone']);

        $reporter = $ticket->reporter;

        if (! $reporter) {
            return;
        }

        $newStatus = str($ticket->status)->replace('_', ' ')->title()->toString();
        $oldStatusLabel = str($oldStatus)->replace('_', ' ')->title()->toString();

        $subject = 'Ticket Status Update: '.$ticket->ticket_no;
        $message = "Hello {$reporter->name},\n\n".
            "Your ticket {$ticket->ticket_no} changed from {$oldStatusLabel} to {$newStatus}.";

        $this->sendEmail($reporter->email, $subject, $message);
        $this->sendSms($reporter->phone, "{$ticket->ticket_no} status: {$newStatus}");
    }

    public function sendCostRequestReviewed(CostRequest $costRequest): void
    {
        $costRequest->loadMissing([
            'ticket:id,ticket_no,title,reported_by',
            'requester:id,name,email,phone',
        ]);

        $ticket = $costRequest->ticket;
        $requester = $costRequest->requester;

        if (! $ticket || ! $requester) {
            return;
        }

        $decision = str($costRequest->status)->title()->toString();

        $subject = 'Cost Request '.$decision.': '.$ticket->ticket_no;
        $message = "Hello {$requester->name},\n\n".
            "Your cost request for ticket {$ticket->ticket_no} has been {$decision}.";

        $this->sendEmail($requester->email, $subject, $message);
        $this->sendSms($requester->phone, "Cost request {$decision}: {$ticket->ticket_no}");
    }

    public function sendEmail(?string $to, string $subject, string $content): bool
    {
        if (empty($to)) {
            return false;
        }

        $host = Setting::valueFor('smtp_host');

        if (empty($host)) {
            return false;
        }

        try {
            $port = (int) (Setting::valueFor('smtp_port', '587') ?? '587');
            $username = Setting::valueFor('smtp_username');
            $password = Setting::valueFor('smtp_password');
            $encryption = Setting::valueFor('smtp_encryption', 'tls');
            $fromEmail = Setting::valueFor('smtp_from_email', 'no-reply@kai.local');
            $fromName = Setting::valueFor('smtp_from_name', Setting::valueFor('site_name', 'Kai Properties'));

            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $host,
                'mail.mailers.smtp.port' => $port,
                'mail.mailers.smtp.username' => $username,
                'mail.mailers.smtp.password' => $password,
                'mail.mailers.smtp.encryption' => $encryption === 'none' ? null : $encryption,
                'mail.from.address' => $fromEmail,
                'mail.from.name' => $fromName,
            ]);

            $manager = app('mail.manager');
            if (method_exists($manager, 'purge')) {
                $manager->purge('smtp');
            }

            Mail::mailer('smtp')->raw($content, function ($message) use ($to, $subject): void {
                $message->to($to)->subject($subject);
            });

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Notification email sending failed.', [
                'to' => $to,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function sendSms(?string $phone, string $message): bool
    {
        if (empty($phone)) {
            return false;
        }

        $apiKey = Setting::valueFor('arkesel_api_key');

        if (empty($apiKey)) {
            return false;
        }

        try {
            $senderId = Setting::valueFor('arkesel_sender_id', 'KaiProps');

            $response = Http::timeout(12)
                ->acceptJson()
                ->withHeaders(['api-key' => $apiKey])
                ->post('https://sms.arkesel.com/api/v2/sms/send', [
                    'sender' => $senderId,
                    'message' => $message,
                    'recipients' => [$phone],
                ]);

            if (! $response->successful()) {
                Log::warning('Arkesel SMS request failed.', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Notification SMS sending failed.', [
                'phone' => $phone,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
